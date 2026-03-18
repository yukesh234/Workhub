/**
 * public/js/projects.js
 * Drives the Projects page. Requires app.js loaded first.
 */

let allProjects            = [];
let allUsers               = [];
let activeFilter           = 'all';
let activeMembersProjectId = null;
let pickerProjectId        = null;
let pickerSelected         = new Set();

// ── Dummy project members (used as fallback if API not ready) ─────────
const DUMMY_PROJECT_MEMBERS = {};

// ── Boot ─────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    loadSidebarOrg();
    loadProjects();
    loadOrgUsers();

    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            activeFilter = btn.dataset.filter;
            renderProjects();
        });
    });

    document.getElementById('search-input')?.addEventListener('input', renderProjects);
    document.getElementById('picker-search')?.addEventListener('input', renderPickerUsers);
});

// ── Load org users (for the member picker) ────────────────────────────
async function loadOrgUsers() {
    try {
        const res  = await fetch(BASE + '/api/members', { credentials: 'same-origin' });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        allUsers = json.data || [];
    } catch (err) {
        console.warn('Could not load org users:', err.message);
        allUsers = [];
    }
}

// ── Load projects ─────────────────────────────────────────────────────
async function loadProjects() {
    try {
        const res  = await fetch(BASE + '/api/projects', { credentials: 'same-origin' });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        allProjects = json.data || [];
        updateSubtitle();
        renderProjects();
    } catch (err) {
        document.getElementById('projects-grid').innerHTML = `
            <div class="projects-empty">
                <p style="color:var(--text-muted)">${esc(err.message || 'Failed to load projects.')}</p>
                <button class="btn-ghost" onclick="loadProjects()" style="margin-top:12px">Retry</button>
            </div>`;
    }
}

function updateSubtitle() {
    const sub = document.getElementById('projects-subtitle');
    if (sub) sub.textContent =
        `${allProjects.length} project${allProjects.length !== 1 ? 's' : ''} in your organization`;
}

// ── Render projects grid ──────────────────────────────────────────────
function renderProjects() {
    const grid   = document.getElementById('projects-grid');
    const search = (document.getElementById('search-input')?.value || '').toLowerCase();

    const filtered = allProjects.filter(p => {
        const matchFilter = activeFilter === 'all' || p.status === activeFilter;
        const matchSearch = !search
            || p.name.toLowerCase().includes(search)
            || (p.description || '').toLowerCase().includes(search);
        return matchFilter && matchSearch;
    });

    if (filtered.length === 0) {
        grid.innerHTML = `
            <div class="projects-empty">
                <div class="projects-empty-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                    </svg>
                </div>
                <h3>${search ? 'No results found' : 'No projects yet'}</h3>
                <p>${search ? `No projects match "${esc(search)}"` : 'Create your first project to get started.'}</p>
                ${!search ? `<button class="btn-primary" onclick="openModal('modal-create-project')">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>New Project</button>` : ''}
            </div>`;
        return;
    }

    grid.innerHTML = filtered.map((p, i) => `
        <div class="project-card" style="animation-delay:${i * 0.04}s"
             onclick="location.href = window.WH_BASE + '/project-detail?id=${p.project_id}'">

            <div class="project-card-header">
                <div class="project-name">${esc(p.name)}</div>
                <span class="status-pill ${esc(p.status)}">${esc(p.status)}</span>
            </div>

            ${p.description
                ? `<div class="project-desc">${esc(p.description)}</div>`
                : `<div class="project-desc" style="color:var(--text-muted);font-style:italic">No description</div>`
            }

            <div class="project-footer">
                <span class="project-date">Created ${formatDate(p.created_at)}</span>
                <div class="project-actions" onclick="event.stopPropagation()">
                    <button class="project-action-btn add-member" title="Add member"
                            onclick="openMemberPicker(${p.project_id}, '${esc(p.name)}')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="8.5" cy="7" r="4"/>
                            <line x1="20" y1="8" x2="20" y2="14"/>
                            <line x1="23" y1="11" x2="17" y2="11"/>
                        </svg>
                    </button>
                    <button class="project-action-btn edit-btn" title="Edit"
                            onclick="openEditModal(${p.project_id})">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                    </button>
                    <button class="project-action-btn delete" title="Delete"
                            onclick="openDeleteModal(${p.project_id}, '${esc(p.name)}')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"/>
                            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                            <path d="M10 11v6"/><path d="M14 11v6"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>`).join('');
}

// ── Members Modal (card click) ────────────────────────────────────────
async function openMembersModal(projectId, projectName) {
    activeMembersProjectId = projectId;

    document.getElementById('members-modal-title').textContent = projectName;
    document.getElementById('members-modal-body').innerHTML = `
        <div class="members-modal-loading">
            <div class="spinner" style="width:32px;height:32px;border-width:2px"></div>
        </div>`;

    openModal('modal-members');

    try {
        const res  = await fetch(`${BASE}/api/projects/members?project_id=${projectId}`, { credentials: 'same-origin' });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        renderMembersModalList(json.data, projectId);
    } catch {
        renderMembersModalList([], projectId);
    }
}

function renderMembersModalList(members, projectId) {
    const body = document.getElementById('members-modal-body');

    if (!members || members.length === 0) {
        body.innerHTML = `
            <div class="members-modal-empty">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                <p>No members yet</p>
                <button class="btn-primary" style="margin-top:16px;padding:10px 22px;font-size:13px"
                        onclick="closeModal('modal-members'); openMemberPicker(${projectId}, document.getElementById('members-modal-title').textContent)">
                    Add First Member
                </button>
            </div>`;
        return;
    }

    body.innerHTML = members.map(m => {
        const initials   = (m.name || m.email || '?').split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
        // API returns userProfile; support avatar as fallback for any local dummy data
        const avatarSrc  = m.userProfile || m.avatar || null;
        const avatarHtml = avatarSrc
            ? `<img class="mm-avatar-img" src="${esc(avatarSrc)}" alt="${esc(m.name || '')}">`
            : `<div class="mm-avatar-fallback">${initials}</div>`;
        return `
        <div class="mm-member-row" id="mmrow-${m.user_id}">
            <div class="mm-avatar">${avatarHtml}</div>
            <div class="mm-info">
                <div class="mm-name">${esc(m.name || '—')}</div>
                <div class="mm-email">${esc(m.email || '')}</div>
            </div>
            <span class="role-badge ${esc(m.role || 'member')}">${esc(m.role || 'member')}</span>
            <button class="btn-remove-member" title="Remove"
                    onclick="removeMemberFromModal(${m.user_id})">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>`;
    }).join('');
}

async function removeMemberFromModal(userId) {
    if (!activeMembersProjectId || !confirm('Remove this member from the project?')) return;
    try {
        const res  = await fetch(BASE + '/api/projects/members', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ project_id: activeMembersProjectId, user_id: userId }),
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);

        const row = document.getElementById(`mmrow-${userId}`);
        if (row) {
            row.style.transition = 'opacity .2s, transform .2s';
            row.style.opacity    = '0';
            row.style.transform  = 'translateX(16px)';
            setTimeout(() => {
                row.remove();
                if (!document.querySelector('.mm-member-row')) {
                    renderMembersModalList([], activeMembersProjectId);
                }
            }, 200);
        }
        showToast('Member removed.');
    } catch (err) { showToast(err.message, 'error'); }
}

// ── Member picker ─────────────────────────────────────────────────────
let currentProjectMemberIds = new Set(); // tracks who's already in the project

async function openMemberPicker(projectId, projectName) {
    pickerProjectId = projectId;
    pickerSelected  = new Set();
    document.getElementById('picker-project-name').textContent = projectName;
    document.getElementById('picker-search').value = '';
    document.getElementById('picker-selected-count').textContent = '';
    document.getElementById('picker-confirm-btn').disabled = true;

    // Fetch existing members so we can exclude them from the picker
    currentProjectMemberIds = new Set();
    try {
        const res  = await fetch(`${BASE}/api/projects/members?project_id=${projectId}`, { credentials: 'same-origin' });
        const json = await res.json();
        if (json.success && json.data) {
            json.data.forEach(m => currentProjectMemberIds.add(m.user_id));
        }
    } catch { /* if it fails, just show everyone */ }

    renderPickerUsers();
    openModal('modal-add-member');
}

function renderPickerUsers() {
    const query    = (document.getElementById('picker-search')?.value || '').toLowerCase();
    const list     = document.getElementById('picker-user-list');

    if (allUsers.length === 0) {
        list.innerHTML = `<div class="picker-empty">No org members found. Add members from the Members page first.</div>`;
        return;
    }

    const available = allUsers.filter(u => !currentProjectMemberIds.has(u.user_id));
    if (available.length === 0) {
        list.innerHTML = `<div class="picker-empty">All org members are already in this project.</div>`;
        return;
    }

    const filtered = allUsers.filter(u =>
        !currentProjectMemberIds.has(u.user_id) && (
            !query
            || (u.name  || '').toLowerCase().includes(query)
            || (u.email || '').toLowerCase().includes(query)
        )
    );

    if (filtered.length === 0) {
        list.innerHTML = `<div class="picker-empty">No users match "${esc(query)}"</div>`;
        return;
    }

    list.innerHTML = filtered.map(u => {
        const selected   = pickerSelected.has(u.user_id);
        const initials   = (u.name || u.email || '?').split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
        // API field is userProfile; fallback to avatar for any local data
        const avatarSrc  = u.userProfile || u.avatar || null;
        const avatarHtml = avatarSrc
            ? `<img class="picker-avatar" src="${esc(avatarSrc)}" alt="${esc(u.name || '')}">`
            : `<div class="picker-avatar picker-avatar-fallback">${initials}</div>`;
        return `
        <div class="picker-user ${selected ? 'selected' : ''}"
             onclick="togglePickerUser(${u.user_id})" data-uid="${u.user_id}">
            <div class="picker-avatar-wrap">${avatarHtml}</div>
            <div class="picker-user-info">
                <div class="picker-user-name">${esc(u.name || '—')}</div>
                <div class="picker-user-email">${esc(u.email || '')}</div>
            </div>
            <div class="picker-check ${selected ? 'visible' : ''}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"
                     stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
            </div>
        </div>`;
    }).join('');
}

function togglePickerUser(userId) {
    pickerSelected.has(userId) ? pickerSelected.delete(userId) : pickerSelected.add(userId);
    const row   = document.querySelector(`.picker-user[data-uid="${userId}"]`);
    const check = row?.querySelector('.picker-check');
    const sel   = pickerSelected.has(userId);
    row?.classList.toggle('selected', sel);
    check?.classList.toggle('visible', sel);
    const count = pickerSelected.size;
    document.getElementById('picker-selected-count').textContent = count > 0 ? `${count} selected` : '';
    document.getElementById('picker-confirm-btn').disabled = count === 0;
}

document.getElementById('picker-confirm-btn')?.addEventListener('click', async function () {
    if (!pickerProjectId || pickerSelected.size === 0) return;
    this.classList.add('loading');
    const errors = [];

    for (const userId of pickerSelected) {
        try {
            const res  = await fetch(BASE + '/api/projects/members', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ project_id: pickerProjectId, user_id: userId, role: 'member' }),
            });
            const json = await res.json();
            if (!json.success) errors.push(json.message);
        } catch { errors.push(`Failed to add user ${userId}`); }
    }

    this.classList.remove('loading');
    closeModal('modal-add-member');
    showToast(
        errors.length === 0
            ? `${pickerSelected.size} member${pickerSelected.size > 1 ? 's' : ''} added!`
            : errors[0],
        errors.length === 0 ? 'success' : 'error'
    );
    pickerSelected  = new Set();
    pickerProjectId = null;
});

// ── Create project ────────────────────────────────────────────────────
document.getElementById('create-project-form')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn   = document.getElementById('create-project-btn');
    const errEl = document.getElementById('create-project-error');
    errEl.style.display = 'none';
    btn.classList.add('loading');
    try {
        const res  = await fetch(BASE + '/api/projects', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({
                name:        document.getElementById('cp-name').value.trim(),
                description: document.getElementById('cp-desc').value.trim() || null,
            }),
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        closeModal('modal-create-project');
        this.reset();
        await loadProjects();
        showToast('Project created successfully!');
    } catch (err) {
        errEl.textContent = err.message; errEl.style.display = 'block';
    } finally { btn.classList.remove('loading'); }
});

// ── Edit project ──────────────────────────────────────────────────────
function openEditModal(projectId) {
    const project = allProjects.find(p => p.project_id === projectId);
    if (!project) return;
    document.getElementById('ep-id').value     = project.project_id;
    document.getElementById('ep-name').value   = project.name;
    document.getElementById('ep-desc').value   = project.description || '';
    document.getElementById('ep-status').value = project.status;
    document.getElementById('edit-project-error').style.display = 'none';
    openModal('modal-edit-project');
}

document.getElementById('edit-project-form')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn   = document.getElementById('edit-project-btn');
    const errEl = document.getElementById('edit-project-error');
    errEl.style.display = 'none';
    btn.classList.add('loading');
    try {
        const res  = await fetch(BASE + '/api/projects', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({
                project_id:  parseInt(document.getElementById('ep-id').value),
                name:        document.getElementById('ep-name').value.trim(),
                description: document.getElementById('ep-desc').value.trim() || null,
                status:      document.getElementById('ep-status').value,
            }),
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        closeModal('modal-edit-project');
        await loadProjects();
        showToast('Project updated successfully!');
    } catch (err) {
        errEl.textContent = err.message; errEl.style.display = 'block';
    } finally { btn.classList.remove('loading'); }
});

// ── Delete project ────────────────────────────────────────────────────
function openDeleteModal(projectId, projectName) {
    document.getElementById('delete-project-id').value = projectId;
    document.getElementById('delete-project-msg').textContent =
        `"${projectName}" and all its tasks will be permanently deleted. This cannot be undone.`;
    openModal('modal-delete-project');
}

document.getElementById('confirm-delete-btn')?.addEventListener('click', async function () {
    const projectId = parseInt(document.getElementById('delete-project-id').value);
    this.classList.add('loading');
    try {
        const res  = await fetch(BASE + '/api/projects', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ project_id: projectId }),
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        closeModal('modal-delete-project');
        await loadProjects();
        showToast('Project deleted.', 'error');
    } catch (err) { showToast(err.message, 'error'); }
    finally { this.classList.remove('loading'); }
});