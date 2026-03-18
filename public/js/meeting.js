/**
 * public/js/meeting.js
 * JaaS (Jitsi as a Service) meeting integration.
 * Uses JWT auth → lobby disabled server-side, moderator assigned by token.
 * Opens in a new tab — clean, no iframe lobby issues.
 *
 * Requires: BASE, PROJECT_ID, window._meetingUserName, window._canEndMeeting
 */

let activeMeeting    = null;
let meetingPollTimer = null;
const MEETING_POLL_MS = 6000;

// ── Boot ──────────────────────────────────────────────────────────────
async function initMeeting(userName) {
    if (userName) window._meetingUserName = userName;
    await checkActiveMeeting();
    meetingPollTimer = setInterval(checkActiveMeeting, MEETING_POLL_MS);
}

// ── Poll ──────────────────────────────────────────────────────────────
async function checkActiveMeeting() {
    try {
        const res  = await fetch(`${BASE}/api/meetings/active?project_id=${PROJECT_ID}`, { credentials:'same-origin' });
        const json = await res.json();
        if (!json.success) return;

        const wasActive = !!activeMeeting;
        const isActive  = !!json.data;
        activeMeeting   = json.data || null;

        if (wasActive !== isActive) {
            renderMeetingBar();
            syncHeroButton();
        }
    } catch {}
}

// ── Meeting bar ───────────────────────────────────────────────────────
function renderMeetingBar() {
    const bar = document.getElementById('meeting-bar');
    if (!bar) return;

    if (!activeMeeting) {
        bar.innerHTML = ''; bar.style.display = 'none'; return;
    }

    const started = new Date(activeMeeting.started_at)
        .toLocaleTimeString('en-US', { hour:'2-digit', minute:'2-digit' });

    bar.style.display = 'block';
    bar.innerHTML = `
        <div style="background:linear-gradient(90deg,#1a56db,#1e40af);border-radius:10px;
            padding:13px 20px;margin-bottom:16px;display:flex;align-items:center;gap:14px;
            box-shadow:0 4px 18px rgba(26,86,219,.35);animation:slideDown .3s ease">
            <span style="width:9px;height:9px;border-radius:50%;background:#34d399;
                flex-shrink:0;animation:livePulse 1.4s infinite"></span>
            <div style="flex:1">
                <div style="font-size:14px;font-weight:700;color:#fff">Meeting in progress</div>
                <div style="font-size:12px;color:rgba(255,255,255,.7)">Started at ${started}</div>
            </div>
            <button onclick="joinMeeting()" style="padding:9px 20px;background:#fff;color:#1a56db;
                border:none;border-radius:8px;font-family:'DM Sans',sans-serif;
                font-size:13px;font-weight:700;cursor:pointer"
                onmouseenter="this.style.opacity='.85'" onmouseleave="this.style.opacity='1'">
                📹 Join Meeting
            </button>
            ${window._canEndMeeting ? `
            <button onclick="endMeeting()" style="padding:9px 16px;background:rgba(255,255,255,.12);
                color:#fff;border:1px solid rgba(255,255,255,.3);border-radius:8px;
                font-family:'DM Sans',sans-serif;font-size:13px;font-weight:600;cursor:pointer">
                End
            </button>` : ''}
        </div>
        <style>
            @keyframes livePulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.4;transform:scale(1.6)} }
            @keyframes slideDown { from{opacity:0;transform:translateY(-8px)} to{opacity:1;transform:translateY(0)} }
        </style>`;
}

// ── Sync hero button ──────────────────────────────────────────────────
function syncHeroButton() {
    const btn = document.getElementById('meet-hero-btn');
    if (!btn) return;

    if (window._canEndMeeting) {
        btn.style.display = 'inline-flex';
        if (activeMeeting) {
            btn.innerHTML        = '📹 Meeting Live — Join';
            btn.onclick          = joinMeeting;
            btn.style.background = 'linear-gradient(135deg,#059669,#047857)';
            btn.style.boxShadow  = '0 4px 14px rgba(5,150,105,.4)';
        } else {
            btn.innerHTML        = '📹 Start Meeting';
            btn.onclick          = startMeeting;
            btn.style.background = 'linear-gradient(135deg,#1a56db,#0e3fa5)';
            btn.style.boxShadow  = '0 4px 14px rgba(26,86,219,.35)';
        }
        btn.disabled = false;
    } else {
        // Member: only show when meeting is live
        btn.style.display = activeMeeting ? 'inline-flex' : 'none';
        if (activeMeeting) {
            btn.innerHTML = '📹 Join Meeting';
            btn.onclick   = joinMeeting;
        }
    }
}

// ── Start meeting ─────────────────────────────────────────────────────
async function startMeeting() {
    const btn = document.getElementById('meet-hero-btn');
    if (btn) { btn.textContent = 'Starting…'; btn.disabled = true; }

    try {
        const res  = await fetch(BASE + '/api/meetings/start', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            credentials: 'same-origin',
            body: JSON.stringify({ project_id: PROJECT_ID }),
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);

        activeMeeting = json.data;
        renderMeetingBar();
        syncHeroButton();

        // Open with the JWT returned from the start response
        openJaaSTab(json.data.jaas_url, json.data.token);
        showToast('Meeting started — others can now join!');
    } catch (err) {
        showToast(err.message, 'error');
        if (btn) { btn.innerHTML = '📹 Start Meeting'; btn.disabled = false; }
    }
}

// ── Join meeting (fetch a fresh JWT for this user, then open) ─────────
async function joinMeeting() {
    if (!activeMeeting) {
        showToast('No active meeting — ask the manager to start one first.', 'error');
        return;
    }

    try {
        const res  = await fetch(`${BASE}/api/meetings/token?project_id=${PROJECT_ID}`, { credentials:'same-origin' });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);

        openJaaSTab(json.data.jaas_url, json.data.token);
    } catch (err) {
        showToast(err.message, 'error');
    }
}

// ── Open JaaS tab with JWT ────────────────────────────────────────────
// The JWT has features.lobby=false — JaaS server honours this and lets
// the user straight in with no lobby, no moderator wait.
function openJaaSTab(jaasUrl, token) {
    const url = `${jaasUrl}?jwt=${token}#config.prejoinPageEnabled=false&config.startWithVideoMuted=true&config.startWithAudioMuted=false`;
    window.open(url, '_blank');
}

// ── End meeting ───────────────────────────────────────────────────────
async function endMeeting() {
    if (!activeMeeting || !confirm('End the meeting for everyone?')) return;
    try {
        const res  = await fetch(BASE + '/api/meetings/end', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            credentials: 'same-origin',
            body: JSON.stringify({ meeting_id: activeMeeting.meeting_id }),
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        activeMeeting = null;
        renderMeetingBar();
        syncHeroButton();
        showToast('Meeting ended.');
    } catch (err) { showToast(err.message, 'error'); }
}

// ── Aliases for any old HTML that still has these ─────────────────────
function openMeetingModal()  { joinMeeting(); }
function openMeetingTab()    { joinMeeting(); }
function closeMeetingModal() {}
function closeJitsiModal()   {}