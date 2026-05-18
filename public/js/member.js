/**
 * public/js/members.js
 * Drives the Members page. Requires app.js loaded first.
 */

let allMembers   = [];
let activeRole   = 'all';

// ── Boot ─────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    loadSidebarOrg();
    loadMembers();

    // Role filter buttons
    document.querySelectorAll('.role-filter-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.role-filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            activeRole = btn.dataset.role;
            renderMembers();
        });
    });

    // Search
    document.getElementById('member-search')?.addEventListener('input', renderMembers);

    // Date in topbar
    const el = document.getElementById('topbarDate');
    if (el) el.textContent = new Date().toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' });
});

// ── Load members ──────────────────────────────────────────────────────
async function loadMembers() {
    try {
        const res  = await fetch(BASE + '/api/members', { credentials: 'same-origin' });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        allMembers = json.data || [];
        updateSubtitle();
        renderMembers();
    } catch (err) {
        document.getElementById('members-grid').innerHTML = `
            <div class="members-empty">
                <p>${esc(err.message || 'Failed to load members.')}</p>
                <button class="btn-ghost" onclick="loadMembers()" style="margin-top:12px">Retry</button>
            </div>`;
    }
}

function updateSubtitle() {
    const sub = document.getElementById('members-subtitle');
    if (!sub) return;
    const managers = allMembers.filter(m => m.role === 'manager').length;
    const members  = allMembers.filter(m => m.role === 'member').length;
    sub.textContent = `${allMembers.length} total · ${managers} manager${managers !== 1 ? 's' : ''} · ${members} member${members !== 1 ? 's' : ''}`;
}

// ── Render grid ───────────────────────────────────────────────────────
function renderMembers() {
    const grid   = document.getElementById('members-grid');
    const search = (document.getElementById('member-search')?.value || '').toLowerCase();

    const filtered = allMembers.filter(m => {
        const matchRole   = activeRole === 'all' || m.role === activeRole;
        const matchSearch = !search
            || (m.name  || '').toLowerCase().includes(search)
            || (m.email || '').toLowerCase().includes(search);
        return matchRole && matchSearch;
    });

    if (filtered.length === 0) {
        grid.innerHTML = `
            <div class="members-empty">
                <div class="members-empty-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
                <h3>${search ? 'No results found' : 'No members yet'}</h3>
                <p>${search ? `No members match "${esc(search)}"` : 'Add your first team member to get started.'}</p>
                ${!search ? `<button class="btn-primary" onclick="openModal('modal-add-member')">Add First Member</button>` : ''}
            </div>`;
        return;
    }

    grid.innerHTML = filtered.map((m, i) => {
        const initials   = (m.name || m.email || '?').split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
        const avatarHtml = m.userProfile
            ? `<img class="mc-avatar" src="${esc(m.userProfile)}" alt="${esc(m.name || '')}">`
            : `<div class="mc-avatar-fallback">${initials}</div>`;

        return `
        <div class="member-card" style="animation-delay:${i * 0.04}s" id="mc-${m.user_id}">
            <div class="mc-avatar-wrap" style="cursor:pointer" onclick="window.location='${BASE}/member-analytics?id=${m.user_id}'">${avatarHtml}</div>
            <div class="mc-name" style="cursor:pointer" onclick="window.location='${BASE}/member-analytics?id=${m.user_id}'">${esc(m.name || '—')}</div>
            ${m.email ? `<div class="mc-email">${esc(m.email)}</div>` : ''}
            <span class="role-badge ${esc(m.role)}">${esc(m.role)}</span>
            <div class="mc-joined">Joined ${formatDateShort(m.created_at)}</div>
            
        <div class="mc-actions">
    <button class="btn-mc-action" title="View analytics"
            onclick="window.location='${BASE}/member-analytics?id=${m.user_id}'">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="20" x2="18" y2="10"/>
            <line x1="12" y1="20" x2="12" y2="4"/>
            <line x1="6"  y1="20" x2="6"  y2="14"/>
            <line x1="2"  y1="20" x2="22" y2="20"/>
        </svg>
        Analytics
    </button>
    <button class="btn-mc-action" title="Edit member"
            onclick="openEditMemberModal(${m.user_id})">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round">
            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
        </svg>
        Edit
    </button>
    <button class="btn-mc-action danger" title="Remove member"
            onclick="removeMember(${m.user_id}, '${esc(m.name || 'this member')}')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round">
            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
            <circle cx="8.5" cy="7" r="4"/>
            <line x1="18" y1="8" x2="23" y2="13"/>
            <line x1="23" y1="8" x2="18" y2="13"/>
        </svg>
        Remove
        </button>
    </div>
        </div>`;
    }).join('');
}

// ── Photo preview ─────────────────────────────────────────────────────
function previewMemberPhoto(input) {
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        const img  = document.getElementById('photo-preview-img');
        const icon = document.getElementById('photo-preview-icon');
        img.src    = e.target.result;
        img.style.display  = 'block';
        icon.style.display = 'none';
    };
    reader.readAsDataURL(file);
}

// ── Role radio styling ────────────────────────────────────────────────
function selectRole(radio) {
    document.getElementById('role-member-opt').classList.remove('selected');
    document.getElementById('role-manager-opt').classList.remove('selected');
    document.getElementById(`role-${radio.value}-opt`).classList.add('selected');
}

// ── Add member form submit 
document.getElementById('add-member-form')?.addEventListener('submit', async function (e) {
    e.preventDefault();
    const btn   = document.getElementById('add-member-btn');
    const errEl = document.getElementById('add-member-error');
    errEl.style.display = 'none';
    btn.classList.add('loading');

    try {
        const formData = new FormData(this);

        const res  = await fetch(BASE + '/api/members', {
            method: 'POST',
            credentials: 'same-origin',
            body: formData,  // multipart — do NOT set Content-Type header
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);

        // Close add modal, show credentials
        closeModal('modal-add-member');
        this.reset();
        resetPhotoPreview();

        showCredentials(json.data);

        // Refresh list
        await loadMembers();

    } catch (err) {
        errEl.textContent   = err.message;
        errEl.style.display = 'block';
    } finally {
        btn.classList.remove('loading');
    }
});

function resetPhotoPreview() {
    const img  = document.getElementById('photo-preview-img');
    const icon = document.getElementById('photo-preview-icon');
    img.src    = '';
    img.style.display  = 'none';
    icon.style.display = '';
    // Reset role highlight
    document.getElementById('role-member-opt').classList.add('selected');
    document.getElementById('role-manager-opt').classList.remove('selected');
}

// ── Show credential modal 
function showCredentials(data) {
    document.getElementById('cred-member-name').textContent = data.name  || 'the member';
    document.getElementById('cred-email').textContent       = data.email || '';
    document.getElementById('cred-password').textContent    = data.generated_password || '';
    openModal('modal-credentials');
}

// ── Copy helper 
function copyCredField(elementId, btn) {
    const text = document.getElementById(elementId)?.textContent || '';
    navigator.clipboard.writeText(text).then(() => {
        btn.textContent = 'Copied!';
        btn.classList.add('copied');
        setTimeout(() => {
            btn.textContent = 'Copy';
            btn.classList.remove('copied');
        }, 2000);
    });
}

// ── Remove member 
async function removeMember(userId, name) {
    if(!await ConfirmDialog.show(`Are you sure you want to remove ${name}? This cannot be undone.`)) return;
    try {
        const res  = await fetch(BASE + '/api/members', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ user_id: userId }),
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);

        // Animate removal
        const card = document.getElementById(`mc-${userId}`);
        if (card) {
            card.style.transition = 'opacity .2s, transform .2s';
            card.style.opacity    = '0';
            card.style.transform  = 'scale(.95)';
            setTimeout(() => {
                allMembers = allMembers.filter(m => m.user_id !== userId);
                updateSubtitle();
                renderMembers();
            }, 200);
        }

        showToast(`${name} removed.`, 'error');
    } catch (err) {
        showToast(err.message, 'error');
    }
}

// ── Open edit modal 
function openEditMemberModal(userId) {
    const m = allMembers.find(m => m.user_id === userId);
    if (!m) return;

    // Populate fields
    document.getElementById('edit-user-id').value    = m.user_id;
    document.getElementById('edit-m-name').value     = m.name  || '';
    document.getElementById('edit-m-email').value    = m.email || '';
    document.getElementById('edit-member-error').style.display = 'none';

    // Set role radio + highlight
    const roleValue = m.role === 'manager' ? 'manager' : 'member';
    document.querySelector(`#edit-member-form input[name="role"][value="${roleValue}"]`).checked = true;
    document.getElementById('edit-role-member-opt').classList.toggle('selected',  roleValue === 'member');
    document.getElementById('edit-role-manager-opt').classList.toggle('selected', roleValue === 'manager');

    // Show existing photo or fallback icon
    const img  = document.getElementById('edit-photo-preview-img');
    const icon = document.getElementById('edit-photo-preview-icon');
    if (m.userProfile) {
        img.src            = m.userProfile;
        img.style.display  = 'block';
        icon.style.display = 'none';
    } else {
        img.src            = '';
        img.style.display  = 'none';
        icon.style.display = '';
    }

    // Clear any previously selected file
    document.getElementById('edit-member-photo-input').value = '';

    openModal('modal-edit-member');
}

// ── Photo preview (edit modal) ────────────────────────────────────────
function previewEditMemberPhoto(input) {
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        const img  = document.getElementById('edit-photo-preview-img');
        const icon = document.getElementById('edit-photo-preview-icon');
        img.src           = e.target.result;
        img.style.display  = 'block';
        icon.style.display = 'none';
    };
    reader.readAsDataURL(file);
}

// ── Role highlight (edit modal) ───────────────────────────────────────
function selectEditRole(radio) {
    document.getElementById('edit-role-member-opt').classList.remove('selected');
    document.getElementById('edit-role-manager-opt').classList.remove('selected');
    document.getElementById(`edit-role-${radio.value}-opt`).classList.add('selected');
}

// ── Edit member form submit ───────────────────────────────────────────
document.getElementById('edit-member-form')?.addEventListener('submit', async function (e) {
    e.preventDefault();

    const btn   = document.getElementById('edit-member-btn');
    const errEl = document.getElementById('edit-member-error');
    errEl.style.display = 'none';

    const name  = document.getElementById('edit-m-name').value.trim();
    const email = document.getElementById('edit-m-email').value.trim();
    const role  = document.querySelector('#edit-member-form input[name="role"]:checked')?.value;

    if (!name)  { showInlineError(errEl, 'Name is required');              return; }
    if (!email) { showInlineError(errEl, 'Email is required');             return; }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                  showInlineError(errEl, 'Enter a valid email address');   return; }
    if (!role)  { showInlineError(errEl, 'Please select a role');          return; }

    btn.classList.add('loading');

    try {
        const formData = new FormData(this); // user_id already in form as hidden input

        const res  = await fetch(BASE + '/api/members', {
            method: 'PUT',
            credentials: 'same-origin',
            body: formData,
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);

        // Patch local state — no full reload needed
        const idx = allMembers.findIndex(m => m.user_id === json.data.user_id);
        if (idx !== -1) allMembers[idx] = { ...allMembers[idx], ...json.data };

        closeModal('modal-edit-member');
        updateSubtitle();
        renderMembers();
        showToast('Member updated successfully!');

    } catch (err) {
        showInlineError(errEl, err.message);
    } finally {
        btn.classList.remove('loading');
    }
});

// ── Small helper (avoids repeating the two-liner) 
function showInlineError(el, msg) {
    el.textContent   = msg;
    el.style.display = 'block';
}