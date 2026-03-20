/**
 * public/js/member-analytics.js
 * Member analytics page — requires app.js (BASE, esc, loadSidebarOrg)
 */

document.addEventListener('DOMContentLoaded', () => {
    loadSidebarOrg();
    document.getElementById('topbarDate').textContent =
        new Date().toLocaleDateString('en-US', { weekday:'long', month:'long', day:'numeric' });

    loadMemberAnalytics();
});

async function loadMemberAnalytics() {
    try {
        const res  = await fetch(`${BASE}/api/analytics/member?user_id=${MEMBER_ID}`, { credentials:'same-origin' });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);

        const { profile, completion_trend, tasks, projects } = json.data;

        renderHero(profile);
        renderKPIs(profile);
        renderCompletionRing(profile);
        renderTrendChart(completion_trend);
        renderStatusBars(profile);
        renderPriorityBars(profile);
        renderProjects(projects);
        renderTasks(tasks);

    } catch (err) {
        document.querySelector('.content').innerHTML = `
            <div style="padding:60px;text-align:center">
                <p style="color:#dc2626;font-size:14px;margin-bottom:12px">${esc(err.message)}</p>
                <a href="${BASE}/members" style="color:var(--brand);font-weight:600;font-size:13px">← Back to Members</a>
            </div>`;
    }
}

// ── Hero ──────────────────────────────────────────────────────────────
function renderHero(p) {
    // Update topbar title
    document.getElementById('topbar-member-name').textContent = p.name || 'Member Analytics';

    // Avatar
    const avatarEl = document.getElementById('hero-avatar');
    if (p.userProfile) {
        avatarEl.innerHTML = `<img src="${esc(p.userProfile)}" alt="${esc(p.name)}">`;
    } else {
        const initials = (p.name || '?').split(' ').map(w => w[0]).slice(0,2).join('').toUpperCase();
        avatarEl.textContent = initials;
    }

    // Info
    const joinedDate = new Date(p.created_at).toLocaleDateString('en-US', { month:'long', day:'numeric', year:'numeric' });
    document.getElementById('hero-info').innerHTML = `
        <div class="hero-name">${esc(p.name)}</div>
        <div class="hero-email">${esc(p.email)}</div>
        <div class="hero-meta">
            <span class="hero-chip ${esc(p.role)}">${esc(p.role)}</span>
            <span class="hero-chip">Joined ${joinedDate}</span>
            <span class="hero-chip">${p.total_tasks ?? 0} tasks assigned</span>
        </div>`;
}

// ── KPI cards ─────────────────────────────────────────────────────────
function renderKPIs(p) {
    const total   = parseInt(p.total_tasks)   || 0;
    const done    = parseInt(p.completed)     || 0;
    const active  = parseInt(p.in_progress)   || 0;
    const review  = parseInt(p.in_review)     || 0;
    const pending = parseInt(p.pending)       || 0;
    const overdue = parseInt(p.overdue)       || 0;
    const pct     = total > 0 ? Math.round((done / total) * 100) : 0;

    const cards = [
        { val: total,   lbl: 'Total Tasks',     cls: '' },
        { val: done,    lbl: 'Completed',        cls: 'done' },
        { val: active,  lbl: 'In Progress',      cls: 'progress' },
        { val: review,  lbl: 'In Review',        cls: '' },
        { val: pending, lbl: 'Pending',          cls: '' },
        { val: overdue, lbl: 'Overdue',          cls: 'overdue' },
    ];

    document.getElementById('kpi-row').innerHTML = cards.map((c, i) => `
        <div class="kpi-card ${c.cls}" style="animation:fadeUp .3s ease ${i*.05}s both">
            <div class="kpi-val">${c.val}</div>
            <div class="kpi-lbl">${c.lbl}</div>
        </div>`).join('');
}

// ── Completion ring ───────────────────────────────────────────────────
function renderCompletionRing(p) {
    const total   = parseInt(p.total_tasks) || 0;
    const done    = parseInt(p.completed)   || 0;
    const overdue = parseInt(p.overdue)     || 0;
    const pct     = total > 0 ? Math.round((done / total) * 100) : 0;

    const r = 54; // radius
    const circ = 2 * Math.PI * r;
    const offset = circ - (pct / 100) * circ;

    document.getElementById('rate-wrap').innerHTML = `
        <div class="rate-ring">
            <svg width="130" height="130" viewBox="0 0 130 130">
                <circle class="rate-ring-bg"   cx="65" cy="65" r="${r}"/>
                <circle class="rate-ring-fill"  cx="65" cy="65" r="${r}"
                    stroke-dasharray="${circ}"
                    stroke-dashoffset="${offset}"/>
            </svg>
            <div class="rate-center">
                <div class="rate-pct">${pct}%</div>
                <div class="rate-lbl">done</div>
            </div>
        </div>
        <div class="rate-stats">
            <div class="rate-stat-item">
                <div class="rate-stat-val">${done}</div>
                <div class="rate-stat-lbl">Completed</div>
            </div>
            <div class="rate-stat-item">
                <div class="rate-stat-val" style="color:${overdue > 0 ? '#dc2626' : 'inherit'}">${overdue}</div>
                <div class="rate-stat-lbl">Overdue</div>
            </div>
            <div class="rate-stat-item">
                <div class="rate-stat-val">${parseInt(p.in_progress)||0}</div>
                <div class="rate-stat-lbl">In Progress</div>
            </div>
            <div class="rate-stat-item">
                <div class="rate-stat-val">${parseInt(p.pending)||0}</div>
                <div class="rate-stat-lbl">Pending</div>
            </div>
        </div>`;
}

// ── Trend chart ───────────────────────────────────────────────────────
function renderTrendChart(data) {
    const ctx  = document.getElementById('chart-trend').getContext('2d');
    const days = last30Days();
    const map  = {};
    data.forEach(r => { map[r.day] = parseInt(r.count); });
    const vals = days.map(d => map[d] || 0);

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: days.map(d => new Date(d + 'T00:00:00').toLocaleDateString('en-US', { month:'short', day:'numeric' })),
            datasets: [{
                label: 'Completed',
                data: vals,
                backgroundColor: 'rgba(106,0,49,.75)',
                borderRadius: 4,
                borderSkipped: false,
            }],
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { maxTicksLimit: 8, font: { size: 10 } }, grid: { display: false } },
                y: { beginAtZero: true, ticks: { stepSize: 1 } },
            },
        },
    });
}

// ── Status bars ───────────────────────────────────────────────────────
function renderStatusBars(p) {
    const total = parseInt(p.total_tasks) || 0;
    const rows = [
        { key: 'completed',   val: parseInt(p.completed)   || 0, label: 'Completed' },
        { key: 'in_progress', val: parseInt(p.in_progress) || 0, label: 'In Progress' },
        { key: 'in_review',   val: parseInt(p.in_review)   || 0, label: 'In Review' },
        { key: 'pending',     val: parseInt(p.pending)      || 0, label: 'Pending' },
    ];

    document.getElementById('status-bars').innerHTML = rows.map(r => {
        const pct = total > 0 ? Math.round((r.val / total) * 100) : 0;
        return `
        <div class="bar-row">
            <div class="bar-label">${r.label}</div>
            <div class="bar-track"><div class="bar-fill fill-${r.key}" style="width:${pct}%"></div></div>
            <div class="bar-count">${r.val}</div>
        </div>`;
    }).join('');
}

// ── Priority bars ─────────────────────────────────────────────────────
function renderPriorityBars(p) {
    const total = parseInt(p.total_tasks) || 0;
    const rows = [
        { key: 'critical', val: parseInt(p.critical)  || 0 },
        { key: 'high',     val: parseInt(p.high_p)    || 0 },
        { key: 'medium',   val: parseInt(p.medium_p)  || 0 },
        { key: 'low',      val: parseInt(p.low_p)     || 0 },
    ];

    document.getElementById('priority-bars').innerHTML = rows.map(r => {
        const pct = total > 0 ? Math.round((r.val / total) * 100) : 0;
        return `
        <div class="bar-row">
            <div class="bar-label" style="text-transform:capitalize">${r.key}</div>
            <div class="bar-track"><div class="bar-fill fill-${r.key}" style="width:${pct}%"></div></div>
            <div class="bar-count">${r.val}</div>
        </div>`;
    }).join('');
}

// ── Projects ──────────────────────────────────────────────────────────
function renderProjects(projects) {
    const list = document.getElementById('projects-list');
    if (!projects.length) {
        list.innerHTML = `<div style="padding:20px;text-align:center;font-size:13px;color:var(--text-muted)">Not part of any projects yet.</div>`;
        return;
    }
    list.innerHTML = projects.map(p => {
        const done  = parseInt(p.done)  || 0;
        const total = parseInt(p.total) || 0;
        const cls   = p.status === 'completed' ? 'completed' : p.status === 'archived' ? 'archived' : 'active';
        return `
        <div class="proj-row">
            <div class="proj-dot ${cls}"></div>
            <div class="proj-info">
                <div class="proj-name">${esc(p.name)}</div>
                <div class="proj-sub">${done}/${total} tasks done</div>
            </div>
            <span class="proj-role ${esc(p.role)}">${esc(p.role)}</span>
        </div>`;
    }).join('');
}

// ── Task list ─────────────────────────────────────────────────────────
function renderTasks(tasks) {
    const list = document.getElementById('task-list');
    document.getElementById('task-count').textContent = `${tasks.length} task${tasks.length !== 1 ? 's' : ''}`;

    if (!tasks.length) {
        list.innerHTML = `<div style="padding:24px;text-align:center;font-size:13px;color:var(--text-muted)">No tasks assigned yet.</div>`;
        return;
    }

    const prioColor = { critical:'#dc2626', high:'#f97316', medium:'#f59e0b', low:'#94a3b8' };
    const today     = new Date(); today.setHours(0,0,0,0);

    list.innerHTML = tasks.map(t => {
        const isOverdue = t.due_date && new Date(t.due_date) < today && t.status !== 'completed';
        const dueLabel  = t.due_date
            ? new Date(t.due_date + 'T00:00:00').toLocaleDateString('en-US', { month:'short', day:'numeric' })
            : '—';
        return `
        <div class="task-row">
            <div class="task-priority-dot" style="background:${prioColor[t.priority]||'#ccc'}"></div>
            <div class="task-info">
                <div class="task-title">${esc(t.title)}</div>
                <div class="task-proj">${esc(t.project_name)}</div>
            </div>
            <span class="task-status-badge badge-${t.status}">${t.status.replace('_',' ')}</span>
            <div class="task-due ${isOverdue ? 'overdue' : ''}">${isOverdue ? '⚠ ' : ''}${dueLabel}</div>
        </div>`;
    }).join('');
}

// ── Helpers ───────────────────────────────────────────────────────────
function last30Days() {
    const days = [];
    for (let i = 29; i >= 0; i--) {
        const d = new Date(); d.setDate(d.getDate() - i);
        days.push(d.toISOString().split('T')[0]);
    }
    return days;
}