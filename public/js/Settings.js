/**
 * public/js/settings.js
 * Drives the Settings page. Requires app.js (BASE, esc, showToast, loadSidebarOrg).
 */

let currentOrg     = null;
let confirmAction  = null;

// ── Boot ─────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    loadSidebarOrg();
    loadOrgData();

    // Tab navigation
    document.querySelectorAll('.settings-nav-item').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.settings-nav-item').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.settings-panel').forEach(p => p.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById(`tab-${btn.dataset.tab}`).classList.add('active');
        });
    });

    // Forms
    document.getElementById('form-org-info').addEventListener('submit', saveOrgInfo);
    document.getElementById('form-change-pw').addEventListener('submit', changePassword);

    // Close confirm on backdrop click
    document.getElementById('confirm-overlay').addEventListener('click', e => {
        if (e.target.id === 'confirm-overlay') closeConfirm();
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeConfirm();
    });
});

// ── Load org data ─────────────────────────────────────────────────────
async function loadOrgData() {
    try {
        const res  = await fetch(BASE + '/api/organization', { credentials: 'same-origin' });
        const json = await res.json();
        if (!json.success || !json.data) return;

        currentOrg = json.data;
        populateOrgForm(currentOrg);
        loadOrgStats();
    } catch (err) {
        console.warn('Failed to load org:', err.message);
    }
}

function populateOrgForm(org) {
    document.getElementById('org-name').value   = org.name   || '';
    document.getElementById('org-slogan').value = org.slogan || '';

    const box     = document.getElementById('logo-preview-box');
    const initial = document.getElementById('logo-fallback-initial');
    const removeBtn = document.getElementById('btn-remove-logo');

    if (org.organization_logo) {
        box.innerHTML = `<img src="${esc(org.organization_logo)}" alt="Logo" style="width:100%;height:100%;object-fit:cover">`;
        removeBtn.style.display = 'inline-flex';
    } else {
        box.innerHTML = `<span style="font-family:'Playfair Display',serif;font-size:28px;font-weight:700;color:var(--brand)">${esc((org.name||'?')[0].toUpperCase())}</span>`;
        removeBtn.style.display = 'none';
    }
}

async function loadOrgStats() {
    try {
        const res  = await fetch(BASE + '/api/analytics/admin', { credentials: 'same-origin' });
        const json = await res.json();
        if (!json.success) return;
        const s = json.data.summary;

        setText('ov-members',  s.total_members  ?? '0');
        setText('ov-projects', s.total_projects ?? '0');
        setText('ov-tasks',    s.total_tasks    ?? '0');

        if (currentOrg?.created_at) {
            setText('ov-since', new Date(currentOrg.created_at).toLocaleDateString('en-US', { month:'short', year:'numeric' }));
        }
    } catch {}
}

// ── Save org info (name + slogan) ─────────────────────────────────────
async function saveOrgInfo(e) {
    e.preventDefault();
    const btn  = document.getElementById('btn-save-org-info');
    const name = document.getElementById('org-name').value.trim();
    const slogan = document.getElementById('org-slogan').value.trim();

    if (!name) { showToast('Organization name is required', 'error'); return; }

    btn.classList.add('loading');
    try {
        const fd = new FormData();
        fd.set('name',   name);
        fd.set('slogan', slogan);

        const res  = await fetch(BASE + '/api/organization/update', {
            method: 'POST',
            credentials: 'same-origin',
            body: fd,
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);

        currentOrg.name   = name;
        currentOrg.slogan = slogan;

        // Update sidebar org name
        setSidebarOrg(currentOrg);

        const saved = document.getElementById('org-info-saved');
        saved.classList.add('show');
        setTimeout(() => saved.classList.remove('show'), 3000);
        showToast('Organization updated!');
    } catch (err) {
        showToast(err.message, 'error');
    } finally {
        btn.classList.remove('loading');
    }
}

// ── Logo upload ───────────────────────────────────────────────────────
async function previewAndUploadLogo(input) {
    const file = input.files[0];
    if (!file) return;

    const maxMB = 5;
    if (file.size > maxMB * 1024 * 1024) {
        showToast(`Logo must be under ${maxMB}MB`, 'error');
        input.value = '';
        return;
    }

    // Show preview immediately
    const reader = new FileReader();
    reader.onload = ev => {
        document.getElementById('logo-preview-box').innerHTML =
            `<img src="${ev.target.result}" alt="Logo" style="width:100%;height:100%;object-fit:cover">`;
    };
    reader.readAsDataURL(file);

    // Upload
    const status = document.getElementById('logo-upload-status');
    status.textContent = 'Uploading…';
    status.style.color = 'var(--brand)';

    try {
        const fd = new FormData();
        fd.set('logo', file);

        const res  = await fetch(BASE + '/api/organization/logo', {
            method: 'POST',
            credentials: 'same-origin',
            body: fd,
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);

        currentOrg.organization_logo = json.data.organization_logo;
        setSidebarOrg(currentOrg);

        document.getElementById('btn-remove-logo').style.display = 'inline-flex';
        status.textContent = 'Logo updated ✓';
        status.style.color = '#1a8a5c';
        setTimeout(() => {
            status.textContent = 'PNG, JPG or WEBP — max 5 MB';
            status.style.color = 'var(--text-muted)';
        }, 3000);
        showToast('Logo updated!');
    } catch (err) {
        status.textContent = 'Upload failed';
        status.style.color = '#dc2626';
        showToast(err.message, 'error');
    }

    input.value = '';
}

// ── Remove logo ───────────────────────────────────────────────────────
async function removeLogo() {
    if (!currentOrg?.organization_logo) {
        showToast('No logo to remove', 'error');
        return;
    }

    try {
        const res  = await fetch(BASE + '/api/organization/logo', {
            method: 'DELETE',
            credentials: 'same-origin',
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);

        currentOrg.organization_logo = null;

        const initial = (currentOrg.name || '?')[0].toUpperCase();
        document.getElementById('logo-preview-box').innerHTML =
            `<span style="font-family:'Playfair Display',serif;font-size:28px;font-weight:700;color:var(--brand)">${initial}</span>`;
        document.getElementById('btn-remove-logo').style.display = 'none';

        setSidebarOrg(currentOrg);
        showToast('Logo removed');
    } catch (err) {
        showToast(err.message, 'error');
    }
}

// ── Change admin password ─────────────────────────────────────────────
async function changePassword(e) {
    e.preventDefault();
    const btn     = document.getElementById('btn-change-pw');
    const errEl   = document.getElementById('pw-error');
    const current = document.getElementById('pw-current').value;
    const newPw   = document.getElementById('pw-new').value;
    const confirm = document.getElementById('pw-confirm').value;

    errEl.style.display = 'none';

    if (newPw !== confirm) {
        errEl.textContent = 'New passwords do not match.';
        errEl.style.display = 'block';
        return;
    }
    if (newPw.length < 8) {
        errEl.textContent = 'Password must be at least 8 characters.';
        errEl.style.display = 'block';
        return;
    }

    btn.classList.add('loading');
    try {
        const res  = await fetch(BASE + '/api/admin/change-password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ current_password: current, new_password: newPw, confirm_password: confirm }),
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);

        document.getElementById('form-change-pw').reset();
        resetStrength();

        const saved = document.getElementById('pw-saved');
        saved.classList.add('show');
        setTimeout(() => saved.classList.remove('show'), 3000);
        showToast('Password updated successfully!');
    } catch (err) {
        errEl.textContent   = err.message;
        errEl.style.display = 'block';
    } finally {
        btn.classList.remove('loading');
    }
}

// ── Password strength ─────────────────────────────────────────────────
function checkPwStrength(val) {
    const reqs = {
        len:     val.length >= 8,
        upper:   /[A-Z]/.test(val),
        num:     /[0-9]/.test(val),
        special: /[^A-Za-z0-9]/.test(val),
    };

    Object.entries(reqs).forEach(([key, met]) => {
        document.getElementById(`req-${key}`)?.classList.toggle('met', met);
    });

    const score = Object.values(reqs).filter(Boolean).length;
    const fill  = document.getElementById('pw-strength-fill');
    const colors = ['', '#dc2626', '#f97316', '#f59e0b', '#1a8a5c'];
    fill.style.width      = `${score * 25}%`;
    fill.style.background = colors[score] || '';
}

function resetStrength() {
    ['len','upper','num','special'].forEach(k => document.getElementById(`req-${k}`)?.classList.remove('met'));
    const fill = document.getElementById('pw-strength-fill');
    fill.style.width = '0';
}

function toggleEye(inputId, btn) {
    const input = document.getElementById(inputId);
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    btn.querySelector('svg').style.opacity = isHidden ? '0.4' : '1';
}

// ── Confirm dialog ────────────────────────────────────────────────────
const confirmConfigs = {
    deleteOrg: {
        title:       'Delete Organization',
        desc:        'This will permanently delete your organization, all projects, tasks, members, and uploaded files. This CANNOT be undone.',
        inputLabel:  'Type your organization name to confirm:',
        inputHint:   '',
        btnText:     'Delete Organization',
        validate:    (val) => val.trim() === (currentOrg?.name || '').trim(),
        invalidMsg:  'Organization name does not match.',
    },
    deleteMembers: {
        title:    'Delete All Members',
        desc:     'This will permanently remove all members from your organization. Projects and tasks will remain but will have no assignees.',
        btnText:  'Delete All Members',
        validate: () => true,
    },
};

function openConfirm(action) {
    confirmAction = action;
    const cfg = confirmConfigs[action];
    if (!cfg) return;

    document.getElementById('confirm-title').textContent   = cfg.title;
    document.getElementById('confirm-desc').textContent    = cfg.desc;
    document.getElementById('confirm-ok-text').textContent = cfg.btnText;

    const wrap  = document.getElementById('confirm-input-wrap');
    const input = document.getElementById('confirm-input');
    if (cfg.inputLabel) {
        document.getElementById('confirm-input-label').textContent = cfg.inputLabel;
        input.placeholder = cfg.inputHint || '';
        input.value = '';
        wrap.style.display = 'block';
        setTimeout(() => input.focus(), 100);
    } else {
        wrap.style.display = 'none';
    }

    document.getElementById('confirm-overlay').classList.add('open');
}

function closeConfirm() {
    document.getElementById('confirm-overlay').classList.remove('open');
    document.getElementById('confirm-input').value = '';
    confirmAction = null;
}

async function executeConfirm() {
    if (!confirmAction) return;
    const cfg = confirmConfigs[confirmAction];
    const val = document.getElementById('confirm-input').value;

    if (!cfg.validate(val)) {
        showToast(cfg.invalidMsg || 'Validation failed', 'error');
        return;
    }

    closeConfirm();

    if (confirmAction === 'deleteOrg') {
        await deleteOrganization();
    } else if (confirmAction === 'deleteMembers') {
        await deleteAllMembers();
    }
}

async function deleteOrganization() {
    try {
        const res  = await fetch(BASE + '/api/organization', {
            method: 'DELETE',
            credentials: 'same-origin',
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        showToast('Organization deleted. Redirecting…');
        setTimeout(() => { window.location.href = BASE + '/dashboard'; }, 1500);
    } catch (err) {
        showToast(err.message, 'error');
    }
}

async function deleteAllMembers() {
    try {
        const res  = await fetch(BASE + '/api/members/all', {
            method: 'DELETE',
            credentials: 'same-origin',
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        loadOrgStats();
        showToast('All members removed.');
    } catch (err) {
        showToast(err.message, 'error');
    }
}

// ── Helpers ───────────────────────────────────────────────────────────
function setText(id, val) {
    const el = document.getElementById(id);
    if (el) el.textContent = val;
}