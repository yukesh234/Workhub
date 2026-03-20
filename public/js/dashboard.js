/**
 * public/js/dashboard.js
 * Drives the Admin Dashboard page.
 * Requires app.js (BASE, esc, setSidebarOrg, showToast, formatDateShort, openModal, closeModal, previewLogo)
 */

// ── State helpers ─────────────────────────────────────────────────────
function showState(name) {
    ['loading', 'no-org', 'error'].forEach(s => {
        const el = document.getElementById(`main-${s}`);
        if (el) el.classList.remove('active');
    });

    const hasOrg = document.getElementById('main-has-org');
    if (hasOrg) hasOrg.style.display = 'none';

    if (name === 'has-org') {
        if (hasOrg) hasOrg.style.display = 'block';
    } else {
        const el = document.getElementById(`main-${name}`);
        if (el) el.classList.add('active');
    }
}

// ── Render org profile card ───────────────────────────────────────────
function renderOrgCard(org) {
    const sub = document.getElementById('org-welcome-sub');
    if (sub) sub.textContent = `Here's your overview for ${org.name}.`;

    // Logo
    const slot = document.getElementById('org-logo-slot');
    if (slot) {
        slot.innerHTML = org.organization_logo
            ? `<img class="org-card-logo" src="${esc(org.organization_logo)}" alt="${esc(org.name)}">`
            : `<div class="org-card-logo-fallback">${esc(org.name.charAt(0).toUpperCase())}</div>`;
    }

    setText('org-card-name',   org.name);
    setText('org-card-slogan', org.slogan || '');
    setText('meta-since', formatDateShort(org.created_at));
}

// ── Fetch org + analytics together ───────────────────────────────────
async function fetchOrg() {
    showState('loading');
    setSidebarSkeleton();

    try {
        const res  = await fetch(BASE + '/api/organization', { credentials: 'same-origin' });
        const json = await res.json();

        if (!json.success || !json.data) {
            setSidebarNoOrg();
            showState('no-org');
            return;
        }

        const org = json.data;
        setSidebarOrg(org);
        renderOrgCard(org);
        showState('has-org');

        // Load stats and panels in parallel — don't block the page showing
        loadStats();
        loadRecentProjects();
        loadRecentActivity();

    } catch (err) {
        setText('error-msg', err.message || 'Failed to connect to the server.');
        showState('error');
        setSidebarNoOrg();
    }
}

// ── Load KPI stats from analytics API ────────────────────────────────
async function loadStats() {
    try {
        const res  = await fetch(BASE + '/api/analytics/admin', { credentials: 'same-origin' });
        const json = await res.json();
        if (!json.success) return;

        const s = json.data.summary;

        // Fill org card meta counts
        setText('meta-members',  s.total_members  ?? '0');
        setText('meta-projects', s.total_projects ?? '0');
        setText('meta-tasks',    s.total_tasks    ?? '0');

        // Render KPI cards
        const cards = [
            {
                cls: 'members',
                val: s.total_members ?? 0,
                lbl: 'Team Members',
                sub: null,
                svg: '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
            },
            {
                cls: 'active',
                val: s.active_projects ?? 0,
                lbl: 'Active Projects',
                sub: `${s.total_projects ?? 0} total`,
                svg: '<path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>',
            },
            {
                cls: 'tasks',
                val: s.total_tasks ?? 0,
                lbl: 'Total Tasks',
                sub: null,
                svg: '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
            },
            {
                cls: 'completed',
                val: s.completed_tasks ?? 0,
                lbl: 'Tasks Completed',
                sub: s.total_tasks > 0
                    ? `${Math.round((s.completed_tasks / s.total_tasks) * 100)}% completion rate`
                    : null,
                svg: '<polyline points="20 6 9 17 4 12"/>',
            },
            {
                cls: 'overdue',
                val: s.overdue_tasks ?? 0,
                lbl: 'Overdue Tasks',
                sub: s.overdue_tasks > 0 ? 'Need attention' : 'All on track ✓',
                svg: '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
            },
            {
                cls: 'projects',
                val: s.completed_projects ?? 0,
                lbl: 'Completed Projects',
                sub: null,
                svg: '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
            },
        ];

        document.getElementById('stat-cards').innerHTML = cards.map((c, i) => `
            <div class="stat-card" style="animation:fadeUp .3s ease ${i * 0.05}s both">
                <div class="stat-icon ${c.cls}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">${c.svg}</svg>
                </div>
                <div>
                    <div class="stat-value">${c.val}</div>
                    <div class="stat-label">${c.lbl}</div>
                    ${c.sub ? `<div class="stat-sub">${esc(c.sub)}</div>` : ''}
                </div>
            </div>`).join('');

    } catch (err) {
        // Stats failing silently is fine — page is still usable
        console.warn('Stats load failed:', err.message);
    }
}

// ── Recent projects ───────────────────────────────────────────────────
async function loadRecentProjects() {
    try {
        const res  = await fetch(BASE + '/api/projects', { credentials: 'same-origin' });
        const json = await res.json();
        if (!json.success) return;

        const list = document.getElementById('recent-projects');
        const projects = (json.data || []).slice(0, 5);

        if (!projects.length) {
            list.innerHTML = `<div style="padding:20px;text-align:center;font-size:13px;color:var(--text-muted)">
                No projects yet. <a href="${BASE}/projects" style="color:var(--brand);font-weight:600">Create one →</a>
            </div>`;
            return;
        }

        // For each project try to get task counts from analytics
        let progressMap = {};
        try {
            const ar  = await fetch(BASE + '/api/analytics/admin', { credentials: 'same-origin' });
            const aj  = await ar.json();
            if (aj.success) {
                (aj.data.project_progress || []).forEach(p => {
                    progressMap[p.project_id] = p;
                });
            }
        } catch {}

        list.innerHTML = projects.map(p => {
            const prog  = progressMap[p.project_id];
            const pct   = prog ? prog.pct   : 0;
            const total = prog ? prog.total : 0;
            const done  = prog ? prog.done  : 0;
            const statusCls = p.status === 'completed' ? 'completed' : p.status === 'archived' ? 'archived' : 'active';

            return `
            <div class="proj-row" onclick="window.location='${BASE}/project-detail?id=${p.project_id}'" style="cursor:pointer">
                <div class="proj-dot ${statusCls}"></div>
                <div class="proj-info">
                    <div class="proj-name">${esc(p.name)}</div>
                    <div class="proj-sub">${done}/${total} tasks · ${p.status}</div>
                </div>
                <div class="proj-bar-wrap">
                    <div class="proj-bar-fill" style="width:${pct}%"></div>
                </div>
                <div class="proj-pct">${pct}%</div>
            </div>`;
        }).join('');

    } catch (err) {
        console.warn('Recent projects failed:', err.message);
    }
}

// ── Recent activity ───────────────────────────────────────────────────
async function loadRecentActivity() {
    try {
        const res  = await fetch(`${BASE}/api/analytics/activity?limit=7&offset=0`, { credentials: 'same-origin' });
        const json = await res.json();
        if (!json.success) return;

        const list = document.getElementById('recent-activity');
        const logs = json.data.logs || [];

        if (!logs.length) {
            list.innerHTML = `<div style="padding:20px;text-align:center;font-size:13px;color:var(--text-muted)">
                No activity yet. Start by creating a project.
            </div>`;
            return;
        }

        list.innerHTML = logs.map(l => {
            const time  = timeAgo(l.created_at);
            const label = l.entity_label ? ` <b style="color:var(--text-primary)">"${esc(l.entity_label)}"</b>` : '';
            const action = l.action.replace(/_/g, ' ');
            return `
            <div class="act-row">
                <div class="act-dot ${l.actor_type}"></div>
                <div class="act-body">
                    <div class="act-text"><b>${esc(l.actor_name)}</b> ${action}${label}</div>
                </div>
                <div class="act-time">${time}</div>
            </div>`;
        }).join('');

    } catch (err) {
        console.warn('Activity failed:', err.message);
    }
}

// ── Create org form ───────────────────────────────────────────────────
document.getElementById('create-org-form')?.addEventListener('submit', async function (e) {
    e.preventDefault();

    const btn   = document.getElementById('submit-btn');
    const errEl = document.getElementById('form-error');
    errEl.style.display = 'none';
    btn.classList.add('loading');

    try {
        const res  = await fetch(BASE + '/organization/create', {
            method: 'POST',
            body: new FormData(this),
            credentials: 'same-origin',
        });
        const json = await res.json();

        if (json.success && json.data) {
            closeModal('modalBackdrop');
            this.reset();

            const preview = document.getElementById('upload-preview');
            const icon    = document.getElementById('upload-icon');
            if (preview) { preview.src = ''; preview.style.display = 'none'; }
            if (icon)    icon.style.display = 'block';

            setSidebarOrg(json.data);
            renderOrgCard(json.data);
            showState('has-org');
            showToast('Organization created successfully!');

            // Load stats after org creation
            loadStats();
            loadRecentProjects();
            loadRecentActivity();
        } else {
            throw new Error(json.message || 'Failed to create organization.');
        }
    } catch (err) {
        errEl.textContent   = err.message;
        errEl.style.display = 'block';
    } finally {
        btn.classList.remove('loading');
    }
});

// ── Helpers ───────────────────────────────────────────────────────────
function setText(id, val) {
    const el = document.getElementById(id);
    if (el) el.textContent = val;
}

function timeAgo(dateStr) {
    const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
    if (diff < 60)   return 'just now';
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400)return `${Math.floor(diff / 3600)}h ago`;
    return `${Math.floor(diff / 86400)}d ago`;
}

// ── Boot ─────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', fetchOrg);