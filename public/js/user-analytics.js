/**
 * public/js/user-analytics.js
 * Manager analytics page — fetches /api/user/analytics and renders all widgets.
 */

let _rawData        = null;   // full API response
let _activeProject  = 'all';  // currently selected project pill

//  Sidebar nav state (shared pattern) 
let _navProjectsOpen   = false;
let _navProjectsLoaded = false;

//  Boot 
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('topbar-date').textContent =
        new Date().toLocaleDateString('en-US', { weekday:'long', month:'long', day:'numeric' });

    loadAnalytics();
    NotifPoll.start(BASE, () => loadNotifications());
    toggleProjectsNav(); // auto-open projects nav
});

// ── Fetch 
async function loadAnalytics() {
    try {
        const res  = await fetch(BASE + '/api/user/analytics', { credentials: 'same-origin' });
        const json = await res.json();
        console.log('Analytics data:', json);
        if (!json.success) throw new Error(json.message);

        _rawData = json.data;

        buildProjectPills(_rawData.projects);
        renderAll('all');

        // Also feed the sidebar nav
        if (!_navProjectsLoaded) {
            _navProjectsLoaded = true;
            renderProjectsNav(_rawData.projects.map(p => ({ ...p, my_role: 'manager' })));
        }

    } catch (err) {
        showToast(err.message || 'Failed to load analytics', 'error');
    }
}

//  Project filter pills 
function buildProjectPills(projects) {
    const wrap = document.getElementById('project-pills');
    projects.forEach(p => {
        const btn = document.createElement('button');
        btn.className   = 'project-pill';
        btn.dataset.id  = p.project_id;
        btn.textContent = p.name;
        btn.onclick     = () => filterByProject(p.project_id, btn);
        wrap.appendChild(btn);
    });
}

function filterByProject(projectId, btn) {
    _activeProject = projectId;
    document.querySelectorAll('.project-pill').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    renderAll(projectId);
}

//  Master render 
function renderAll(projectId) {
    if (!_rawData) return;

    const isAll    = projectId === 'all';
    const pidInt   = isAll ? null : parseInt(projectId);

    // Filter tasks_by_project and workload if a single project is selected
    const projects = isAll
        ? _rawData.tasks_by_project
        : _rawData.tasks_by_project.filter(p => p.project_id === pidInt);

    // Recalculate headline from filtered projects
    const total    = projects.reduce((s, p) => s + p.total,     0);
    const done     = projects.reduce((s, p) => s + p.completed, 0);
    const overdue  = projects.reduce((s, p) => s + p.overdue,   0);
    const rate     = total > 0 ? Math.round((done / total) * 100) : 0;

    // Active members: unique assigned_to across tasks of selected project(s)
    // We use the workload list — members with total > 0
    const workload = isAll
        ? _rawData.workload
        : _rawData.workload.filter(m => {
            // Re-compute per-project workload from tasks_by_project isn't available here,
            // so for single-project view we keep all members (they may span projects).
            // A future enhancement would be per-project member breakdown.
            return m.total > 0;
        });
    const activeMembers = workload.filter(m => m.total > 0).length;

    // Render each widget
    renderHeadline({ total, done, overdue, rate, activeMembers });
    renderHealthTable(projects);
    renderWorkload(workload);
    renderBarChart(isAll ? _rawData.weekly_completions : null);
    renderDonut(isAll ? _rawData.priority_split : buildPriorityFromProjects(projects));

    // Update subtitle
    const pName = isAll
        ? `${_rawData.projects.length} project${_rawData.projects.length !== 1 ? 's' : ''}`
        : (_rawData.projects.find(p => p.project_id === pidInt)?.name || '');
    document.getElementById('analytics-subtitle').textContent =
        `Performance overview · ${pName}`;
}

function buildPriorityFromProjects(/* filtered tasks_by_project — no per-priority breakdown */) {
    // We don't have per-priority breakdown per project in the current API shape.
    // Return null so the donut shows a "Select all to see priority breakdown" message.
    return null;
}

//  Headline 
function renderHeadline({ total, done, overdue, rate, activeMembers }) {
    document.getElementById('h-total').textContent   = total;
    document.getElementById('h-done').textContent    = done;
    document.getElementById('h-rate').textContent    = rate + '%';
    document.getElementById('h-overdue').textContent = overdue;
    document.getElementById('h-members').textContent = activeMembers;
}

// ── Bar chart ──────────────────────────────────────────────────────────
function renderBarChart(weeks) {
    const wrap = document.getElementById('bar-chart');

    if (!weeks || weeks.length === 0) {
        wrap.innerHTML = `<div style="flex:1;display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:13px">Select "All Projects" to see weekly trend</div>`;
        return;
    }

    const maxCount = Math.max(...weeks.map(w => w.count), 1);

    wrap.innerHTML = weeks.map(w => {
        const pct    = Math.round((w.count / maxCount) * 100);
        const height = Math.max(pct, 3); // minimum visible height
        return `
        <div class="bar-col">
            <div class="bar-fill" style="height:${height}%">
                <div class="bar-tooltip">${w.count} task${w.count !== 1 ? 's' : ''}</div>
            </div>
            <div class="bar-lbl">${esc(w.week)}</div>
        </div>`;
    }).join('');
}

// ── Donut chart ────────────────────────────────────────────────────────
function renderDonut(prioritySplit) {
    const wrap = document.getElementById('donut-wrap');

    if (!prioritySplit) {
        wrap.innerHTML = `<div style="color:var(--text-muted);font-size:13px;text-align:center;padding:20px 0">Select "All Projects" to see priority breakdown</div>`;
        return;
    }

    const total = prioritySplit.reduce((s, p) => s + p.count, 0);

    if (total === 0) {
        wrap.innerHTML = `<div style="color:var(--text-muted);font-size:13px;text-align:center;padding:20px 0">No tasks yet</div>`;
        return;
    }

    const colors = {
        critical: '#dc2626',
        high:     '#f97316',
        medium:   '#f59e0b',
        low:      '#94a3b8',
    };

    const R = 60; const C = 2 * Math.PI * R;
    let offset = 0;
    const paths = prioritySplit.map(s => {
        const dash = (s.count / total) * C;
        const el   = `<circle cx="80" cy="80" r="${R}" fill="none"
                        stroke="${colors[s.priority] || '#ccc'}" stroke-width="20"
                        stroke-dasharray="${dash.toFixed(2)} ${(C - dash).toFixed(2)}"
                        stroke-dashoffset="${(-offset).toFixed(2)}"
                        stroke-linecap="butt"/>`;
        offset += dash;
        return el;
    }).join('');

    const completedSplit = prioritySplit.find(p => false); // unused — donut shows priority
    const donePct = 0; // not relevant here

    wrap.innerHTML = `
        <div class="donut-svg-wrap">
            <svg class="donut-svg" viewBox="0 0 160 160">
                <circle cx="80" cy="80" r="${R}" fill="none" stroke="var(--border)" stroke-width="20"/>
                ${paths}
            </svg>
            <div class="donut-center">
                <div class="donut-center-val">${total}</div>
                <div class="donut-center-lbl">tasks</div>
            </div>
        </div>
        <div class="donut-legend">
            ${prioritySplit.map(s => `
            <div class="legend-item">
                <div class="legend-dot" style="background:${colors[s.priority] || '#ccc'}"></div>
                <span class="legend-lbl">${esc(s.priority)}</span>
                <span class="legend-val">${s.count}</span>
            </div>`).join('')}
        </div>`;
}

// ── Project health table ───────────────────────────────────────────────
function renderHealthTable(projects) {
    const body = document.getElementById('health-body');

    if (!projects.length) {
        body.innerHTML = `<tr><td colspan="6"><div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
            </svg>
            <p>No projects found</p>
        </div></td></tr>`;
        return;
    }

    body.innerHTML = projects.map(p => `
    <tr>
        <td>
            <a href="${BASE}/user/project?id=${p.project_id}"
               style="font-weight:600;color:var(--brand);text-decoration:none;font-size:13px">
                ${esc(p.name)}
            </a>
        </td>
        <td><span class="pill ${esc(p.status)}">${esc(p.status)}</span></td>
        <td>
            <div style="display:flex;align-items:center;gap:8px">
                <div class="progress-mini" style="flex:1">
                    <div class="progress-mini-fill" style="width:${p.completion_rate}%"></div>
                </div>
                <span style="font-size:12px;font-weight:600;color:var(--brand);min-width:32px">${p.completion_rate}%</span>
            </div>
        </td>
        <td style="text-align:center;font-weight:600">${p.total}</td>
        <td style="text-align:center">
            <span style="color:var(--green);font-weight:600">${p.completed}</span>
        </td>
        <td style="text-align:center">
            ${p.overdue > 0
                ? `<span style="color:var(--red);font-weight:700;background:var(--red-pale);padding:2px 8px;border-radius:20px;font-size:12px">${p.overdue}</span>`
                : `<span style="color:var(--text-muted)">—</span>`}
        </td>
    </tr>`).join('');
}

// ── Member workload ────────────────────────────────────────────────────
function renderWorkload(workload) {
    const wrap = document.getElementById('workload-list');

    if (!workload.length) {
        wrap.innerHTML = `<div style="padding:24px;text-align:center;font-size:13px;color:var(--text-muted)">No members assigned yet.</div>`;
        return;
    }

    const maxTasks = Math.max(...workload.map(m => m.total), 1);

    wrap.innerHTML = workload.map(m => {
        const initials = (m.name || '?').split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
        const barW     = Math.round((m.total / maxTasks) * 100);
        const avatar   = m.userProfile
            ? `<img src="${esc(m.userProfile)}" style="width:100%;height:100%;object-fit:cover;border-radius:50%">`
            : initials;

        return `
        <div class="member-workload-row">
            <div class="mw-avatar">${avatar}</div>
            <div class="mw-info">
                <div class="mw-name">${esc(m.name || '—')}</div>
                <div class="mw-bar-wrap">
                    <div class="mw-bar-fill" style="width:${barW}%"></div>
                </div>
            </div>
            <div class="mw-stat">
                <div class="mw-count">${m.completed}/${m.total}</div>
                <div class="mw-label">done</div>
            </div>
            ${m.overdue > 0
                ? `<span style="font-size:10px;font-weight:700;padding:2px 6px;border-radius:10px;background:var(--red-pale);color:var(--red);flex-shrink:0">${m.overdue} late</span>`
                : ''}
        </div>`;
    }).join('');
}

// ── Sidebar projects nav ───────────────────────────────────────────────
function toggleProjectsNav() {
    _navProjectsOpen = !_navProjectsOpen;
    const list    = document.getElementById('projects-nav-list');
    const chevron = document.getElementById('projects-chevron');
    const toggle  = document.getElementById('nav-projects-toggle');

    if (_navProjectsOpen) {
        list.style.maxHeight    = '400px';
        chevron.style.transform = 'rotate(180deg)';
        toggle.classList.add('active');
        if (!_navProjectsLoaded) loadProjectsNav();
    } else {
        list.style.maxHeight    = '0';
        chevron.style.transform = 'rotate(0deg)';
        toggle.classList.remove('active');
    }
}

async function loadProjectsNav() {
    try {
        // If analytics data already loaded, reuse it
        if (_rawData && _rawData.projects.length > 0) {
            _navProjectsLoaded = true;
            renderProjectsNav(_rawData.projects.map(p => ({ ...p, my_role: 'manager' })));
            return;
        }
        const res  = await fetch(BASE + '/api/user/projects', { credentials: 'same-origin' });
        const json = await res.json();
        _navProjectsLoaded = true;
        renderProjectsNav(json.success ? (json.data || []) : []);
    } catch {
        const items = document.getElementById('projects-nav-items');
        const skel  = document.getElementById('projects-nav-skeleton');
        if (items) items.innerHTML = `<div class="nav-projects-empty">Failed to load</div>`;
        if (skel)  skel.style.display = 'none';
    }
}

function renderProjectsNav(projects) {
    const skeleton = document.getElementById('projects-nav-skeleton');
    const items    = document.getElementById('projects-nav-items');
    if (!skeleton || !items) return;
    skeleton.style.display = 'none';

    if (!projects.length) {
        items.innerHTML = `<div class="nav-projects-empty">No projects yet</div>`;
        return;
    }

    items.innerHTML = projects.map(p => {
        const name = (p.name || 'Untitled').length > 22 ? p.name.slice(0, 21) + '…' : p.name;
        return `
        <a href="${BASE}/user/project?id=${p.project_id}" class="nav-project-item">
            <span class="nav-project-dot ${escNav(p.status)}"></span>
            <span style="flex:1;overflow:hidden;text-overflow:ellipsis">${escNav(name)}</span>
            <span style="font-size:9px;font-weight:700;padding:1px 5px;border-radius:8px;background:rgba(232,160,69,.2);color:var(--accent);flex-shrink:0">MGR</span>
        </a>`;
    }).join('');
}

// ── Notification badge ─────────────────────────────────────────────────
async function loadNotifBadge() {
   NotifPoll.start(BASE);
}

// ── Helpers ────────────────────────────────────────────────────────────
function esc(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}
function escNav(str) { return esc(str); }

function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    document.getElementById('toast-msg').textContent = message;
    toast.className = `toast ${type}`;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3500);
}

function handleLogout() {
    fetch(BASE + '/user/logout', { method: 'POST', credentials: 'same-origin' })
        .finally(() => { location.href = BASE + '/user/login'; });
}