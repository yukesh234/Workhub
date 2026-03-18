/**
 * public/js/app.js
 * Shared utilities loaded on every admin page.
 * Requires: window.WH_BASE to be set in the page.
 */

const BASE = window.WH_BASE || '';

// ── XSS helper ──────────────────────────────────────────────────────
function esc(str) {
    return String(str)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

// ── Modal ───────────────────────────────────────────────────────────
function openModal(id)  { document.getElementById(id)?.classList.add('open'); }
function closeModal(id) { document.getElementById(id)?.classList.remove('open'); }

function closeModalOutside(e, id) {
    if (e.target.id === id) closeModal(id);
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape')
        document.querySelectorAll('.modal-backdrop.open')
                .forEach(m => m.classList.remove('open'));
});

// ── Toast notifications ─────────────────────────────────────────────
function showToast(message, type = 'success') {
    const existing = document.getElementById('wh-toast');
    if (existing) existing.remove();

    const toast = document.createElement('div');
    toast.id = 'wh-toast';
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <span class="toast-icon">${type === 'success' ? '✓' : '✕'}</span>
        <span>${esc(message)}</span>`;
    document.body.appendChild(toast);

    requestAnimationFrame(() => toast.classList.add('toast-show'));

    setTimeout(() => {
        toast.classList.remove('toast-show');
        toast.addEventListener('transitionend', () => toast.remove(), { once: true });
    }, 3500);
}

// ── Sidebar org block ────────────────────────────────────────────────
function setSidebarSkeleton() {
    const el = document.getElementById('sidebar-org');
    if (!el) return;
    el.innerHTML = `
        <div class="org-skeleton">
            <div class="sk-line short"></div>
            <div class="sk-line long"></div>
            <div class="sk-line tiny"></div>
        </div>`;
}

function setSidebarNoOrg() {
    const el = document.getElementById('sidebar-org');
    if (!el) return;
    // ✅ Fixed: was 'modal-create-org' — now matches the actual modal id="modalBackdrop"
    el.innerHTML = `
        <div class="sidebar-no-org">
            <p>No organization yet.<br>Create one to get started.</p>
            <button class="btn-sidebar-create" onclick="openModal('modalBackdrop')">
                + Create Organization
            </button>
        </div>`;
}

function setSidebarOrg(org) {
    const el = document.getElementById('sidebar-org');
    if (!el) return;

    const logo = org.organization_logo
        ? `<img class="org-logo-thumb" src="${esc(org.organization_logo)}" alt="${esc(org.name)}">`
        : `<div class="org-logo-fallback">${esc(org.name.charAt(0).toUpperCase())}</div>`;

    el.innerHTML = `
        <div class="org-block">
            ${logo}
            <div class="org-text">
                <div class="org-label">Organization</div>
                <div class="org-name">${esc(org.name)}</div>
                ${org.slogan ? `<div class="org-slogan">${esc(org.slogan)}</div>` : ''}
            </div>
        </div>`;

    // ✅ Fixed: was 'nav-activity log' (had a space)
    ['nav-projects', 'nav-tasks', 'nav-members', 'nav-activity', 'nav-settings']
        .forEach(id => document.getElementById(id)?.classList.remove('disabled'));
}

// ── Logo upload preview ──────────────────────────────────────────────
function previewLogo(e, previewId = 'upload-preview', iconId = 'upload-icon') {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = ev => {
        const preview = document.getElementById(previewId);
        if (preview) {
            preview.src = ev.target.result;
            preview.style.display = 'block';
        }
        const icon = document.getElementById(iconId);
        if (icon) icon.style.display = 'none';
    };
    reader.readAsDataURL(file);
}

// ── Logout ───────────────────────────────────────────────────────────
function handleLogout() {
    if (confirm('Are you sure you want to logout?'))
        window.location.href = BASE + '/logout';
}

// ── Date helpers ─────────────────────────────────────────────────────
function formatDate(dateStr) {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('en-US', {
        month: 'short', day: 'numeric', year: 'numeric'
    });
}

function formatDateShort(dateStr) {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('en-US', {
        month: 'short', year: 'numeric'
    });
}

// ── Set topbar date ──────────────────────────────────────────────────
const topbarDate = document.getElementById('topbarDate');
if (topbarDate) {
    topbarDate.textContent = new Date().toLocaleDateString('en-US', {
        weekday: 'long', month: 'long', day: 'numeric'
    });
}

// ── Shared org fetch (sidebar only — used by non-dashboard pages) ────
async function loadSidebarOrg() {
    setSidebarSkeleton();
    try {
        const res  = await fetch(BASE + '/api/organization', { credentials: 'same-origin' });
        const json = await res.json();
        if (json.success && json.data) {
            setSidebarOrg(json.data);
            return json.data;
        } else {
            setSidebarNoOrg();
            return null;
        }
    } catch {
        setSidebarNoOrg();
        return null;
    }
}