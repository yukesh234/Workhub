/**
 * public/js/project-detail.js
 * Drives the Project Detail page. Requires app.js loaded first.
 */

const PROJECT_ID = parseInt(new URLSearchParams(location.search).get('id')) || 0;
let allTasks     = [];
let allMembers   = [];
let activeTab    = 'all';
let currentTask  = null; // full task object open in side panel

if (!PROJECT_ID) location.href = BASE + '/projects';

// ── Boot ──────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    loadSidebarOrg();

    const el = document.getElementById('topbarDate');
    if (el) el.textContent = new Date().toLocaleDateString('en-US', { weekday:'long', month:'long', day:'numeric' });

    document.getElementById('tf-due').min = new Date().toISOString().split('T')[0];

    Promise.all([loadProject(), loadMembers(), loadTasks()]);

    // Init meeting — admin always has canEndMeeting
    window._canEndMeeting = true;
    initMeeting(window._meetingUserName || 'Admin').then(() => syncHeroButton());

    document.getElementById('task-tabs').addEventListener('click', e => {
        const btn = e.target.closest('.task-tab');
        if (!btn) return;
        document.querySelectorAll('.task-tab').forEach(t => t.classList.remove('active'));
        btn.classList.add('active');
        activeTab = btn.dataset.status;
        renderTasks();
    });
});

// ── Load project ──────────────────────────────────────────────────────
async function loadProject() {
    try {
        const res  = await fetch(`${BASE}/api/projects/single?project_id=${PROJECT_ID}`, { credentials: 'same-origin' });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        renderHero(json.data);
    } catch (err) {
        document.getElementById('hero-name').textContent = 'Failed to load project';
    }
}

function renderHero(p) {
    document.title = `${p.name} — WorkHub`;
    document.getElementById('topbar-project-name').textContent = p.name;
    document.getElementById('hero-name').textContent = p.name;
    document.getElementById('hero-desc').textContent = p.description || 'No description provided.';
    if (!p.description) document.getElementById('hero-desc').style.fontStyle = 'italic';

    document.getElementById('hero-meta').innerHTML = `
        <span class="status-pill ${esc(p.status)}">${esc(p.status)}</span>
        <span class="meta-chip">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
            Created ${formatDate(p.created_at)}
        </span>`;

    document.getElementById('hero-actions').innerHTML = `
        <button id="meet-hero-btn" onclick="startMeeting()" style="
            display:inline-flex;align-items:center;gap:8px;
            padding:9px 18px;border-radius:var(--radius-sm);
            background:linear-gradient(135deg,#1a56db,#0e3fa5);
            color:#fff;border:none;font-family:'DM Sans',sans-serif;
            font-size:13px;font-weight:600;cursor:pointer;
            box-shadow:0 4px 14px rgba(26,86,219,.35);transition:opacity .2s">
            📹 Start Meeting
        </button>
        <button class="btn-primary" style="padding:9px 18px;font-size:13px" onclick="openCreateTaskModal()">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            New Task
        </button>`;
}

// ── Load members ──────────────────────────────────────────────────────
async function loadMembers() {
    try {
        const res  = await fetch(`${BASE}/api/projects/members?project_id=${PROJECT_ID}`, { credentials: 'same-origin' });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        allMembers = json.data || [];
        populateAssigneeDropdown();
    } catch { allMembers = []; }
}

function populateAssigneeDropdown() {
    const sel = document.getElementById('tf-assigned');
    sel.innerHTML = '<option value="">— Select member —</option>' +
        allMembers.map(m => `<option value="${m.user_id}">${esc(m.name || m.email)}</option>`).join('');
}

// ── Load tasks ────────────────────────────────────────────────────────
async function loadTasks() {
    try {
        const res  = await fetch(`${BASE}/api/tasks?project_id=${PROJECT_ID}`, { credentials: 'same-origin' });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        allTasks = json.data || [];
        renderTasks(); renderStats(); renderWorkload(); renderDonut(); renderPriorityBars();
    } catch (err) {
        document.getElementById('tasks-list').innerHTML =
            `<div class="tasks-empty"><p>${esc(err.message || 'Failed to load tasks.')}</p></div>`;
    }
}

// ── Render task list ──────────────────────────────────────────────────
function renderTasks() {
    const list = document.getElementById('tasks-list');
    const now  = new Date(); now.setHours(0,0,0,0);
    const filtered = allTasks.filter(t => activeTab === 'all' || t.status === activeTab);

    if (filtered.length === 0) {
        list.innerHTML = `
            <div class="tasks-empty">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                </svg>
                <p>${activeTab === 'all' ? 'No tasks yet. Create the first one!' : `No ${activeTab.replace('_',' ')} tasks.`}</p>
            </div>`;
        return;
    }

    list.innerHTML = filtered.map(t => {
        const isDone    = t.status === 'completed';
        const dueDate   = t.due_date ? new Date(t.due_date) : null;
        const isOverdue = dueDate && dueDate < now && !isDone;
        const initials  = (t.assigned_user_name || '?').split(' ').map(w => w[0]).slice(0,2).join('').toUpperCase();
        const assignedMember = allMembers.find(m => m.user_id === t.assigned_to);
        const assigneeRole   = assignedMember?.role ?? null;

        return `
        <div class="task-row" onclick="openTaskPanel(${t.task_id})">
            <div class="task-check ${isDone ? 'done' : ''}"></div>
            <div class="task-body">
                <div class="task-title ${isDone ? 'done' : ''}">${esc(t.title)}</div>
                <div class="task-meta-row">
                    <div class="priority-dot ${esc(t.priority)}"></div>
                    ${t.due_date ? `<span class="task-due ${isOverdue ? 'overdue' : ''}">${isOverdue ? '⚠ ' : ''}${formatDateShort(t.due_date)}</span>` : ''}
                    ${t.assigned_user_name ? `
                    <span class="task-assignee-chip">
                        <div class="assignee-mini">${initials}</div>
                        ${esc(t.assigned_user_name)}
                        ${assigneeRole ? `<span style="font-size:10px;font-weight:700;padding:1px 5px;border-radius:10px;${assigneeRole === 'manager' ? 'background:var(--brand-pale2);color:var(--brand)' : 'background:var(--surface-2);color:var(--text-muted)'}">${assigneeRole}</span>` : ''}
                    </span>` : ''}
                </div>
            </div>
            <span class="task-status-badge ${esc(t.status)}">${esc(t.status.replace('_',' '))}</span>
            <div class="task-row-actions" onclick="event.stopPropagation()">
                <button class="btn-task-action" title="Edit" onclick="openEditTaskModal(${t.task_id})">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                </button>
                <button class="btn-task-action danger" title="Delete" onclick="confirmDeleteTask(${t.task_id})">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"/>
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                        <path d="M10 11v6"/><path d="M14 11v6"/>
                    </svg>
                </button>
            </div>
        </div>`;
    }).join('');
}

// ── Stats ─────────────────────────────────────────────────────────────
function renderStats() {
    const now     = new Date(); now.setHours(0,0,0,0);
    const total   = allTasks.length;
    const done    = allTasks.filter(t => t.status === 'completed').length;
    const inProg  = allTasks.filter(t => t.status === 'in_progress').length;
    const overdue = allTasks.filter(t => t.due_date && new Date(t.due_date) < now && t.status !== 'completed').length;

    document.getElementById('stat-total').textContent    = total;
    document.getElementById('stat-done').textContent     = done;
    document.getElementById('stat-progress').textContent = inProg;
    document.getElementById('stat-overdue').textContent  = overdue;

    const pct = total > 0 ? Math.round((done / total) * 100) : 0;
    document.getElementById('progress-pct').textContent = pct + '%';
    setTimeout(() => { document.getElementById('progress-fill').style.width = pct + '%'; }, 100);
}

// ── Workload ──────────────────────────────────────────────────────────
function renderWorkload() {
    const wrap = document.getElementById('members-workload');
    if (allMembers.length === 0) {
        wrap.innerHTML = `<div style="padding:20px;text-align:center;font-size:13px;color:var(--text-muted)">No members yet.</div>`;
        return;
    }
    const counts = {};
    allMembers.forEach(m => { counts[m.user_id] = { total: 0, done: 0 }; });
    allTasks.forEach(t => {
        if (t.assigned_to && counts[t.assigned_to]) {
            counts[t.assigned_to].total++;
            if (t.status === 'completed') counts[t.assigned_to].done++;
        }
    });
    const maxTasks = Math.max(...allMembers.map(m => counts[m.user_id]?.total || 0), 1);
    wrap.innerHTML = allMembers.map(m => {
        const c      = counts[m.user_id] || { total: 0, done: 0 };
        const initials = (m.name || '?').split(' ').map(w => w[0]).slice(0,2).join('').toUpperCase();
        const barW   = Math.round((c.total / maxTasks) * 100);
        const roleCss = m.role === 'manager'
            ? 'background:var(--brand-pale2);color:var(--brand)'
            : 'background:var(--surface-2);color:var(--text-muted);border:1px solid var(--border)';
        const avatarHtml = m.userProfile
            ? `<img src="${esc(m.userProfile)}" style="width:100%;height:100%;object-fit:cover;border-radius:50%">`
            : initials;
        return `
        <div class="member-workload-row">
            <div class="mw-avatar">${avatarHtml}</div>
            <div class="mw-info">
                <div style="display:flex;align-items:center;gap:6px">
                    <div class="mw-name">${esc(m.name || '—')}</div>
                    <span style="font-size:10px;font-weight:700;padding:2px 7px;border-radius:20px;text-transform:capitalize;${roleCss}">${esc(m.role)}</span>
                </div>
                <div class="mw-bar-wrap" style="margin-top:5px"><div class="mw-bar-fill" style="width:${barW}%"></div></div>
            </div>
            <div class="mw-tasks"><div class="mw-count">${c.done}/${c.total}</div><div class="mw-label">done</div></div>
        </div>`;
    }).join('');
}

// ── Donut ─────────────────────────────────────────────────────────────
function renderDonut() {
    const wrap = document.getElementById('donut-chart-wrap');
    const total = allTasks.length;
    const segments = [
        { key:'completed',   label:'Completed',   color:'#1a8a5c' },
        { key:'in_progress', label:'In Progress',  color:'#ea580c' },
        { key:'in_review',   label:'In Review',    color:'#6A0031' },
        { key:'pending',     label:'Pending',      color:'#e8dde3' },
    ];
    const counts = {};
    segments.forEach(s => { counts[s.key] = allTasks.filter(t => t.status === s.key).length; });

    if (total === 0) { wrap.innerHTML = `<div style="font-size:13px;color:var(--text-muted);padding:20px 0">No tasks yet.</div>`; return; }

    const R = 54, C = 2 * Math.PI * R;
    let offset = 0;
    const paths = segments.map(s => {
        const dash = (counts[s.key] / total) * C;
        const el   = `<circle cx="70" cy="70" r="${R}" fill="none" stroke="${s.color}" stroke-width="16"
                        stroke-dasharray="${dash.toFixed(2)} ${(C-dash).toFixed(2)}"
                        stroke-dashoffset="${(-offset).toFixed(2)}" stroke-linecap="butt"/>`;
        offset += dash; return el;
    }).join('');

    wrap.innerHTML = `
        <div class="donut-wrapper">
            <svg class="donut-svg" viewBox="0 0 140 140">
                <circle cx="70" cy="70" r="${R}" fill="none" stroke="var(--border)" stroke-width="16"/>
                ${paths}
            </svg>
            <div class="donut-center">
                <div class="donut-center-val">${Math.round((counts.completed/total)*100)}%</div>
                <div class="donut-center-lbl">done</div>
            </div>
        </div>
        <div class="chart-legend">
            ${segments.map(s => `
            <div class="legend-item">
                <div class="legend-dot" style="background:${s.color}"></div>
                <span class="legend-lbl">${s.label}</span>
                <span class="legend-val">${counts[s.key]}</span>
            </div>`).join('')}
        </div>`;
}

// ── Priority bars ─────────────────────────────────────────────────────
function renderPriorityBars() {
    const wrap   = document.getElementById('priority-bars');
    const levels = ['critical','high','medium','low'];
    const counts = {};
    levels.forEach(l => { counts[l] = allTasks.filter(t => t.priority === l).length; });
    const maxCount = Math.max(...Object.values(counts), 1);
    wrap.innerHTML = levels.map(l => `
        <div class="priority-bar-row">
            <span class="priority-bar-lbl">${l}</span>
            <div class="priority-bar-track"><div class="priority-bar-fill ${l}" style="width:${Math.round((counts[l]/maxCount)*100)}%"></div></div>
            <span class="priority-bar-count">${counts[l]}</span>
        </div>`).join('');
}

// ══════════════════════════════════════════════════════════════════════
// ── Task side panel (view-only + comments + attachments) ──────────────
// ══════════════════════════════════════════════════════════════════════

async function openTaskPanel(taskId) {
    const task = allTasks.find(t => t.task_id === taskId);
    if (!task) return;
    currentTask = task;

    // Fill meta
    const now     = new Date(); now.setHours(0,0,0,0);
    const dueDate = task.due_date ? new Date(task.due_date) : null;
    const overdue = dueDate && dueDate < now && task.status !== 'completed';

    document.getElementById('tdp-title').textContent = task.title;

    // Status — display only, no click
    const statusColors = {
        pending:     { bg:'var(--surface-2)',   color:'var(--text-muted)' },
        in_progress: { bg:'#fff7ed',             color:'#ea580c' },
        in_review:   { bg:'var(--brand-pale2)', color:'var(--brand)' },
        completed:   { bg:'#e6f9f1',             color:'#1a8a5c' },
    };
    const sc = statusColors[task.status] || statusColors.pending;
    document.getElementById('tdp-status-display').innerHTML = `
        <span style="display:inline-flex;align-items:center;gap:6px;padding:5px 14px;border-radius:20px;font-size:13px;font-weight:600;background:${sc.bg};color:${sc.color}">
            ${esc(task.status.replace('_',' '))}
        </span>
        <span style="font-size:12px;color:var(--text-muted);margin-left:6px">Status is updated by the assigned member</span>`;

    document.getElementById('tdp-desc').textContent   = task.description || 'No description.';
    document.getElementById('tdp-desc').className     = 'tdp-desc' + (!task.description ? ' empty' : '');
    document.getElementById('tdp-priority').textContent = task.priority.charAt(0).toUpperCase() + task.priority.slice(1);
    document.getElementById('tdp-due').textContent    = task.due_date ? (formatDateShort(task.due_date) + (overdue ? ' ⚠' : '')) : '—';
    document.getElementById('tdp-due').style.color    = overdue ? '#dc2626' : '';

    const assignedMember = allMembers.find(m => m.user_id === task.assigned_to);
    const roleLabel = assignedMember
        ? ` <span style="font-size:10px;font-weight:700;padding:2px 6px;border-radius:10px;vertical-align:middle;${assignedMember.role === 'manager' ? 'background:var(--brand-pale2);color:var(--brand)' : 'background:var(--surface-2);color:var(--text-muted)'}">${assignedMember.role}</span>`
        : '';
    document.getElementById('tdp-assignee').innerHTML = (task.assigned_user_name || '—') + roleLabel;
    document.getElementById('tdp-created').textContent = formatDateShort(task.created_at);

    // Open panel
    document.getElementById('task-detail-backdrop').classList.add('open');

    // Comment + upload visible to everyone (admin and all members)
    document.getElementById('tdp-comment-compose').style.display = 'flex';
    document.getElementById('tdp-upload-btn').style.display      = 'flex';

    // Load comments + attachments in parallel
    loadComments(task.task_id);
    loadAttachments(task.task_id);
}

function closeTaskPanel(e) {
    if (e && e.target !== document.getElementById('task-detail-backdrop')) return;
    document.getElementById('task-detail-backdrop').classList.remove('open');
    currentTask = null;
}

// ── Comments ──────────────────────────────────────────────────────────
async function loadComments(taskId) {
    const wrap = document.getElementById('tdp-comments-list');
    wrap.innerHTML = `<div style="padding:12px 0;color:var(--text-muted);font-size:13px">Loading…</div>`;
    try {
        const res  = await fetch(`${BASE}/api/tasks/comments?task_id=${taskId}&project_id=${PROJECT_ID}`, { credentials:'same-origin' });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        renderComments(json.data);
    } catch { wrap.innerHTML = `<div style="color:#dc2626;font-size:13px">Failed to load comments.</div>`; }
}

function renderComments(comments) {
    const wrap = document.getElementById('tdp-comments-list');
    if (!comments.length) {
        wrap.innerHTML = `<div style="padding:8px 0;color:var(--text-muted);font-size:13px;font-style:italic">No comments yet. Be the first.</div>`;
        return;
    }
    wrap.innerHTML = comments.map(c => {
        const initials = (c.author_name || '?').split(/[\s@]/)[0].charAt(0).toUpperCase();
        const isImage  = c.author_avatar;
        const avatar   = isImage
            ? `<img src="${esc(c.author_avatar)}" style="width:28px;height:28px;border-radius:50%;object-fit:cover">`
            : `<div style="width:28px;height:28px;border-radius:50%;background:var(--brand);color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0">${initials}</div>`;
        return `
        <div class="tdp-comment" id="comment-${c.comment_id}">
            <div class="tdp-comment-header">
                ${avatar}
                <span class="tdp-comment-author">${esc(c.author_name || '—')}</span>
                <span class="tdp-comment-time">${formatDateShort(c.created_at)}</span>
                <button class="tdp-comment-delete" onclick="deleteComment(${c.comment_id})" title="Delete">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            <div class="tdp-comment-body">${esc(c.body)}</div>
        </div>`;
    }).join('');
}

async function postComment() {
    if (!currentTask) return;
    const input = document.getElementById('tdp-comment-input');
    const body  = input.value.trim();
    if (!body) return;

    const btn = document.getElementById('tdp-comment-submit');
    btn.disabled = true;

    try {
        const res  = await fetch(BASE + '/api/tasks/comments', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ task_id: currentTask.task_id, project_id: PROJECT_ID, body }),
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        input.value = '';
        renderComments(json.data);
    } catch (err) { showToast(err.message, 'error'); }
    finally { btn.disabled = false; }
}

async function deleteComment(commentId) {
    if (!confirm('Delete this comment?')) return;
    try {
        const res  = await fetch(BASE + '/api/tasks/comments', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ comment_id: commentId }),
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        const el = document.getElementById(`comment-${commentId}`);
        if (el) { el.style.opacity='0'; el.style.transform='translateX(8px)'; el.style.transition='.2s'; setTimeout(() => el.remove(), 200); }
        showToast('Comment deleted.');
    } catch (err) { showToast(err.message, 'error'); }
}

// Enter to submit comment (Shift+Enter for newline)
document.getElementById('tdp-comment-input')?.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); postComment(); }
});

// ── Attachments ───────────────────────────────────────────────────────
async function loadAttachments(taskId) {
    const wrap = document.getElementById('tdp-attachments-list');
    wrap.innerHTML = `<div style="padding:8px 0;color:var(--text-muted);font-size:13px">Loading…</div>`;
    try {
        const res  = await fetch(`${BASE}/api/tasks/attachments?task_id=${taskId}&project_id=${PROJECT_ID}`, { credentials:'same-origin' });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        renderAttachments(json.data);
    } catch { wrap.innerHTML = `<div style="color:#dc2626;font-size:13px">Failed to load attachments.</div>`; }
}

function renderAttachments(attachments) {
    const wrap = document.getElementById('tdp-attachments-list');
    if (!attachments.length) {
        wrap.innerHTML = `<div style="padding:4px 0;color:var(--text-muted);font-size:13px;font-style:italic">No files attached yet.</div>`;
        return;
    }
    const icons = {
        'application/pdf': '📄',
        'text/plain': '📝', 'text/csv': '📊',
        'application/msword': '📝',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document': '📝',
        'application/vnd.ms-excel': '📊',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': '📊',
    };
    wrap.innerHTML = attachments.map(a => {
        const isImage = a.file_type?.startsWith('image/');
        const icon    = isImage ? '🖼️' : (icons[a.file_type] || '📎');
        const sizeStr = a.file_size > 1024*1024
            ? (a.file_size / (1024*1024)).toFixed(1) + ' MB'
            : (a.file_size / 1024).toFixed(0) + ' KB';
        return `
        <div class="tdp-attachment" id="att-${a.attachment_id}">
            ${isImage
                ? `<img src="${esc(a.file_url)}" class="tdp-att-thumb" onclick="window.open('${esc(a.file_url)}','_blank')" title="View full size">`
                : `<div class="tdp-att-icon">${icon}</div>`}
            <div class="tdp-att-info">
                <a href="${esc(a.file_url)}" target="_blank" class="tdp-att-name" title="${esc(a.file_name)}">${esc(a.file_name)}</a>
                <div class="tdp-att-meta">${sizeStr} · ${formatDateShort(a.created_at)}</div>
            </div>
            <button class="tdp-att-delete" onclick="deleteAttachment(${a.attachment_id})" title="Remove">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>`;
    }).join('');
}

async function uploadAttachment(input) {
    if (!input.files.length || !currentTask) return;
    const file     = input.files[0];
    const formData = new FormData();
    formData.append('file',       file);
    formData.append('task_id',    currentTask.task_id);
    formData.append('project_id', PROJECT_ID);

    const btn = document.getElementById('tdp-upload-btn');
    const lbl = document.getElementById('tdp-upload-label');
    btn.classList.add('loading');
    lbl.textContent = 'Uploading…';

    try {
        const res  = await fetch(BASE + '/api/tasks/attachments', {
            method: 'POST',
            credentials: 'same-origin',
            body: formData,
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        renderAttachments(json.data);
        showToast('File uploaded!');
    } catch (err) { showToast(err.message, 'error'); }
    finally {
        btn.classList.remove('loading');
        lbl.textContent = 'Attach file';
        input.value = '';
    }
}

async function deleteAttachment(attachmentId) {
    if (!confirm('Remove this attachment?')) return;
    try {
        const res  = await fetch(BASE + '/api/tasks/attachments', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ attachment_id: attachmentId }),
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        const el = document.getElementById(`att-${attachmentId}`);
        if (el) { el.style.opacity='0'; el.style.transition='.2s'; setTimeout(() => el.remove(), 200); }
        showToast('Attachment removed.');
    } catch (err) { showToast(err.message, 'error'); }
}

// ── Create task ───────────────────────────────────────────────────────
function openCreateTaskModal() {
    document.getElementById('task-modal-title').textContent = 'New Task';
    document.getElementById('task-modal-sub').textContent   = 'Add a task to this project.';
    document.getElementById('task-submit-btn').querySelector('.btn-text').textContent = 'Create Task';
    document.getElementById('tf-task-id').value = '';
    document.getElementById('task-form').reset();
    document.getElementById('task-form-error').style.display = 'none';
    document.getElementById('tf-due').min = new Date().toISOString().split('T')[0];
    openModal('modal-task');
}

// ── Edit task ─────────────────────────────────────────────────────────
function openEditTaskModal(taskId) {
    const task = allTasks.find(t => t.task_id === taskId);
    if (!task) return;

    document.getElementById('task-modal-title').textContent = 'Edit Task';
    document.getElementById('task-modal-sub').textContent   = 'Update the task details.';
    document.getElementById('task-submit-btn').querySelector('.btn-text').textContent = 'Save Changes';
    document.getElementById('task-form-error').style.display = 'none';
    document.getElementById('tf-task-id').value  = task.task_id;
    document.getElementById('tf-title').value    = task.title;
    document.getElementById('tf-desc').value     = task.description || '';
    document.getElementById('tf-assigned').value = task.assigned_to || '';
    document.getElementById('tf-due').value      = task.due_date ? task.due_date.split(' ')[0] : '';
    document.getElementById('tf-priority').value = task.priority;
    document.getElementById('tf-due').min        = '';

    // Close panel first, then open modal after the transition finishes
    // so the panel backdrop doesn't swallow the click
    document.getElementById('task-detail-backdrop').classList.remove('open');
    currentTask = null;
    setTimeout(() => openModal('modal-task'), 250);
}

document.getElementById('task-form')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn    = document.getElementById('task-submit-btn');
    const errEl  = document.getElementById('task-form-error');
    const taskId = document.getElementById('tf-task-id').value;
    const isEdit = !!taskId;

    errEl.style.display = 'none';
    btn.classList.add('loading');

    const payload = {
        project_id:  PROJECT_ID,
        title:       document.getElementById('tf-title').value.trim(),
        description: document.getElementById('tf-desc').value.trim() || null,
        assigned_to: parseInt(document.getElementById('tf-assigned').value) || null,
        due_date:    document.getElementById('tf-due').value || null,
        priority:    document.getElementById('tf-priority').value,
        // status intentionally omitted — only assigned user can change it
    };
    if (isEdit) {
        payload.task_id = parseInt(taskId);
        // For edit, pass current status unchanged
        const existing = allTasks.find(t => t.task_id === parseInt(taskId));
        if (existing) payload.status = existing.status;
    } else {
        payload.status = 'pending'; // new tasks always start as pending
    }

    try {
        const res  = await fetch(BASE + '/api/tasks', {
            method: isEdit ? 'PUT' : 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify(payload),
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        closeModal('modal-task');
        await loadTasks();
        showToast(isEdit ? 'Task updated!' : 'Task created!');
    } catch (err) {
        errEl.textContent   = err.message;
        errEl.style.display = 'block';
    } finally { btn.classList.remove('loading'); }
});

// ── Delete task ───────────────────────────────────────────────────────
async function confirmDeleteTask(taskId) {
    if (!confirm('Delete this task? This cannot be undone.')) return;
    await doDeleteTask(taskId);
}

async function deleteTaskFromPanel() {
    if (!currentTask || !confirm('Delete this task? This cannot be undone.')) return;
    const id = currentTask.task_id;
    document.getElementById('task-detail-backdrop').classList.remove('open');
    await doDeleteTask(id);
}

async function doDeleteTask(taskId) {
    try {
        const res  = await fetch(BASE + '/api/tasks', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ task_id: taskId, project_id: PROJECT_ID }),
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        await loadTasks();
        showToast('Task deleted.', 'error');
    } catch (err) { showToast(err.message, 'error'); }
}

// ── ESC closes panel ──────────────────────────────────────────────────
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        document.getElementById('task-detail-backdrop').classList.remove('open');
        currentTask = null;
    }
});