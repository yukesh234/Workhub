/**
 * public/js/user-dashboard.js
 * Member / Manager dashboard. Role is read from window.WH_ROLE.
 */

let allTasks       = [];
let allProjects    = [];
let activeFilter   = 'all';
let activeProjectFilter = 'all'; // manager only
let popoverTaskId  = null;
let popoverProjectId = null;

// ── Boot ──────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('topbar-date').textContent =
        new Date().toLocaleDateString('en-US', { weekday:'long', month:'long', day:'numeric' });

    document.getElementById('tf-due')?.setAttribute('min', new Date().toISOString().split('T')[0]);

    loadMyProjects();
    loadMyTasks();

    // Task filter tabs
    document.querySelectorAll('.task-filter-tab').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.task-filter-tab').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            activeFilter = btn.dataset.filter;
            renderTasks();
        });
    });

    // Close status popover on outside click
    document.addEventListener('click', e => {
        if (!e.target.closest('#status-popover') && !e.target.closest('.task-check')) {
            closeStatusPopover();
        }
    });
});

// ── Load my projects ──────────────────────────────────────────────────
async function loadMyProjects() {
    try {
        const res  = await fetch(BASE + '/api/user/projects', { credentials: 'same-origin' });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        allProjects = json.data || [];
        renderProjects();
        populateProjectDropdowns();
        document.getElementById('stat-projects').textContent = allProjects.length;
    } catch (err) {
        document.getElementById('projects-list').innerHTML =
            `<div style="padding:20px;font-size:13px;color:var(--text-muted);text-align:center">${esc(err.message)}</div>`;
    }
}

function renderProjects() {
    const list = document.getElementById('projects-list');
    if (allProjects.length === 0) {
        list.innerHTML = `<div style="padding:24px;text-align:center;font-size:13px;color:var(--text-muted)">You're not in any projects yet.</div>`;
        return;
    }

    list.innerHTML = allProjects.map(p => {
        const taskCount = allTasks.filter(t => t.project_id === p.project_id).length;
        return `
        <div class="project-item"
             onclick="location.href = BASE + '/user/project?id=${p.project_id}'">
            <div class="project-dot ${esc(p.status)}"></div>
            <div>
                <div class="project-item-name">${esc(p.name)}</div>
                <div class="project-item-tasks">${taskCount} task${taskCount !== 1 ? 's' : ''}</div>
            </div>
            <span class="project-item-role ${esc(p.my_role)}">${esc(p.my_role)}</span>
        </div>`;
    }).join('');
}

function populateProjectDropdowns() {
    // Manager project filter select
    const filterSel = document.getElementById('project-filter-select');
    if (filterSel) {
        filterSel.innerHTML = '<option value="all">All Projects</option>' +
            allProjects.map(p => `<option value="${p.project_id}">${esc(p.name)}</option>`).join('');
    }

    // Task form project select
    const tfProject = document.getElementById('tf-project');
    if (tfProject) {
        tfProject.innerHTML = '<option value="">— Select project —</option>' +
            allProjects.map(p => `<option value="${p.project_id}">${esc(p.name)}</option>`).join('');
    }
}

// ── Load tasks ─────────────────────────────────────────────────────────
// Manager  → GET /api/user/tasks (all tasks across their projects)
// Member   → GET /api/user/tasks (only tasks assigned to them)
// The API handles the difference based on session role
async function loadMyTasks() {
    try {
        const res  = await fetch(BASE + '/api/user/tasks', { credentials: 'same-origin' });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        allTasks = json.data || [];
        renderTasks();
        renderStats();
        // Re-render projects to update task counts
        if (allProjects.length > 0) renderProjects();
    } catch (err) {
        document.getElementById('tasks-list').innerHTML =
            `<div class="panel-empty"><p>${esc(err.message || 'Failed to load tasks.')}</p></div>`;
    }
}

// ── Render tasks ───────────────────────────────────────────────────────
function renderTasks() {
    const list = document.getElementById('tasks-list');
    const now  = new Date(); now.setHours(0,0,0,0);

    let filtered = allTasks.filter(t =>
        activeFilter === 'all' || t.status === activeFilter
    );

    // Manager: also filter by project if one is selected
    if (IS_MANAGER && activeProjectFilter !== 'all') {
        filtered = filtered.filter(t => t.project_id === parseInt(activeProjectFilter));
    }

    if (filtered.length === 0) {
        list.innerHTML = `
            <div class="panel-empty">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 11l3 3L22 4"/>
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                </svg>
                <p>${activeFilter === 'all' ? 'No tasks yet.' : `No ${activeFilter.replace('_',' ')} tasks.`}</p>
            </div>`;
        return;
    }

    list.innerHTML = filtered.map(t => {
        const isDone    = t.status === 'completed';
        const dueDate   = t.due_date ? new Date(t.due_date) : null;
        const isOverdue = dueDate && dueDate < now && !isDone;
        const project   = allProjects.find(p => p.project_id === t.project_id);

        // Manager sees assigned_user_name; member sees their own tasks so we show project instead
        const rightLabel = IS_MANAGER && t.assigned_user_name
            ? `<span style="font-size:11px;color:var(--text-muted)">${esc(t.assigned_user_name)}</span>`
            : '';

        // Only the assigned user can change status
        const isAssigned = t.assigned_to === WH_USER_ID;

        return `
        <div class="task-row" onclick="openTaskPanel(${t.task_id})">
            <div class="task-check ${isDone ? 'done' : ''} ${isAssigned ? 'clickable' : 'locked'}"
                 title="${isAssigned ? 'Update status' : 'Only the assigned member can update status'}"
                 ${isAssigned ? `onclick="event.stopPropagation(); openStatusPopover(event, ${t.task_id}, ${t.project_id})"` : 'onclick="event.stopPropagation()"'}
                 style="${isAssigned ? 'cursor:pointer' : 'cursor:not-allowed;opacity:.45'}"></div>

            <div class="task-body">
                <div class="task-title ${isDone ? 'done' : ''}">${esc(t.title)}</div>
                <div class="task-meta">
                    ${project ? `<span class="task-project-chip">${esc(project.name)}</span>` : ''}
                    <div class="priority-dot ${esc(t.priority)}"></div>
                    ${t.due_date ? `<span class="task-due ${isOverdue ? 'overdue' : ''}">${isOverdue ? '⚠ ' : ''}${formatDateShort(t.due_date)}</span>` : ''}
                    ${rightLabel}
                </div>
            </div>

            <span class="status-badge ${esc(t.status)}">${esc(t.status.replace('_',' '))}</span>

            ${IS_MANAGER ? `
            <div class="task-actions" onclick="event.stopPropagation()">
                <button class="btn-task-sm" title="Edit" onclick="openEditTask(${t.task_id})">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                </button>
                <button class="btn-task-sm danger" title="Delete" onclick="deleteTask(${t.task_id}, ${t.project_id})">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"/>
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                        <path d="M10 11v6"/><path d="M14 11v6"/>
                    </svg>
                </button>
            </div>` : ''}
        </div>`;
    }).join('');
}

// ── Stats ──────────────────────────────────────────────────────────────
function renderStats() {
    const now     = new Date(); now.setHours(0,0,0,0);
    const myTasks = IS_MANAGER ? allTasks : allTasks.filter(t => t.assigned_to === WH_USER_ID);

    document.getElementById('stat-total').textContent   = myTasks.length;
    document.getElementById('stat-done').textContent    = myTasks.filter(t => t.status === 'completed').length;
    document.getElementById('stat-pending').textContent = myTasks.filter(t => t.status === 'in_progress').length;
    document.getElementById('stat-overdue').textContent = myTasks.filter(t =>
        t.due_date && new Date(t.due_date) < now && t.status !== 'completed'
    ).length;
}

// ── Status popover ─────────────────────────────────────────────────────
function openStatusPopover(event, taskId, projectId) {
    event.stopPropagation();
    popoverTaskId    = taskId;
    popoverProjectId = projectId;

    const popover = document.getElementById('status-popover');
    const rect    = event.currentTarget.getBoundingClientRect();
    popover.style.top  = (rect.bottom + window.scrollY + 6) + 'px';
    popover.style.left = (rect.left + window.scrollX) + 'px';
    popover.classList.add('open');
}

function closeStatusPopover() {
    document.getElementById('status-popover').classList.remove('open');
    popoverTaskId    = null;
    popoverProjectId = null;
}

async function setStatus(status) {
    if (!popoverTaskId) return;

    // ✅ Capture BEFORE closing (closeStatusPopover nulls them out)
    const taskId    = popoverTaskId;
    const projectId = popoverProjectId;
    closeStatusPopover();

    try {
        const res  = await fetch(BASE + '/api/user/tasks/status', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ task_id: taskId, project_id: projectId, status }),
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);

        // Update local state
        const t = allTasks.find(t => t.task_id === taskId);
        if (t) t.status = status;

        renderTasks();
        renderStats();
        showToast(`Marked as "${status.replace('_',' ')}"`);
    } catch (err) { showToast(err.message, 'error'); }
}

// ── Manager: filter by project ─────────────────────────────────────────
function filterTasksByProject(projectId) {
    activeProjectFilter = projectId;
    renderTasks();
}

// ── Manager: load members for selected project ─────────────────────────
async function loadProjectMembers(projectId) {
    const sel = document.getElementById('tf-assignee');
    sel.innerHTML = '<option value="">Loading…</option>';
    if (!projectId) { sel.innerHTML = '<option value="">— Select member —</option>'; return; }

    try {
        const res  = await fetch(`${BASE}/api/user/project/members?project_id=${projectId}`, { credentials: 'same-origin' });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        sel.innerHTML = '<option value="">— Select member —</option>' +
            (json.data || []).map(m => `<option value="${m.user_id}">${esc(m.name || m.email)}</option>`).join('');
    } catch { sel.innerHTML = '<option value="">Failed to load</option>'; }
}

// ── Manager: create task ───────────────────────────────────────────────
function openCreateTask() {
    document.getElementById('task-modal-title').textContent = 'New Task';
    document.getElementById('task-modal-sub').textContent   = 'Create a task for your project.';
    document.getElementById('task-submit-btn').querySelector('.btn-text').textContent = 'Create Task';
    document.getElementById('task-form').reset();
    document.getElementById('task-error').style.display = 'none';
    document.getElementById('tf-task-id').value = '';
    document.getElementById('tf-due').min = new Date().toISOString().split('T')[0];
    openModal('modal-task');
}

function openEditTask(taskId) {
    const task = allTasks.find(t => t.task_id === taskId);
    if (!task) return;

    document.getElementById('task-modal-title').textContent = 'Edit Task';
    document.getElementById('task-modal-sub').textContent   = 'Update the task details.';
    document.getElementById('task-submit-btn').querySelector('.btn-text').textContent = 'Save Changes';
    document.getElementById('task-error').style.display = 'none';
    document.getElementById('tf-task-id').value   = task.task_id;
    document.getElementById('tf-project').value   = task.project_id;
    document.getElementById('tf-title').value     = task.title;
    document.getElementById('tf-desc').value      = task.description || '';
    document.getElementById('tf-due').value       = task.due_date ? task.due_date.split(' ')[0] : '';
    document.getElementById('tf-priority').value  = task.priority;
    document.getElementById('tf-status').value    = task.status;
    document.getElementById('tf-due').min         = '';

    // Load members for this project, then set assigned value
    loadProjectMembers(task.project_id).then(() => {
        document.getElementById('tf-assignee').value = task.assigned_to || '';
    });

    openModal('modal-task');
}

document.getElementById('task-form')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn    = document.getElementById('task-submit-btn');
    const errEl  = document.getElementById('task-error');
    const taskId = document.getElementById('tf-task-id').value;
    const isEdit = !!taskId;

    errEl.style.display = 'none';
    btn.classList.add('loading');

    const payload = {
        project_id:  parseInt(document.getElementById('tf-project').value),
        title:       document.getElementById('tf-title').value.trim(),
        description: document.getElementById('tf-desc').value.trim() || null,
        assigned_to: parseInt(document.getElementById('tf-assignee').value) || null,
        due_date:    document.getElementById('tf-due').value || null,
        priority:    document.getElementById('tf-priority').value,
        status:      document.getElementById('tf-status').value,
    };
    if (isEdit) payload.task_id = parseInt(taskId);

    try {
        const res  = await fetch(BASE + '/api/user/tasks', {
            method: isEdit ? 'PUT' : 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify(payload),
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);

        closeModal('modal-task');
        await loadMyTasks();
        showToast(isEdit ? 'Task updated!' : 'Task created!');
    } catch (err) {
        errEl.textContent   = err.message;
        errEl.style.display = 'block';
    } finally { btn.classList.remove('loading'); }
});

async function deleteTask(taskId, projectId) {
    if (!confirm('Delete this task? This cannot be undone.')) return;
    try {
        const res  = await fetch(BASE + '/api/user/tasks', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ task_id: taskId, project_id: projectId }),
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        await loadMyTasks();
        showToast('Task deleted.', 'error');
    } catch (err) { showToast(err.message, 'error'); }
}

// ── Helpers (inline since we don't load app.js here) ──────────────────
function esc(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

function formatDateShort(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('en-US', { month:'short', day:'numeric' });
}

function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    document.getElementById('toast-msg').textContent = message;
    toast.className = `toast ${type}`;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3000);
}

function openModal(id) {
    document.getElementById(id)?.classList.add('open');
}

function closeModal(id) {
    document.getElementById(id)?.classList.remove('open');
}

function closeModalOutside(e, id) {
    if (e.target.id === id) closeModal(id);
}

function handleLogout() {
    if(!confirm("Are you sure you want to logout")) return;
    fetch(BASE + '/user/logout', { method: 'POST', credentials: 'same-origin' })
        .finally(() => { location.href = BASE + '/user/login'; });
}

// ══════════════════════════════════════════════════════════════════════
// ── Task side panel ───────────────────────────────────────────────────
// ══════════════════════════════════════════════════════════════════════

let currentTask = null;

async function openTaskPanel(taskId) {
    const task = allTasks.find(t => t.task_id === taskId);
    if (!task) return;
    currentTask = task;

    const now     = new Date(); now.setHours(0,0,0,0);
    const dueDate = task.due_date ? new Date(task.due_date) : null;
    const overdue = dueDate && dueDate < now && task.status !== 'completed';

    document.getElementById('udp-title').textContent = task.title;

    // Status display
    const statusColors = {
        pending:     { bg:'var(--surface-2)',  color:'var(--text-muted)' },
        in_progress: { bg:'#fff7ed',            color:'#ea580c' },
        in_review:   { bg:'var(--brand-pale2)', color:'var(--brand)' },
        completed:   { bg:'#e6f9f1',            color:'#1a8a5c' },
    };
    const sc = statusColors[task.status] || statusColors.pending;
    const isAssigned = task.assigned_to === WH_USER_ID;

    document.getElementById('udp-status-display').innerHTML = `
        <span style="display:inline-flex;align-items:center;gap:6px;padding:5px 14px;border-radius:20px;font-size:13px;font-weight:600;background:${sc.bg};color:${sc.color}">
            ${esc(task.status.replace('_',' '))}
        </span>
        ${isAssigned
            ? `<button onclick="openStatusPopoverFromPanel(event, ${task.task_id}, ${task.project_id})"
                style="margin-left:8px;font-size:12px;color:var(--brand);background:none;border:none;cursor:pointer;font-weight:600;padding:0">
                Change ↓</button>`
            : `<span style="font-size:12px;color:var(--text-muted);margin-left:8px">Updated by assigned member</span>`
        }`;

    document.getElementById('udp-desc').textContent     = task.description || 'No description.';
    document.getElementById('udp-desc').className       = task.description ? '' : 'udp-empty';
    document.getElementById('udp-priority').textContent = task.priority.charAt(0).toUpperCase() + task.priority.slice(1);
    document.getElementById('udp-due').textContent      = task.due_date ? (formatDateShort(task.due_date) + (overdue ? ' ⚠' : '')) : '—';
    document.getElementById('udp-due').style.color      = overdue ? '#dc2626' : '';
    document.getElementById('udp-assignee').textContent = task.assigned_user_name || '—';
    document.getElementById('udp-project').textContent  = allProjects.find(p => p.project_id === task.project_id)?.name || '—';

    document.getElementById('task-panel-backdrop').classList.add('open');

    loadPanelComments(task.task_id, task.project_id);
    loadPanelAttachments(task.task_id, task.project_id);
}

function closeTaskPanel(e) {
    if (e && e.target !== document.getElementById('task-panel-backdrop')) return;
    document.getElementById('task-panel-backdrop').classList.remove('open');
    currentTask = null;
}

function openStatusPopoverFromPanel(event, taskId, projectId) {
    event.stopPropagation();
    // Close panel briefly so popover is visible, then re-use existing popover
    popoverTaskId    = taskId;
    popoverProjectId = projectId;
    const popover = document.getElementById('status-popover');
    const rect    = event.currentTarget.getBoundingClientRect();
    popover.style.top  = (rect.bottom + window.scrollY + 6) + 'px';
    popover.style.left = (rect.left  + window.scrollX) + 'px';
    popover.classList.add('open');
}

// ── Comments ──────────────────────────────────────────────────────────
async function loadPanelComments(taskId, projectId) {
    const wrap = document.getElementById('udp-comments-list');
    wrap.innerHTML = `<div style="padding:8px 0;color:var(--text-muted);font-size:13px">Loading…</div>`;
    try {
        const res  = await fetch(`${BASE}/api/tasks/comments?task_id=${taskId}&project_id=${projectId}`, { credentials:'same-origin' });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        renderPanelComments(json.data);
    } catch { wrap.innerHTML = `<div style="color:#dc2626;font-size:13px">Failed to load comments.</div>`; }
}

function renderPanelComments(comments) {
    const wrap = document.getElementById('udp-comments-list');
    if (!comments.length) {
        wrap.innerHTML = `<div style="padding:8px 0;color:var(--text-muted);font-size:13px;font-style:italic">No comments yet. Be the first!</div>`;
        return;
    }
    wrap.innerHTML = comments.map(c => {
        const initial = (c.author_name || '?').split(/[\s@]/)[0].charAt(0).toUpperCase();
        const avatar  = c.author_avatar
            ? `<img src="${esc(c.author_avatar)}" style="width:28px;height:28px;border-radius:50%;object-fit:cover;flex-shrink:0">`
            : `<div style="width:28px;height:28px;border-radius:50%;background:var(--brand);color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0">${initial}</div>`;
        return `
        <div class="udp-comment" id="udp-comment-${c.comment_id}">
            <div style="display:flex;align-items:center;gap:7px;margin-bottom:5px">
                ${avatar}
                <span style="font-size:13px;font-weight:600;color:var(--text-primary);flex:1">${esc(c.author_name || '—')}</span>
                <span style="font-size:11px;color:var(--text-muted)">${formatDateShort(c.created_at)}</span>
                <button onclick="deletePanelComment(${c.comment_id})"
                    style="background:none;border:none;cursor:pointer;color:var(--text-muted);padding:2px;border-radius:4px;opacity:0;transition:.2s"
                    class="udp-del-btn" title="Delete">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            <div style="font-size:13.5px;color:var(--text-secondary);line-height:1.6;white-space:pre-wrap;padding-left:35px">${esc(c.body)}</div>
        </div>`;
    }).join('');

    // Show delete btn on hover
    wrap.querySelectorAll('.udp-comment').forEach(el => {
        el.addEventListener('mouseenter', () => el.querySelector('.udp-del-btn').style.opacity = '1');
        el.addEventListener('mouseleave', () => el.querySelector('.udp-del-btn').style.opacity = '0');
    });
}

async function postPanelComment() {
    if (!currentTask) return;
    const input = document.getElementById('udp-comment-input');
    const body  = input.value.trim();
    if (!body) return;

    const btn = document.getElementById('udp-comment-submit');
    btn.disabled = true;

    try {
        const res  = await fetch(BASE + '/api/tasks/comments', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ task_id: currentTask.task_id, project_id: currentTask.project_id, body }),
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        input.value = '';
        renderPanelComments(json.data);
    } catch (err) { showToast(err.message, 'error'); }
    finally { btn.disabled = false; }
}

async function deletePanelComment(commentId) {
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
        const el = document.getElementById(`udp-comment-${commentId}`);
        if (el) { el.style.opacity='0'; el.style.transition='.2s'; setTimeout(() => el.remove(), 200); }
        showToast('Comment deleted.');
    } catch (err) { showToast(err.message, 'error'); }
}

document.getElementById('udp-comment-input')?.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); postPanelComment(); }
});

// ── Attachments ───────────────────────────────────────────────────────
async function loadPanelAttachments(taskId, projectId) {
    const wrap = document.getElementById('udp-attachments-list');
    wrap.innerHTML = `<div style="padding:8px 0;color:var(--text-muted);font-size:13px">Loading…</div>`;
    try {
        const res  = await fetch(`${BASE}/api/tasks/attachments?task_id=${taskId}&project_id=${projectId}`, { credentials:'same-origin' });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        renderPanelAttachments(json.data);
    } catch { wrap.innerHTML = `<div style="color:#dc2626;font-size:13px">Failed to load.</div>`; }
}

function renderPanelAttachments(attachments) {
    const wrap = document.getElementById('udp-attachments-list');
    if (!attachments.length) {
        wrap.innerHTML = `<div style="padding:4px 0;color:var(--text-muted);font-size:13px;font-style:italic">No files attached yet.</div>`;
        return;
    }
    const icons = { 'application/pdf':'📄','text/plain':'📝','text/csv':'📊','application/msword':'📝','application/vnd.openxmlformats-officedocument.wordprocessingml.document':'📝','application/vnd.ms-excel':'📊','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':'📊' };
    wrap.innerHTML = attachments.map(a => {
        const isImage = a.file_type?.startsWith('image/');
        const icon    = isImage ? '🖼️' : (icons[a.file_type] || '📎');
        const sizeStr = a.file_size > 1024*1024 ? (a.file_size/(1024*1024)).toFixed(1)+' MB' : (a.file_size/1024).toFixed(0)+' KB';
        return `
        <div class="udp-attachment" id="udp-att-${a.attachment_id}">
            ${isImage
                ? `<img src="${esc(a.file_url)}" style="width:44px;height:44px;border-radius:6px;object-fit:cover;cursor:pointer;border:1px solid var(--border)" onclick="window.open('${esc(a.file_url)}','_blank')">`
                : `<div style="width:44px;height:44px;border-radius:6px;background:var(--surface-2);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0">${icon}</div>`}
            <div style="flex:1;min-width:0">
                <a href="${esc(a.file_url)}" target="_blank" style="font-size:13px;font-weight:500;color:var(--brand);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block;text-decoration:none">${esc(a.file_name)}</a>
                <div style="font-size:11px;color:var(--text-muted);margin-top:2px">${sizeStr} · ${formatDateShort(a.created_at)}</div>
            </div>
            <button onclick="deletePanelAttachment(${a.attachment_id})"
                style="background:none;border:none;cursor:pointer;color:var(--text-muted);padding:4px;border-radius:4px;flex-shrink:0;transition:.2s"
                onmouseenter="this.style.color='#dc2626'" onmouseleave="this.style.color='var(--text-muted)'"
                title="Remove">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>`;
    }).join('');
}

async function uploadPanelAttachment(input) {
    if (!input.files.length || !currentTask) return;
    const formData = new FormData();
    formData.append('file',       input.files[0]);
    formData.append('task_id',    currentTask.task_id);
    formData.append('project_id', currentTask.project_id);

    const lbl = document.getElementById('udp-upload-label');
    lbl.textContent = 'Uploading…';

    try {
        const res  = await fetch(BASE + '/api/tasks/attachments', { method:'POST', credentials:'same-origin', body: formData });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        renderPanelAttachments(json.data);
        showToast('File uploaded!');
    } catch (err) { showToast(err.message, 'error'); }
    finally { lbl.textContent = 'Attach file'; input.value = ''; }
}

async function deletePanelAttachment(attachmentId) {
    if (!confirm('Remove this attachment?')) return;
    try {
        const res  = await fetch(BASE + '/api/tasks/attachments', {
            method:'DELETE', headers:{'Content-Type':'application/json'},
            credentials:'same-origin',
            body: JSON.stringify({ attachment_id: attachmentId }),
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        const el = document.getElementById(`udp-att-${attachmentId}`);
        if (el) { el.style.opacity='0'; el.style.transition='.2s'; setTimeout(() => el.remove(), 200); }
        showToast('Removed.');
    } catch (err) { showToast(err.message, 'error'); }
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        document.getElementById('task-panel-backdrop')?.classList.remove('open');
        currentTask = null;
    }
});