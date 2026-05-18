/**
 * public/js/user-notifications.js
 * Notifications page logic.
 */

let _allNotifs  = [];
let _filter     = 'all';

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('topbar-date').textContent =
        new Date().toLocaleDateString('en-US', { weekday:'long', month:'long', day:'numeric' });

    loadNotifications();

    // Start polling — when new ones arrive, reload the list
    NotifPoll.start(BASE, () => loadNotifications());
    
    toggleProjectsNav();
});

async function loadNotifications() {
    try {
        const res  = await fetch(BASE + '/api/user/notifications', { credentials:'same-origin' });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        _allNotifs = json.data || [];
        renderList();
    } catch (err) {
        document.getElementById('notif-list').innerHTML =
            `<div class="empty-state"><p style="color:#dc2626">${esc(err.message)}</p></div>`;
    }
}

function setFilter(filter, btn) {
    _filter = filter;
    document.querySelectorAll('.filter-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    renderList();
}

function renderList() {
    const list = document.getElementById('notif-list');
    const unreadCount = _allNotifs.filter(n => !n.is_read).length;

    // Subtitle
    document.getElementById('notif-subtitle').textContent =
        unreadCount > 0
            ? `${unreadCount} unread notification${unreadCount !== 1 ? 's' : ''}`
            : 'You\'re all caught up!';

    // Disable mark-all if nothing unread
    document.getElementById('btn-mark-all').disabled = unreadCount === 0;

    // Apply filter
    let filtered = _allNotifs;
    if (_filter === 'unread')   filtered = _allNotifs.filter(n => !n.is_read);
    if (_filter === 'overdue')  filtered = _allNotifs.filter(n => n.type === 'overdue');
    if (_filter === 'due_soon') filtered = _allNotifs.filter(n => n.type === 'due_soon');

    if (!filtered.length) {
        list.innerHTML = `
            <div class="empty-state">
                <div class="empty-icon">🔔</div>
                <h3>${_filter === 'all' ? 'No notifications yet' : 'Nothing here'}</h3>
                <p>${_filter === 'unread' ? 'All caught up — nothing unread.' : 'No notifications match this filter.'}</p>
            </div>`;
        return;
    }

    // Group: Today / Earlier
    const today = new Date(); today.setHours(0,0,0,0);
    const todayItems    = filtered.filter(n => new Date(n.created_at) >= today);
    const earlierItems  = filtered.filter(n => new Date(n.created_at) <  today);

    let html = '';
    if (todayItems.length) {
        html += `<div class="notif-group-label">Today</div>`;
        html += todayItems.map((n, i) => renderCard(n, i)).join('');
    }
    if (earlierItems.length) {
        html += `<div class="notif-group-label">Earlier</div>`;
        html += earlierItems.map((n, i) => renderCard(n, i + todayItems.length)).join('');
    }

    list.innerHTML = html;
}

function renderCard(n, animIdx) {
    const icons = {
        overdue:       '🚨',
        due_soon:      '⏰',
        assigned:      '✅',
        status_changed:'🔄',
    };
    const icon = icons[n.type] || '🔔';

    const timeAgo = formatTimeAgo(n.created_at);

    return `
    <div class="notif-card ${n.is_read ? '' : 'unread'}"
         style="animation-delay:${animIdx * 0.04}s"
         onclick="handleCardClick(${n.notification_id}, ${n.task_id}, ${n.project_id})">

        ${n.is_read
            ? '<div class="read-spacer"></div>'
            : '<div class="unread-dot"></div>'}

        <div class="notif-icon ${esc(n.type)}">${icon}</div>

        <div class="notif-body">
            <div class="notif-message">${esc(n.message)}</div>
            <div class="notif-meta">
                <span class="notif-project">${esc(n.project_name)}</span>
                <span class="notif-priority ${esc(n.priority)}">${esc(n.priority)}</span>
                <span class="notif-time">${timeAgo}</span>
            </div>
        </div>

        ${!n.is_read ? `
        <button class="notif-action" title="Mark as read"
                onclick="event.stopPropagation(); markRead(${n.notification_id})">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
        </button>` : ''}
    </div>`;
}

async function handleCardClick(notifId, taskId, projectId) {
    // Mark as read then navigate to the project
    await markRead(notifId, false);
    location.href = `${BASE}/user/project?id=${projectId}`;
}

async function markRead(notifId, showToast = true) {
    // Optimistic UI
    const n = _allNotifs.find(n => n.notification_id === notifId);
    if (n && !n.is_read) {
        n.is_read = true;
        renderList();
        NotifPoll.refresh(); // update badge immediately
    }

    try {
        const res  = await fetch(BASE + '/api/user/notifications/read', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ notification_id: notifId }),
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        if (showToast) showToastMsg('Marked as read');
        NotifPoll.refresh();
    } catch (err) {
        // Roll back optimistic update on failure
        if (n) n.is_read = false;
        renderList();
        if (showToast) showToastMsg(err.message, 'error');
    }
}

async function markAllRead() {
    // Optimistic
    _allNotifs.forEach(n => n.is_read = true);
    renderList();
    NotifPoll.refresh();

    try {
        const res  = await fetch(BASE + '/api/user/notifications/read-all', {
            method: 'PATCH',
            credentials: 'same-origin',
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        showToastMsg('All notifications marked as read');
        NotifPoll.refresh();
    } catch (err) {
        showToastMsg(err.message, 'error');
        await loadNotifications(); // reload to get real state
    }
}

// ── Time formatting ───────────────────────────────────────────────────
function formatTimeAgo(dateStr) {
    const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
    if (diff < 60)      return 'just now';
    if (diff < 3600)    return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400)   return Math.floor(diff / 3600) + 'h ago';
    if (diff < 604800)  return Math.floor(diff / 86400) + 'd ago';
    return new Date(dateStr).toLocaleDateString('en-US', { month:'short', day:'numeric' });
}

// ── Helpers ───────────────────────────────────────────────────────────
function esc(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}
function showToastMsg(message, type = 'success') {
    const toast = document.getElementById('toast');
    document.getElementById('toast-msg').textContent = message;
    toast.className = `toast ${type}`;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3000);
}
function handleLogout() {
    fetch(BASE + '/user/logout', { method:'POST', credentials:'same-origin' })
        .finally(() => { location.href = BASE + '/user/login'; });
}

// ── Sidebar nav (same pattern as other pages) ─────────────────────────
let _navProjectsOpen   = false;
let _navProjectsLoaded = false;

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
        const res  = await fetch(BASE + '/api/user/projects', { credentials:'same-origin' });
        const json = await res.json();
        _navProjectsLoaded = true;
        renderProjectsNav(json.success ? (json.data || []) : []);
    } catch {
        document.getElementById('projects-nav-items').innerHTML = `<div class="nav-projects-empty">Failed to load</div>`;
        document.getElementById('projects-nav-skeleton').style.display = 'none';
    }
}

function renderProjectsNav(projects) {
    document.getElementById('projects-nav-skeleton').style.display = 'none';
    const items = document.getElementById('projects-nav-items');
    if (!projects.length) { items.innerHTML = `<div class="nav-projects-empty">No projects yet</div>`; return; }
    items.innerHTML = projects.map(p => {
        const name = p.name.length > 22 ? p.name.slice(0,21) + '…' : p.name;
        return `
        <a href="${BASE}/user/project?id=${p.project_id}" class="nav-project-item">
            <span class="nav-project-dot ${esc(p.status)}"></span>
            <span style="flex:1;overflow:hidden;text-overflow:ellipsis">${esc(name)}</span>
            ${p.my_role === 'manager'
                ? `<span style="font-size:9px;font-weight:700;padding:1px 5px;border-radius:8px;background:rgba(232,160,69,.2);color:var(--accent);flex-shrink:0">MGR</span>`
                : ''}
        </a>`;
    }).join('');
}