/**
 * public/js/user-project-detail.js
 * User-side project detail. Manager can CRUD tasks, member can only view + comment.
 */

const PROJECT_ID = parseInt(new URLSearchParams(location.search).get('id')) || 0;
let allTasks     = [];
let allMembers   = [];
let activeTab    = 'all';
let currentTask  = null;

if (!PROJECT_ID) location.href = BASE + '/user/dashboard';

// ── Boot ──────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('topbar-date').textContent =
        new Date().toLocaleDateString('en-US', { weekday:'long', month:'long', day:'numeric' });

    if (IS_MANAGER) {
        document.getElementById('tf-due')?.setAttribute('min', new Date().toISOString().split('T')[0]);
    }

    Promise.all([loadProject(), loadMembers(), loadTasks()]);

    // Init meeting polling — manager can start, members can join
    initMeeting(window._meetingUserName || 'Team Member').then(() => syncHeroButton());

    document.getElementById('task-tabs').addEventListener('click', e => {
        const btn = e.target.closest('.task-tab');
        if (!btn) return;
        document.querySelectorAll('.task-tab').forEach(t => t.classList.remove('active'));
        btn.classList.add('active');
        activeTab = btn.dataset.status;
        renderTasks();
    });

    // Close status popover on outside click
    document.addEventListener('click', e => {
        if (!e.target.closest('#status-popover')) {
            document.getElementById('status-popover').style.display = 'none';
        }
    });
});

// ── Load project ──────────────────────────────────────────────────────
async function loadProject() {
    try {
        const res  = await fetch(`${BASE}/api/user/project/single?project_id=${PROJECT_ID}`, { credentials:'same-origin' });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        renderHero(json.data);
    } catch (err) {
        document.getElementById('hero-name').textContent = 'Failed to load project';
    }
}

function renderHero(p) {
    document.title = `${p.name} — WorkHub`;
    document.getElementById('topbar-title').textContent = p.name;
    document.getElementById('hero-name').textContent    = p.name;
    document.getElementById('hero-desc').textContent    = p.description || 'No description.';
    if (!p.description) document.getElementById('hero-desc').style.fontStyle = 'italic';

    document.getElementById('hero-meta').innerHTML = `
        <span class="status-pill ${esc(p.status)}">${esc(p.status)}</span>
        <span class="meta-chip">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
            Created ${formatDateShort(p.created_at)}
        </span>
        <span class="meta-chip" style="background:${p.my_role==='manager'?'var(--brand-pale2)':'var(--surface-2)'};color:${p.my_role==='manager'?'var(--brand)':'var(--text-muted)'};padding:2px 10px;border-radius:20px;font-weight:700;text-transform:capitalize">
            ${esc(p.my_role)}
        </span>`;
}

// ── Load members ──────────────────────────────────────────────────────
async function loadMembers() {
    try {
        const res  = await fetch(`${BASE}/api/user/project/members?project_id=${PROJECT_ID}`, { credentials:'same-origin' });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        allMembers = json.data || [];
        renderMembers();
        populateAssigneeDropdown();
    } catch { allMembers = []; }
}

function renderMembers() {
    const list = document.getElementById('members-list');
    if (!allMembers.length) {
        list.innerHTML = `<div style="padding:20px;text-align:center;font-size:13px;color:var(--text-muted)">No members yet.</div>`;
        return;
    }
    list.innerHTML = allMembers.map(m => {
        const initials = (m.name || '?').split(' ').map(w=>w[0]).slice(0,2).join('').toUpperCase();
        const avatar   = m.userProfile
            ? `<img src="${esc(m.userProfile)}" style="width:100%;height:100%;object-fit:cover;border-radius:50%">`
            : initials;
        return `
        <div class="member-row">
            <div class="mem-avatar">${avatar}</div>
            <div class="mem-info">
                <div class="mem-name">${esc(m.name || '—')}</div>
                <div class="mem-email">${esc(m.email || '')}</div>
            </div>
            <span class="role-badge ${esc(m.role)}">${esc(m.role)}</span>
        </div>`;
    }).join('');
}

function populateAssigneeDropdown() {
    const sel = document.getElementById('tf-assigned');
    if (!sel) return;
    sel.innerHTML = '<option value="">— Select member —</option>' +
        allMembers.map(m => `<option value="${m.user_id}">${esc(m.name || m.email)}</option>`).join('');
}

// ── Load tasks ────────────────────────────────────────────────────────
async function loadTasks() {
    try {
        const res  = await fetch(`${BASE}/api/user/project/tasks?project_id=${PROJECT_ID}`, { credentials:'same-origin' });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        allTasks = json.data || [];
        renderTasks();
        renderStats();
    } catch (err) {
        document.getElementById('tasks-list').innerHTML =
            `<div class="tasks-empty"><p>${esc(err.message || 'Failed to load tasks.')}</p></div>`;
    }
}

// ── Render tasks ──────────────────────────────────────────────────────
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
                <p>${activeTab==='all' ? 'No tasks yet.' : `No ${activeTab.replace('_',' ')} tasks.`}</p>
            </div>`;
        return;
    }

    list.innerHTML = filtered.map(t => {
        const isDone    = t.status === 'completed';
        const dueDate   = t.due_date ? new Date(t.due_date) : null;
        const isOverdue = dueDate && dueDate < now && !isDone;
        const initials  = (t.assigned_user_name || '?').split(' ').map(w=>w[0]).slice(0,2).join('').toUpperCase();
        const isAssigned = t.assigned_to === WH_USER_ID;

        return `
        <div class="task-row" onclick="openTaskPanel(${t.task_id})">
            <div class="task-check ${isDone ? 'done' : ''}"
                 onclick="${isAssigned ? `event.stopPropagation();openStatusPopoverInline(event,${t.task_id},${PROJECT_ID})` : 'event.stopPropagation()'}"
                 style="${isAssigned ? 'cursor:pointer' : 'cursor:default'}"
                 title="${isAssigned ? 'Update status' : ''}"></div>
            <div class="task-body">
                <div class="task-title ${isDone ? 'done' : ''}">${esc(t.title)}</div>
                <div class="task-meta-row">
                    <div class="priority-dot ${esc(t.priority)}"></div>
                    ${t.due_date ? `<span class="task-due ${isOverdue ? 'overdue' : ''}">${isOverdue ? '⚠ ' : ''}${formatDateShort(t.due_date)}</span>` : ''}
                    ${t.assigned_user_name ? `
                    <span class="task-assignee-chip">
                        <div class="assignee-mini">${initials}</div>
                        ${esc(t.assigned_user_name)}
                    </span>` : ''}
                </div>
            </div>
            <span class="task-status-badge ${esc(t.status)}">${esc(t.status.replace('_',' '))}</span>
            ${IS_MANAGER ? `
            <div class="task-row-actions" onclick="event.stopPropagation()">
                <button class="btn-task-sm" title="Edit" onclick="openEditTaskModal(${t.task_id})">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                </button>
                <button class="btn-task-sm danger" title="Delete" onclick="confirmDeleteTask(${t.task_id})">
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

// ── Stats ─────────────────────────────────────────────────────────────
function renderStats() {
    const now  = new Date(); now.setHours(0,0,0,0);
    const total   = allTasks.length;
    const done    = allTasks.filter(t => t.status === 'completed').length;
    const inprog  = allTasks.filter(t => t.status === 'in_progress').length;
    const overdue = allTasks.filter(t => t.due_date && new Date(t.due_date) < now && t.status !== 'completed').length;

    document.getElementById('stat-total').textContent  = total;
    document.getElementById('stat-done').textContent   = done;
    document.getElementById('stat-inprog').textContent = inprog;
    document.getElementById('stat-overdue').textContent = overdue;

    const pct = total > 0 ? Math.round((done/total)*100) : 0;
    document.getElementById('progress-pct').textContent = pct + '%';
    setTimeout(() => { document.getElementById('progress-fill').style.width = pct + '%'; }, 100);
}

// ── Status popover (inline on check click) ────────────────────────────
let popoverTaskId = null;
let popoverProjectId = null;

function openStatusPopoverInline(event, taskId, projectId) {
    popoverTaskId    = taskId;
    popoverProjectId = projectId;
    const pop  = document.getElementById('status-popover');
    const rect = event.currentTarget.getBoundingClientRect();
    pop.style.top     = (rect.bottom + window.scrollY + 6) + 'px';
    pop.style.left    = (rect.left  + window.scrollX) + 'px';
    pop.style.display = 'flex';
}

async function setStatusFromPanel(status) {
    const taskId    = popoverTaskId;
    const projectId = popoverProjectId;
    document.getElementById('status-popover').style.display = 'none';
    popoverTaskId = popoverProjectId = null;

    if (!taskId) return;

    try {
        const res  = await fetch(BASE + '/api/user/tasks/status', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ task_id: taskId, project_id: projectId, status }),
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);

        const t = allTasks.find(t => t.task_id === taskId);
        if (t) t.status = status;
        renderTasks();
        renderStats();

        // Update panel if it's open on this task
        if (currentTask?.task_id === taskId) {
            currentTask.status = status;
            refreshPanelStatus();
        }
        showToast(`Marked as "${status.replace('_',' ')}"`);
    } catch (err) { showToast(err.message, 'error'); }
}

// ── Task panel ────────────────────────────────────────────────────────
function openTaskPanel(taskId) {
    const task = allTasks.find(t => t.task_id === taskId);
    if (!task) return;
    currentTask = task;

    const now     = new Date(); now.setHours(0,0,0,0);
    const dueDate = task.due_date ? new Date(task.due_date) : null;
    const overdue = dueDate && dueDate < now && task.status !== 'completed';

    document.getElementById('udp-title').textContent       = task.title;
    document.getElementById('udp-desc').textContent        = task.description || 'No description.';
    document.getElementById('udp-desc').className          = 'udp-desc-text' + (!task.description ? ' empty' : '');
    document.getElementById('udp-priority').textContent    = task.priority.charAt(0).toUpperCase() + task.priority.slice(1);
    document.getElementById('udp-due').textContent         = task.due_date ? (formatDateShort(task.due_date) + (overdue ? ' ⚠' : '')) : '—';
    document.getElementById('udp-due').style.color         = overdue ? '#dc2626' : '';
    document.getElementById('udp-assignee').textContent    = task.assigned_user_name || '—';
    document.getElementById('udp-created').textContent     = formatDateShort(task.created_at);

    refreshPanelStatus();

    document.getElementById('task-panel-backdrop').classList.add('open');
    loadPanelComments(task.task_id, PROJECT_ID);
    loadPanelAttachments(task.task_id, PROJECT_ID);
}

function refreshPanelStatus() {
    if (!currentTask) return;
    const statusColors = {
        pending:     { bg:'var(--surface-2)',  color:'var(--text-muted)' },
        in_progress: { bg:'#fff7ed',            color:'#ea580c' },
        in_review:   { bg:'var(--brand-pale2)', color:'var(--brand)' },
        completed:   { bg:'#e6f9f1',            color:'#1a8a5c' },
    };
    const sc         = statusColors[currentTask.status] || statusColors.pending;
    const isAssigned = currentTask.assigned_to === WH_USER_ID;

    document.getElementById('udp-status-display').innerHTML = `
        <span style="display:inline-flex;align-items:center;gap:6px;padding:5px 14px;border-radius:20px;font-size:13px;font-weight:600;background:${sc.bg};color:${sc.color}">
            ${esc(currentTask.status.replace('_',' '))}
        </span>
        ${isAssigned
            ? `<button onclick="openStatusPopoverInline(event,${currentTask.task_id},${PROJECT_ID})"
                style="margin-left:8px;font-size:12px;color:var(--brand);background:none;border:none;cursor:pointer;font-weight:600">
                Change ↓</button>`
            : `<span style="font-size:12px;color:var(--text-muted);margin-left:8px">Updated by assigned member</span>`
        }`;
}

function closeTaskPanel(e) {
    if (e && e.target !== document.getElementById('task-panel-backdrop')) return;
    document.getElementById('task-panel-backdrop').classList.remove('open');
    currentTask = null;
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
    } catch { wrap.innerHTML = `<div style="color:#dc2626;font-size:13px">Failed to load.</div>`; }
}

function renderPanelComments(comments) {
    const wrap = document.getElementById('udp-comments-list');
    if (!comments.length) {
        wrap.innerHTML = `<div style="padding:8px 0;color:var(--text-muted);font-size:13px;font-style:italic">No comments yet.</div>`;
        return;
    }
    wrap.innerHTML = comments.map(c => {
        const initial = (c.author_name || '?').split(/[\s@]/)[0].charAt(0).toUpperCase();
        const avatar  = c.author_avatar
            ? `<img src="${esc(c.author_avatar)}" style="width:28px;height:28px;border-radius:50%;object-fit:cover;flex-shrink:0">`
            : `<div style="width:28px;height:28px;border-radius:50%;background:var(--brand);color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0">${initial}</div>`;
        return `
        <div class="udp-comment" id="upc-${c.comment_id}"
             onmouseenter="this.querySelector('.del-c').style.opacity='1'"
             onmouseleave="this.querySelector('.del-c').style.opacity='0'">
            <div style="display:flex;align-items:center;gap:7px;margin-bottom:5px">
                ${avatar}
                <span style="font-size:13px;font-weight:600;color:var(--text-primary);flex:1">${esc(c.author_name||'—')}</span>
                <span style="font-size:11px;color:var(--text-muted)">${formatDateShort(c.created_at)}</span>
                <button class="del-c" onclick="deletePanelComment(${c.comment_id})"
                    style="background:none;border:none;cursor:pointer;color:var(--text-muted);padding:2px;border-radius:4px;opacity:0;transition:.2s"
                    title="Delete">✕</button>
            </div>
            <div style="font-size:13.5px;color:var(--text-secondary);line-height:1.6;white-space:pre-wrap;padding-left:35px">${esc(c.body)}</div>
        </div>`;
    }).join('');
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
            method:'POST', headers:{'Content-Type':'application/json'}, credentials:'same-origin',
            body: JSON.stringify({ task_id: currentTask.task_id, project_id: PROJECT_ID, body }),
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
            method:'DELETE', headers:{'Content-Type':'application/json'}, credentials:'same-origin',
            body: JSON.stringify({ comment_id: commentId }),
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        const el = document.getElementById(`upc-${commentId}`);
        if (el) { el.style.opacity='0'; el.style.transition='.2s'; setTimeout(()=>el.remove(),200); }
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
    const icons = {'application/pdf':'📄','text/plain':'📝','text/csv':'📊','application/msword':'📝','application/vnd.openxmlformats-officedocument.wordprocessingml.document':'📝','application/vnd.ms-excel':'📊','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':'📊'};
    wrap.innerHTML = attachments.map(a => {
        const isImage = a.file_type?.startsWith('image/');
        const icon    = isImage ? '🖼️' : (icons[a.file_type]||'📎');
        const sizeStr = a.file_size>1024*1024 ? (a.file_size/(1024*1024)).toFixed(1)+' MB' : (a.file_size/1024).toFixed(0)+' KB';
        return `
        <div class="udp-attachment" id="upa-${a.attachment_id}">
            ${isImage
                ? `<img src="${esc(a.file_url)}" style="width:44px;height:44px;border-radius:6px;object-fit:cover;cursor:pointer;border:1px solid var(--border);flex-shrink:0" onclick="window.open('${esc(a.file_url)}','_blank')">`
                : `<div style="width:44px;height:44px;border-radius:6px;background:var(--surface-2);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0">${icon}</div>`}
            <div style="flex:1;min-width:0">
                <a href="${esc(a.file_url)}" target="_blank" style="font-size:13px;font-weight:500;color:var(--brand);display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;text-decoration:none">${esc(a.file_name)}</a>
                <div style="font-size:11px;color:var(--text-muted);margin-top:2px">${sizeStr} · ${formatDateShort(a.created_at)}</div>
            </div>
            <button onclick="deletePanelAttachment(${a.attachment_id})"
                style="background:none;border:none;cursor:pointer;color:var(--text-muted);padding:4px;border-radius:4px;flex-shrink:0"
                onmouseenter="this.style.color='#dc2626'" onmouseleave="this.style.color='var(--text-muted)'"
                title="Remove">✕</button>
        </div>`;
    }).join('');
}

async function uploadPanelAttachment(input) {
    if (!input.files.length || !currentTask) return;
    const formData = new FormData();
    formData.append('file', input.files[0]);
    formData.append('task_id',    currentTask.task_id);
    formData.append('project_id', PROJECT_ID);
    const lbl = document.getElementById('udp-upload-label');
    lbl.textContent = 'Uploading…';
    try {
        const res  = await fetch(BASE + '/api/tasks/attachments', { method:'POST', credentials:'same-origin', body:formData });
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
            method:'DELETE', headers:{'Content-Type':'application/json'}, credentials:'same-origin',
            body: JSON.stringify({ attachment_id: attachmentId }),
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        const el = document.getElementById(`upa-${attachmentId}`);
        if (el) { el.style.opacity='0'; el.style.transition='.2s'; setTimeout(()=>el.remove(),200); }
        showToast('Removed.');
    } catch (err) { showToast(err.message, 'error'); }
}

// ── Manager: create task ──────────────────────────────────────────────
function openCreateTaskModal() {
    document.getElementById('task-modal-title').textContent = 'New Task';
    document.getElementById('task-modal-sub').textContent   = 'Add a task to this project.';
    document.getElementById('task-submit-btn').querySelector('.btn-text').textContent = 'Create Task';
    document.getElementById('task-form').reset();
    document.getElementById('task-form-error').style.display = 'none';
    document.getElementById('tf-task-id').value = '';
    document.getElementById('tf-due').min = new Date().toISOString().split('T')[0];
    openModal('modal-task');
}

function openEditTaskModal(taskId) {
    const task = allTasks.find(t => t.task_id === taskId);
    if (!task) return;
    document.getElementById('task-modal-title').textContent = 'Edit Task';
    document.getElementById('task-modal-sub').textContent   = 'Update the task details.';
    document.getElementById('task-submit-btn').querySelector('.btn-text').textContent = 'Save Changes';
    document.getElementById('task-form-error').style.display = 'none';
    document.getElementById('tf-task-id').value   = task.task_id;
    document.getElementById('tf-title').value     = task.title;
    document.getElementById('tf-desc').value      = task.description || '';
    document.getElementById('tf-assigned').value  = task.assigned_to || '';
    document.getElementById('tf-due').value       = task.due_date ? task.due_date.split(' ')[0] : '';
    document.getElementById('tf-priority').value  = task.priority;
    document.getElementById('tf-due').min         = '';

    document.getElementById('task-panel-backdrop').classList.remove('open');
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
    };
    if (isEdit) {
        payload.task_id = parseInt(taskId);
        const existing  = allTasks.find(t => t.task_id === parseInt(taskId));
        payload.status  = existing?.status || 'pending';
    } else {
        payload.status = 'pending';
    }

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
        await loadTasks();
        showToast(isEdit ? 'Task updated!' : 'Task created!');
    } catch (err) {
        errEl.textContent   = err.message;
        errEl.style.display = 'block';
    } finally { btn.classList.remove('loading'); }
});

async function confirmDeleteTask(taskId) {
    if (!confirm('Delete this task? This cannot be undone.')) return;
    await doDeleteTask(taskId);
}

async function deleteTaskFromPanel() {
    if (!currentTask || !confirm('Delete this task?')) return;
    const id = currentTask.task_id;
    document.getElementById('task-panel-backdrop').classList.remove('open');
    currentTask = null;
    await doDeleteTask(id);
}

async function doDeleteTask(taskId) {
    try {
        const res  = await fetch(BASE + '/api/user/tasks', {
            method:'DELETE', headers:{'Content-Type':'application/json'}, credentials:'same-origin',
            body: JSON.stringify({ task_id: taskId, project_id: PROJECT_ID }),
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        await loadTasks();
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
    return new Date(dateStr).toLocaleDateString('en-US', { month:'short', day:'numeric', year:'numeric' });
}
function showToast(message, type='success') {
    const toast = document.getElementById('toast');
    document.getElementById('toast-msg').textContent = message;
    toast.className = `toast ${type}`;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3000);
}
function openModal(id)   { document.getElementById(id)?.classList.add('open'); }
function closeModal(id)  { document.getElementById(id)?.classList.remove('open'); }
function closeModalOutside(e, id) { if (e.target.id === id) closeModal(id); }
function handleLogout() {
    fetch(BASE + '/user/logout', { method:'POST', credentials:'same-origin' })
        .finally(() => { location.href = BASE + '/user/login'; });
}
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        document.getElementById('task-panel-backdrop')?.classList.remove('open');
        currentTask = null;
    }
});