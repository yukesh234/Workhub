// ─── Config ────────────────────────────────────────────────────────────────
const BASE       = window.WH_BASE ?? '';
const API_ORG    = BASE + '/api/organization';    // GET  → { success: bool, data: org|null }
const API_CREATE = BASE + '/organization/create'; // POST → { success: bool, data: org } | { success: false, message: string }

// ─── Date ──────────────────────────────────────────────────────────────────
document.getElementById('topbarDate').textContent =
    new Date().toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' });

// ─── State management ──────────────────────────────────────────────────────
function showState(name) {
    document.querySelectorAll('.state').forEach(el => el.classList.remove('active'));
    const hasOrg = document.getElementById('main-has-org');
    hasOrg.style.display = 'none';
    hasOrg.classList.remove('active');

    if (name === 'has-org') {
        hasOrg.style.display = 'block';
        hasOrg.classList.add('active');
    } else {
        const el = document.getElementById('main-' + name);
        if (el) el.classList.add('active');
    }
}

// ─── Sidebar helpers ───────────────────────────────────────────────────────
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
            <button class="btn-sidebar-create" onclick="openModal()">+ Create Organization</button>
        </div>`;
}

// ─── Fetch org on load ─────────────────────────────────────────────────────
async function fetchOrg() {
    showState('loading');
    setSidebarSkeleton();

    try {
        const res  = await fetch(API_ORG, { credentials: 'same-origin' });
        if (!res.ok) throw new Error('Server responded with ' + res.status);
        const json = await res.json();

        if (json.success && json.data) {
            renderOrg(json.data);
        } else {
            renderNoOrg();
        }
    } catch (err) {
        console.error(err);
        document.getElementById('error-msg').textContent =
            err.message || 'Failed to connect to the server.';
        showState('error');
        setSidebarNoOrg();
    }
}

// ─── Render: has org ───────────────────────────────────────────────────────
function renderOrg(org) {
    // Sidebar org block
    const logoHtml = org.organization_logo
        ? `<img class="org-logo-thumb" src="${esc(org.organization_logo)}" alt="${esc(org.name)}">`
        : `<div class="org-logo-fallback">${esc(org.name.charAt(0).toUpperCase())}</div>`;

    document.getElementById('sidebar-org').innerHTML = `
        <div class="org-block">
            ${logoHtml}
            <div class="org-text">
                <div class="org-label">Organization</div>
                <div class="org-name">${esc(org.name)}</div>
                ${org.slogan ? `<div class="org-slogan">${esc(org.slogan)}</div>` : ''}
            </div>
        </div>`;

    // Enable all nav items
    ['nav-projects', 'nav-tasks', 'nav-members', 'nav-activity', 'nav-settings']
        .forEach(id => document.getElementById(id)?.classList.remove('disabled'));

    // Welcome subtitle
    document.getElementById('org-welcome-sub').textContent = `Here's your overview for ${org.name}.`;

    // Org logo slot
    const slot = document.getElementById('org-logo-slot');
    slot.innerHTML = org.organization_logo
        ? `<img class="org-card-logo" src="${esc(org.organization_logo)}" alt="${esc(org.name)}">`
        : `<div class="org-card-logo-fallback">${esc(org.name.charAt(0).toUpperCase())}</div>`;

    // Card info
    document.getElementById('org-card-name').textContent   = org.name;
    document.getElementById('org-card-slogan').textContent = org.slogan ?? '';

    document.getElementById('org-meta-since').textContent = org.created_at
        ? new Date(org.created_at).toLocaleDateString('en-US', { month: 'short', year: 'numeric' })
        : '—';

    showState('has-org');
}

// ─── Render: no org ────────────────────────────────────────────────────────
function renderNoOrg() {
    setSidebarNoOrg();
    showState('no-org');
}

// ─── Modal ─────────────────────────────────────────────────────────────────
function openModal()  { document.getElementById('modalBackdrop').classList.add('open'); }
function closeModal() { document.getElementById('modalBackdrop').classList.remove('open'); }
function closeModalOutside(e) { if (e.target.id === 'modalBackdrop') closeModal(); }
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

// ─── Logo preview ──────────────────────────────────────────────────────────
function previewLogo(e) {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = ev => {
        const preview = document.getElementById('upload-preview');
        preview.src = ev.target.result;
        preview.style.display = 'block';
        document.getElementById('upload-icon').style.display = 'none';
    };
    reader.readAsDataURL(file);
}

// ─── Form submit ───────────────────────────────────────────────────────────
document.getElementById('create-org-form').addEventListener('submit', async function (e) {
    e.preventDefault();

    const btn   = document.getElementById('submit-btn');
    const errEl = document.getElementById('form-error');
    errEl.style.display = 'none';
    btn.classList.add('loading');

    try {
        const res  = await fetch(API_CREATE, {
            method: 'POST',
            body: new FormData(this),
            credentials: 'same-origin',
        });
        const json = await res.json();

        if (json.success && json.data) {
            closeModal();
            this.reset();
            document.getElementById('upload-preview').style.display = 'none';
            document.getElementById('upload-icon').style.display    = 'block';
            renderOrg(json.data);
        } else {
            throw new Error(json.message || 'Failed to create organization.');
        }
    } catch (err) {
        errEl.textContent   = err.message;
        errEl.style.display = 'block';
    } finally {
        btn.classList.remove('loading');
    }
});

// ─── Logout ────────────────────────────────────────────────────────────────
function handleLogout() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = BASE + '/logout';
    }
}

// ─── XSS helper ────────────────────────────────────────────────────────────
function esc(str) {
    return String(str)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

// ─── Boot ──────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', fetchOrg);