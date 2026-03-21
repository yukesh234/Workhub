/**
 * public/js/user-tasks.js
 * Full tasks page — search, filter, sort, paginate.
 * Shares the same API as user-dashboard.js but renders ALL tasks.
 */

let allTasks     = [];
let allProjects  = [];
let currentPage  = 1;
const PAGE_SIZE  = 15;

let popoverTaskId    = null;
let popoverProjectId = null;

// ── Boot ──────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('topbar-date').textContent =
        new Date().toLocaleDateString('en-US', { weekday:'long', month:'long', day:'numeric' });

    document.getElementById('tf-due')?.setAttribute('min', new Date().toISOString().split('T')[0]);

    loadData();

    // Filters & search — all trigger re-render
    ['search-input','filter-status','filter-priority','filter-project','filter-sort','toggle-assigned'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener(el.tagName === 'INPUT' ? 'input' : 'change', () => {
            currentPage = 1;
            renderPage();
        });
    });

    document.addEventListener('click', e => {
        if (!e.target.closest('#status-popover') && !e.target.closest('.task-check')) {
            closeStatusPopover();
        }
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-backdrop.open').forEach(m => m.classList.remove('open'));
            closeStatusPopover();
        }
    });
});

// ── Load ──────────────────────────────────────────────────────────────
async function loadData() {
    try {
        const [projRes, taskRes] = await Promise.all([
            fetch(BASE + '/api/user/projects', { credentials:'same-origin' }),
            fetch(BASE + '/api/user/tasks',    { credentials:'same-origin' }),
        ]);
        const [projJson, taskJson] = await Promise.all([projRes.json(), taskRes.json()]);

        allProjects = projJson.success ? (projJson.data || []) : [];
        allTasks    = taskJson.success ? (taskJson.data || []) : [];

        // Populate project filter dropdown
        const sel = document.getElementById('filter-project');
        allProjects.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.project_id;
            opt.textContent = p.name;
            sel.appendChild(opt);
        });

        // Populate task form project dropdown (manager)
        const tfProj = document.getElementById('tf-project');
        if (tfProj) {
            allProjects.forEach(p => {
                const opt = document.createElement('option');
                opt.value = p.project_id;
                opt.textContent = p.name;
                tfProj.appendChild(opt);
            });
        }

        renderPage();
    } catch (err) {
        document.getElementById('tasks-list').innerHTML = `
            <div class="panel-empty">
                <p style="color:#dc2626">${esc(err.message || 'Failed to load tasks.')}</p>
            </div>`;
    }
}

// ── Filter + sort ─────────────────────────────────────────────────────
function getFiltered() {
    const search  = document.getElementById('search-input').value.toLowerCase().trim();
    const status  = document.getElementById('filter-status').value;
    const prio    = document.getElementById('filter-priority').value;
    const projId  = document.getElementById('filter-project').value;
    const sort    = document.getElementById('filter-sort').value;
    const assignedOnly = document.getElementById('toggle-assigned')?.checked;
    const now     = new Date(); now.setHours(0,0,0,0);

    let tasks = allTasks.filter(t => {
        if (assignedOnly && t.assigned_to !== WH_USER_ID) return false;
        if (status  && t.status   !== status)           return false;
        if (prio    && t.priority !== prio)             return false;
        if (projId  && t.project_id !== parseInt(projId)) return false;
        if (search) {
            const haystack = [t.title, t.description, t.assigned_user_name,
                allProjects.find(p=>p.project_id===t.project_id)?.name || '']
                .join(' ').toLowerCase();
            if (!haystack.includes(search)) return false;
        }
        return true;
    });

    // Sort
    const statusOrder = { in_progress:0, in_review:1, pending:2, completed:3 };
    const prioOrder   = { critical:0, high:1, medium:2, low:3 };

    switch (sort) {
        case 'urgency':
            tasks.sort((a, b) => {
                const aOver = a.due_date && new Date(a.due_date) < now && a.status !== 'completed';
                const bOver = b.due_date && new Date(b.due_date) < now && b.status !== 'completed';
                if (aOver !== bOver) return aOver ? -1 : 1;
                const sDiff = (statusOrder[a.status]??9) - (statusOrder[b.status]??9);
                if (sDiff !== 0) return sDiff;
                return (prioOrder[a.priority]??9) - (prioOrder[b.priority]??9);
            });
            break;
        case 'due_asc':
            tasks.sort((a, b) => (a.due_date||'9999') < (b.due_date||'9999') ? -1 : 1);
            break;
        case 'due_desc':
            tasks.sort((a, b) => (a.due_date||'0000') > (b.due_date||'0000') ? -1 : 1);
            break;
        case 'priority':
            tasks.sort((a, b) => (prioOrder[a.priority]??9) - (prioOrder[b.priority]??9));
            break;
        case 'title':
            tasks.sort((a, b) => a.title.localeCompare(b.title));
            break;
    }

    return tasks;
}

// ── Render ────────────────────────────────────────────────────────────
function renderPage() {
    const filtered = getFiltered();
    const total    = filtered.length;
    const pages    = Math.max(1, Math.ceil(total / PAGE_SIZE));
    currentPage    = Math.min(currentPage, pages);

    const start    = (currentPage - 1) * PAGE_SIZE;
    const slice    = filtered.slice(start, start + PAGE_SIZE);
    const now      = new Date(); now.setHours(0,0,0,0);

    // Subtitle
    const myCount = IS_MANAGER ? total : allTasks.filter(t => t.assigned_to === WH_USER_ID).length;
    document.getElementById('page-subtitle').textContent =
        `${total} task${total !== 1 ? 's' : ''}${total < allTasks.length ? ' (filtered)' : ''}`;

    document.getElementById('results-count').textContent =
        total > 0 ? `${start + 1}–${Math.min(start + PAGE_SIZE, total)} of ${total}` : '';

    const list = document.getElementById('tasks-list');

    if (!slice.length) {
        list.innerHTML = `
            <div class="panel-empty">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <h3>No tasks found</h3>
                <p>Try adjusting your search or filters.</p>
            </div>`;
        document.getElementById('pagination-wrap').style.display = 'none';
        return;
    }

    list.innerHTML = slice.map((t, i) => renderTaskRow(t, now, i)).join('');

    // Pagination
    renderPagination(pages);
}

function renderTaskRow(t, now, animIdx = 0) {
    const isDone     = t.status === 'completed';
    const dueDate    = t.due_date ? new Date(t.due_date) : null;
    const isOverdue  = dueDate && dueDate < now && !isDone;
    const project    = allProjects.find(p => p.project_id === t.project_id);
    const isAssigned = t.assigned_to === WH_USER_ID;

    const rightLabel = IS_MANAGER && t.assigned_user_name
        ? `<span style="font-size:11px;color:var(--text-muted)">${esc(t.assigned_user_name)}</span>`
        : '';

    return `
    <div class="task-row" style="animation:fadeUp .25s ease ${animIdx * 0.03}s both">
        <div class="task-check ${isDone ? 'done' : ''}"
             title="${isAssigned ? 'Update status' : 'Only the assigned member can update'}"
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
}

function renderPagination(pages) {
    const wrap = document.getElementById('pagination-wrap');
    if (pages <= 1) { wrap.style.display = 'none'; return; }
    wrap.style.display = 'flex';

    let html = `<button class="page-btn" onclick="goPage(${currentPage-1})" ${currentPage===1?'disabled':''}>‹</button>`;

    for (let i = 1; i <= pages; i++) {
        if (pages > 7 && i > 2 && i < pages - 1 && Math.abs(i - currentPage) > 1) {
            if (i === 3 || i === pages - 2) html += `<span class="page-info">…</span>`;
            continue;
        }
        html += `<button class="page-btn ${i===currentPage?'active':''}" onclick="goPage(${i})">${i}</button>`;
    }

    html += `<button class="page-btn" onclick="goPage(${currentPage+1})" ${currentPage===pages?'disabled':''}>›</button>`;
    wrap.innerHTML = html;
}

function goPage(p) {
    currentPage = p;
    renderPage();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ── Status popover ────────────────────────────────────────────────────
function openStatusPopover(event, taskId, projectId) {
    event.stopPropagation();
    popoverTaskId    = taskId;
    popoverProjectId = projectId;
    const popover = document.getElementById('status-popover');
    const rect    = event.currentTarget.getBoundingClientRect();
    popover.style.top  = (rect.bottom + window.scrollY + 6) + 'px';
    popover.style.left = (rect.left  + window.scrollX) + 'px';
    popover.classList.add('open');
}

function closeStatusPopover() {
    document.getElementById('status-popover').classList.remove('open');
    popoverTaskId = popoverProjectId = null;
}

async function setStatus(status) {
    if (!popoverTaskId) return;
    const taskId    = popoverTaskId;
    const projectId = popoverProjectId;
    closeStatusPopover();

    try {
        const res  = await fetch(BASE + '/api/user/tasks/status', {
            method: 'PATCH',
            headers: { 'Content-Type':'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ task_id: taskId, project_id: projectId, status }),
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);

        const t = allTasks.find(t => t.task_id === taskId);
        if (t) t.status = status;
        renderPage();
        showToast(`Marked as "${status.replace('_',' ')}"`);
    } catch (err) { showToast(err.message, 'error'); }
}

// ── Manager: create / edit task ───────────────────────────────────────
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
    loadProjectMembers(task.project_id).then(() => {
        document.getElementById('tf-assignee').value = task.assigned_to || '';
    });
    openModal('modal-task');
}

async function loadProjectMembers(projectId) {
    const sel = document.getElementById('tf-assignee');
    if (!sel) return;
    sel.innerHTML = '<option value="">Loading…</option>';
    if (!projectId) { sel.innerHTML = '<option value="">— Select member —</option>'; return; }
    try {
        const res  = await fetch(`${BASE}/api/user/project/members?project_id=${projectId}`, { credentials:'same-origin' });
        const json = await res.json();
        sel.innerHTML = '<option value="">— Select member —</option>' +
            (json.data || []).map(m => `<option value="${m.user_id}">${esc(m.name || m.email)}</option>`).join('');
    } catch { sel.innerHTML = '<option value="">Failed to load</option>'; }
}

document.getElementById('task-form')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn   = document.getElementById('task-submit-btn');
    const errEl = document.getElementById('task-error');
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
            headers: { 'Content-Type':'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify(payload),
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);

        closeModal('modal-task');
        // Reload tasks
        const taskRes  = await fetch(BASE + '/api/user/tasks', { credentials:'same-origin' });
        const taskJson = await taskRes.json();
        allTasks = taskJson.success ? (taskJson.data || []) : allTasks;
        renderPage();
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
            headers: { 'Content-Type':'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ task_id: taskId, project_id: projectId }),
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        allTasks = allTasks.filter(t => t.task_id !== taskId);
        renderPage();
        showToast('Task deleted.', 'error');
    } catch (err) { showToast(err.message, 'error'); }
}

// ── Helpers ───────────────────────────────────────────────────────────
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
function openModal(id)  { document.getElementById(id)?.classList.add('open'); }
function closeModal(id) { document.getElementById(id)?.classList.remove('open'); }
function closeModalOutside(e, id) { if (e.target.id === id) closeModal(id); }
function handleLogout() {
    if (!confirm('Are you sure you want to logout?')) return;
    fetch(BASE + '/user/logout', { method:'POST', credentials:'same-origin' })
        .finally(() => { location.href = BASE + '/user/login'; });
}