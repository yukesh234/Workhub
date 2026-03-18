/**
 * public/js/analytics.js
 * Admin analytics + activity log page.
 * BASE is declared in app.js — do NOT redeclare it here.
 */

let logOffset     = 0;
const LOG_LIMIT   = 20;
let logTotal      = 0;
let actionTypes   = [];

// ── Boot ──────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    loadSidebarOrg();
    document.getElementById('topbarDate').textContent =
        new Date().toLocaleDateString('en-US', { weekday:'long', month:'long', day:'numeric' });

    loadAnalytics();
    loadLogs();
});

// ── Analytics ─────────────────────────────────────────────────────────
async function loadAnalytics() {
    try {
        const res  = await fetch(BASE + '/api/analytics/admin', { credentials:'same-origin' });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        const d = json.data;

        renderSummary(d.summary);
        renderTrendChart(d.task_trend);
        renderStatusChart(d.status_breakdown);
        renderPriorityChart(d.priority_breakdown);
        renderPerformers(d.top_performers);
        renderProjectProgress(d.project_progress);
    } catch (err) {
        document.getElementById('summary-cards').innerHTML =
            `<p style="color:#dc2626;font-size:13px">${esc(err.message)}</p>`;
    }
}

function renderSummary(s) {
    const cards = [
        { icon:'members',  val: s.total_members,    lbl:'Team Members',       svg:'<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>' },
        { icon:'projects', val: s.total_projects,   lbl:'Total Projects',      svg:'<path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>' },
        { icon:'projects', val: s.active_projects,  lbl:'Active Projects',     svg:'<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>' },
        { icon:'tasks',    val: s.total_tasks,       lbl:'Total Tasks',         svg:'<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>' },
        { icon:'done',     val: s.completed_tasks,  lbl:'Tasks Completed',     svg:'<polyline points="20 6 9 17 4 12"/>' },
        { icon:'overdue',  val: s.overdue_tasks,    lbl:'Overdue Tasks',       svg:'<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>' },
    ];

    document.getElementById('summary-cards').innerHTML = cards.map((c,i) => `
        <div class="a-card" style="animation:fadeUp .35s ease ${i*.05}s both">
            <div class="a-icon ${c.icon}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${c.svg}</svg>
            </div>
            <div>
                <div class="a-val">${c.val ?? 0}</div>
                <div class="a-lbl">${c.lbl}</div>
            </div>
        </div>`).join('');
}

function renderTrendChart(data) {
    const ctx   = document.getElementById('chart-trend').getContext('2d');
    const days  = last30Days();
    const map   = {};
    data.forEach(r => { map[r.day] = parseInt(r.count); });
    const vals  = days.map(d => map[d] || 0);

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: days.map(d => new Date(d).toLocaleDateString('en-US',{month:'short',day:'numeric'})),
            datasets: [{
                label: 'Tasks Created',
                data: vals,
                borderColor: '#6A0031',
                backgroundColor: 'rgba(106,0,49,.08)',
                borderWidth: 2,
                pointRadius: 2,
                fill: true,
                tension: 0.4,
            }]
        },
        options: { responsive:true, maintainAspectRatio:false, plugins:{ legend:{display:false} }, scales:{ x:{ ticks:{maxTicksLimit:8,font:{size:10}} }, y:{ beginAtZero:true, ticks:{stepSize:1} } } }
    });
}

function renderStatusChart(data) {
    const ctx    = document.getElementById('chart-status').getContext('2d');
    const labels = data.map(r => r.status.replace('_',' '));
    const vals   = data.map(r => parseInt(r.count));
    const colors = { pending:'#e8dde3', in_progress:'#ea580c', in_review:'#6A0031', completed:'#1a8a5c' };
    const bgs    = data.map(r => colors[r.status] || '#ccc');

    new Chart(ctx, {
        type: 'doughnut',
        data: { labels, datasets: [{ data: vals, backgroundColor: bgs, borderWidth: 2, borderColor:'#fff' }] },
        options: { responsive:true, maintainAspectRatio:false, plugins:{ legend:{ position:'bottom', labels:{ font:{size:11}, padding:10 } } } }
    });
}

function renderPriorityChart(data) {
    const ctx    = document.getElementById('chart-priority').getContext('2d');
    const colors = { critical:'#dc2626', high:'#f97316', medium:'#f59e0b', low:'#94a3b8' };

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(r => r.priority.charAt(0).toUpperCase()+r.priority.slice(1)),
            datasets: [{
                data: data.map(r => parseInt(r.count)),
                backgroundColor: data.map(r => colors[r.priority]||'#ccc'),
                borderRadius: 6,
            }]
        },
        options: { responsive:true, maintainAspectRatio:false, plugins:{ legend:{display:false} }, scales:{ y:{ beginAtZero:true, ticks:{stepSize:1} } } }
    });
}

function renderPerformers(data) {
    const list = document.getElementById('performers-list');
    if (!data.length) {
        list.innerHTML = `<div style="padding:20px;text-align:center;font-size:13px;color:var(--text-muted)">No task data yet.</div>`;
        return;
    }
    list.innerHTML = data.map((m,i) => {
        const initials = (m.name||'?').split(' ').map(w=>w[0]).slice(0,2).join('').toUpperCase();
        const avatar   = m.userProfile
            ? `<img src="${esc(m.userProfile)}">`
            : initials;
        const medal = ['🥇','🥈','🥉','',''][i] || '';
        return `
        <div class="perf-row">
            <div class="perf-avatar">${avatar}</div>
            <div class="perf-info">
                <div class="perf-name">${medal} ${esc(m.name)}</div>
                <div class="perf-sub">${m.done}/${m.total_tasks} tasks done</div>
            </div>
            <div class="perf-badge">${m.pct}%</div>
        </div>`;
    }).join('');
}

function renderProjectProgress(data) {
    const list = document.getElementById('project-progress-list');
    if (!data.length) {
        list.innerHTML = `<div style="padding:20px;text-align:center;font-size:13px;color:var(--text-muted)">No projects yet.</div>`;
        return;
    }
    list.innerHTML = data.slice(0,8).map(p => `
        <div class="prog-row">
            <div class="prog-name" title="${esc(p.name)}">${esc(p.name)}</div>
            <div class="prog-track"><div class="prog-fill" style="width:${p.pct}%"></div></div>
            <div class="prog-pct">${p.pct}%</div>
        </div>`).join('');
}

// ── Activity log ──────────────────────────────────────────────────────
async function loadLogs(reset = false) {
    if (reset) { logOffset = 0; document.getElementById('log-list').innerHTML = ''; }

    try {
        const action      = document.getElementById('filter-action').value;
        const entity_type = document.getElementById('filter-entity').value;
        const actor_type  = document.getElementById('filter-actor').value;

        const params = new URLSearchParams({ limit: LOG_LIMIT, offset: logOffset });
        if (action)      params.set('action',      action);
        if (entity_type) params.set('entity_type', entity_type);
        if (actor_type)  params.set('actor_type',  actor_type);

        const res  = await fetch(`${BASE}/api/analytics/activity?${params}`, { credentials:'same-origin' });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);

        logTotal = json.data.total;
        document.getElementById('log-count').textContent = `${logTotal} events`;

        // Populate action filter once
        if (!actionTypes.length) {
            actionTypes = json.data.action_types;
            const sel = document.getElementById('filter-action');
            sel.innerHTML = '<option value="">All Actions</option>' +
                actionTypes.map(a => `<option value="${a}">${formatAction(a)}</option>`).join('');
        }

        renderLogs(json.data.logs, reset);
        logOffset += LOG_LIMIT;

        const wrap = document.getElementById('load-more-wrap');
        wrap.style.display = logOffset < logTotal ? 'block' : 'none';
    } catch (err) {
        document.getElementById('log-list').innerHTML =
            `<div style="padding:20px;color:#dc2626;font-size:13px">${esc(err.message)}</div>`;
    }
}

function renderLogs(logs, reset) {
    const list = document.getElementById('log-list');
    if (reset && !logs.length) {
        list.innerHTML = `<div style="padding:24px;text-align:center;font-size:13px;color:var(--text-muted)">No activity found.</div>`;
        return;
    }

    const html = logs.map(l => {
        const time = new Date(l.created_at).toLocaleString('en-US',{ month:'short', day:'numeric', hour:'2-digit', minute:'2-digit' });
        const label = l.entity_label ? ` "<b>${esc(l.entity_label)}</b>"` : '';
        return `
        <div class="log-row">
            <div class="log-dot ${l.actor_type}"></div>
            <div class="log-body">
                <div class="log-text"><b>${esc(l.actor_name)}</b> ${formatAction(l.action)} ${l.entity_type}${label}</div>
                <div class="log-meta">${l.actor_type === 'admin' ? '👤 Admin' : '👥 Member'}</div>
            </div>
            <div class="log-time">${time}</div>
        </div>`;
    }).join('');

    list.insertAdjacentHTML('beforeend', html);
}

function reloadLogs() { loadLogs(true); }

function loadMoreLogs() { loadLogs(false); }

// ── Helpers ───────────────────────────────────────────────────────────
function formatAction(a) {
    return a.replace(/_/g,' ');
}

function last30Days() {
    const days = [];
    for (let i = 29; i >= 0; i--) {
        const d = new Date(); d.setDate(d.getDate() - i);
        days.push(d.toISOString().split('T')[0]);
    }
    return days;
}

function esc(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}