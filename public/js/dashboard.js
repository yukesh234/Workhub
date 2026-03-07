/**
 * public/js/dashboard.js
 * Drives the Dashboard page. Requires app.js loaded first.
 */

// ── State helpers ────────────────────────────────────────────────────
function showState(name) {
    ['loading', 'no-org', 'error'].forEach(s => {
        const el = document.getElementById(`main-${s}`);
        if (el) el.classList.remove('active');
    });

    const hasOrg = document.getElementById('main-has-org');
    if (hasOrg) hasOrg.style.display = 'none';

    if (name === 'has-org') {
        if (hasOrg) hasOrg.style.display = 'block';
    } else {
        const el = document.getElementById(`main-${name}`);
        if (el) el.classList.add('active');
    }
}

// ── Render org into the dashboard content area ───────────────────────
function renderDashboardOrg(org) {
    // Welcome subtitle
    const sub = document.getElementById('org-welcome-sub');
    if (sub) sub.textContent = `Here's your overview for ${org.name}.`;

    // Logo slot
    const slot = document.getElementById('org-logo-slot');
    if (slot) {
        slot.innerHTML = org.organization_logo
            ? `<img class="org-card-logo" src="${esc(org.organization_logo)}" alt="${esc(org.name)}">`
            : `<div class="org-card-logo-fallback">${esc(org.name.charAt(0).toUpperCase())}</div>`;
    }

    const name = document.getElementById('org-card-name');
    if (name) name.textContent = org.name;

    const slogan = document.getElementById('org-card-slogan');
    if (slogan) slogan.textContent = org.slogan || '';

    const since = document.getElementById('org-meta-since');
    if (since) since.textContent = formatDateShort(org.created_at);

    // Unlock quick action buttons once org exists
    ['qa-projects', 'qa-tasks', 'qa-members', 'qa-settings'].forEach(id => {
        document.getElementById(id)?.classList.remove('disabled');
    });

    showState('has-org');
}

// ── Fetch org on load ────────────────────────────────────────────────
async function fetchOrg() {
    showState('loading');
    setSidebarSkeleton();

    try {
        const res  = await fetch(BASE + '/api/organization', { credentials: 'same-origin' });
        if (!res.ok) throw new Error('Server error ' + res.status);
        const json = await res.json();

        if (json.success && json.data) {
            setSidebarOrg(json.data);
            renderDashboardOrg(json.data);
        } else {
            setSidebarNoOrg();
            showState('no-org');
        }
    } catch (err) {
        document.getElementById('error-msg').textContent =
            err.message || 'Failed to connect to the server.';
        showState('error');
        setSidebarNoOrg();
    }
}

// ── Create org form ──────────────────────────────────────────────────
document.getElementById('create-org-form')?.addEventListener('submit', async function (e) {
    e.preventDefault();

    const btn   = document.getElementById('submit-btn');
    const errEl = document.getElementById('form-error');
    errEl.style.display = 'none';
    btn.classList.add('loading');

    try {
        const res  = await fetch(BASE + '/organization/create', {
            method: 'POST',
            body: new FormData(this),
            credentials: 'same-origin',
        });
        const json = await res.json();

        if (json.success && json.data) {
            closeModal('modal-create-org');
            this.reset();

            // Reset logo preview
            const preview = document.getElementById('upload-preview');
            const icon    = document.getElementById('upload-icon-wrap');
            if (preview) { preview.src = ''; preview.style.display = 'none'; }
            if (icon)    icon.style.display = 'block';

            setSidebarOrg(json.data);
            renderDashboardOrg(json.data);
            showToast('Organization created successfully!');
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

// ── Boot ─────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', fetchOrg);