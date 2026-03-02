// ── XSS helper ──────────────────────────────────────────────────────
function esc(str) {
    return String(str)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;')
        .replace(/'/g,'&#39;');
}

// ── Modal ───────────────────────────────────────────────────────────
function openModal(id)  { document.getElementById(id)?.classList.add('open'); }
function closeModal(id) { document.getElementById(id)?.classList.remove('open'); }

document.addEventListener('keydown', e => {
    if (e.key === 'Escape')
        document.querySelectorAll('.modal-backdrop.open')
            .forEach(m => m.classList.remove('open'));
});

// ── Sidebar helpers ─────────────────────────────────────────────────
function setSidebarSkeleton() {
    document.getElementById('sidebar-org').innerHTML = `
        <div class="org-skeleton">
            <div class="sk-line short"></div>
            <div class="sk-line long"></div>
            <div class="sk-line tiny"></div>
        </div>`;
}

function setSidebarNoOrg() {
    document.getElementById('sidebar-org').innerHTML = `
        <div class="sidebar-no-org">
            <p>No organization yet.<br>Create one to get started.</p>
            <button class="btn-sidebar-create" onclick="openModal('modal-create-org')">+ Create Organization</button>
        </div>`;
}

function setSidebarOrg(org) {
    const logo = org.organization_logo
        ? `<img class="org-logo-thumb" src="${esc(org.organization_logo)}" alt="${esc(org.name)}">`
        : `<div class="org-logo-fallback">${esc(org.name.charAt(0).toUpperCase())}</div>`;

    document.getElementById('sidebar-org').innerHTML = `
        <div class="org-block">
            ${logo}
            <div class="org-text">
                <div class="org-label">Organization</div>
                <div class="org-name">${esc(org.name)}</div>
                ${org.slogan ? `<div class="org-slogan">${esc(org.slogan)}</div>` : ''}
            </div>
        </div>`;

    // unlock nav items
    ['nav-projects','nav-tasks','nav-members','nav-activity log','nav-settings']
        .forEach(id => document.getElementById(id)?.classList.remove('disabled'));
}

// ── Logo preview ────────────────────────────────────────────────────
function previewLogo(e) {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = ev => {
        const preview = document.getElementById('upload-preview');
        preview.src = ev.target.result;
        preview.style.display = 'block';
        document.getElementById('upload-icon-wrap').style.display = 'none';
    };
    reader.readAsDataURL(file);
}

// ── Logout ──────────────────────────────────────────────────────────
function handleLogout() {
    if (confirm('Are you sure you want to logout?'))
        window.location.href = BASE + '/logout';
}