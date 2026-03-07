/**
 * public/js/projects.js
 * Drives the Projects page. Requires app.js loaded first.
 */

// ── State ────────────────────────────────────────────────────────────
let allProjects    = [];
let activeFilter   = 'all';
let activeDrawerProjectId = null;

// ── Boot ─────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    loadSidebarOrg();   // sidebar org block (from app.js)
    loadProjects();

    // Filter buttons
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            activeFilter = btn.dataset.filter;
            renderProjects();
        });
    });

    // Search
    document.getElementById('search-input')?.addEventListener('input', renderProjects);
});

// ── Load projects ────────────────────────────────────────────────────
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
    if (sub) sub.textContent = `${allProjects.length} project${allProjects.length !== 1 ? 's' : ''} in your organization`;
}

// ── Render projects grid ─────────────────────────────────────────────
function renderProjects() {
    const grid   = document.getElementById('projects-grid');
    const search = (document.getElementById('search-input')?.value || '').toLowerCase();

    let filtered = allProjects.filter(p => {
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
             onclick="openMembersDrawer(${p.project_id}, '${esc(p.name)}')">

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
                    <button class="project-action-btn" title="Edit" onclick="openEditModal(${p.project_id})">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                    </button>
                    <button class="project-action-btn delete" title="Delete" onclick="openDeleteModal(${p.project_id}, '${esc(p.name)}')">
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
        errEl.textContent   = err.message;
        errEl.style.display = 'block';
    } finally {
        btn.classList.remove('loading');
    }
});

// ── Edit project ──────────────────────────────────────────────────────
function openEditModal(projectId) {
    const project = allProjects.find(p => p.project_id === projectId);
    if (!project) return;

    document.getElementById('ep-id').value          = project.project_id;
    document.getElementById('ep-name').value        = project.name;
    document.getElementById('ep-desc').value        = project.description || '';
    document.getElementById('ep-status').value      = project.status;
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
        errEl.textContent   = err.message;
        errEl.style.display = 'block';
    } finally {
        btn.classList.remove('loading');
    }
});

// ── Delete project ────────────────────────────────────────────────────
function openDeleteModal(projectId, projectName) {
    document.getElementById('delete-project-id').value = projectId;
    document.getElementById('delete-project-msg').textContent =
        `"${projectName}" and all its tasks will be permanently deleted. This cannot be undone.`;
    openModal('modal-delete-project');
}

document.getElementById('confirm-delete-btn')?.addEventListener('click', async function() {
    const projectId = parseInt(document.getElementById('delete-project-id').value);
    const btn       = this;
    btn.classList.add('loading');

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

    } catch (err) {
        showToast(err.message, 'error');
    } finally {
        btn.classList.remove('loading');
    }
});

// ── Members drawer ────────────────────────────────────────────────────
async function openMembersDrawer(projectId, projectName) {
    activeDrawerProjectId = projectId;
    document.getElementById('drawer-project-name').textContent = projectName;
    document.getElementById('drawer-body').innerHTML =
        '<div class="drawer-empty">Loading members…</div>';
    document.getElementById('members-drawer').classList.add('open');

    try {
        const res  = await fetch(`${BASE}/api/projects/members?project_id=${projectId}`, {
            credentials: 'same-origin'
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        renderDrawerMembers(json.data);
    } catch (err) {
        document.getElementById('drawer-body').innerHTML =
            `<div class="drawer-empty">${esc(err.message)}</div>`;
    }
}

function renderDrawerMembers(members) {
    const body = document.getElementById('drawer-body');
    if (!members || members.length === 0) {
        body.innerHTML = '<div class="drawer-empty">No members yet.</div>';
        return;
    }

    body.innerHTML = members.map(m => `
        <div class="member-row" id="member-row-${m.user_id}">
            <div class="member-av">${esc((m.name || m.email || '?').charAt(0).toUpperCase())}</div>
            <div class="member-info">
                <div class="member-name">${esc(m.name || '—')}</div>
                <div class="member-email">${esc(m.email || '')}</div>
            </div>
            <span class="role-badge ${esc(m.role)}">${esc(m.role)}</span>
            <button class="btn-remove-member" title="Remove" onclick="removeMember(${m.user_id})">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>`).join('');
}

async function removeMember(userId) {
    if (!activeDrawerProjectId) return;
    if (!confirm('Remove this member from the project?')) return;

    try {
        const res  = await fetch(BASE + '/api/projects/members', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ project_id: activeDrawerProjectId, user_id: userId }),
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);

        // Animate out
        const row = document.getElementById(`member-row-${userId}`);
        if (row) {
            row.style.transition = 'opacity .2s, transform .2s';
            row.style.opacity = '0';
            row.style.transform = 'translateX(16px)';
            setTimeout(() => row.remove(), 200);
        }
        showToast('Member removed.');

    } catch (err) {
        showToast(err.message, 'error');
    }
}

function closeDrawer() {
    document.getElementById('members-drawer')?.classList.remove('open');
    activeDrawerProjectId = null;
}

function closeDrawerOutside(e) {
    if (e.target.id === 'members-drawer') closeDrawer();
}

// Close drawer on Escape (supplement to app.js modal close)
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeDrawer();
});