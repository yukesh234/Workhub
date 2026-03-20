<?php
// views/Settings.php
$basePath     = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$adminEmail   = $_SESSION['admin_email'] ?? 'admin@workhub.com';
$adminInitial = strtoupper(substr($adminEmail, 0, 1));
$adminHandle  = explode('@', $adminEmail)[0];
$activePage   = 'settings';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings — WorkHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $basePath ?>/css/dashboard.css">
    <style>
        .settings-layout { display: grid; grid-template-columns: 220px 1fr; gap: 28px; align-items: start; }

        /* ── Left nav ── */
        .settings-nav { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow-sm); position: sticky; top: 80px; }
        .settings-nav-item {
            display: flex; align-items: center; gap: 10px; padding: 12px 18px;
            font-size: 13px; font-weight: 500; color: var(--text-secondary);
            cursor: pointer; border: none; background: none; width: 100%;
            text-align: left; border-bottom: 1px solid var(--border);
            transition: all var(--transition);
        }
        .settings-nav-item:last-child { border-bottom: none; }
        .settings-nav-item:hover  { background: var(--surface-2); color: var(--text-primary); }
        .settings-nav-item.active { background: var(--brand-pale); color: var(--brand); font-weight: 600; }
        .settings-nav-item svg    { width: 16px; height: 16px; stroke: currentColor; flex-shrink: 0; }

        /* ── Panels ── */
        .settings-panel { display: none; }
        .settings-panel.active { display: block; }

        .settings-card {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: var(--radius); box-shadow: var(--shadow-sm);
            margin-bottom: 20px; overflow: hidden;
        }
        .settings-card-hd {
            padding: 18px 24px; border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
        }
        .settings-card-hd h3 { font-family: 'Playfair Display', serif; font-size: 16px; color: var(--text-primary); }
        .settings-card-hd p  { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
        .settings-card-body  { padding: 24px; }

        /* ── Logo section ── */
        .logo-section { display: flex; align-items: center; gap: 24px; }
        .logo-preview {
            width: 80px; height: 80px; border-radius: 16px;
            background: var(--brand-pale2); border: 2px solid var(--border);
            display: flex; align-items: center; justify-content: center;
            overflow: hidden; flex-shrink: 0; position: relative;
        }
        .logo-preview img  { width: 100%; height: 100%; object-fit: cover; }
        .logo-preview span { font-family: 'Playfair Display', serif; font-size: 28px; font-weight: 700; color: var(--brand); }
        .logo-actions { display: flex; flex-direction: column; gap: 8px; }
        .logo-actions p { font-size: 12px; color: var(--text-muted); line-height: 1.5; }

        /* ── Form fields ── */
        .settings-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-field        { margin-bottom: 18px; }
        .form-field label  { display: block; font-size: 12.5px; font-weight: 600; color: var(--text-primary); margin-bottom: 6px; text-transform: uppercase; letter-spacing: .4px; }
        .form-field input, .form-field textarea {
            width: 100%; padding: 10px 14px; border: 1.5px solid var(--border);
            border-radius: var(--radius-sm); font-family: 'DM Sans', sans-serif;
            font-size: 14px; color: var(--text-primary); background: var(--surface-2);
            outline: none; transition: border-color var(--transition);
        }
        .form-field input:focus, .form-field textarea:focus { border-color: var(--brand); background: var(--surface); }
        .form-field textarea { resize: vertical; min-height: 80px; }
        .form-field .field-hint { font-size: 11px; color: var(--text-muted); margin-top: 4px; }
        .form-footer { display: flex; align-items: center; justify-content: flex-end; gap: 10px; padding-top: 4px; }

        /* ── Stat chips ── */
        .org-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 0; }
        .org-stat  { background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 14px 16px; text-align: center; }
        .org-stat-val { font-family: 'Playfair Display', serif; font-size: 22px; font-weight: 700; color: var(--text-primary); }
        .org-stat-lbl { font-size: 11px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: .4px; margin-top: 2px; }

        /* ── Password strength ── */
        .pw-strength-bar { height: 4px; background: var(--border); border-radius: 2px; margin-top: 8px; overflow: hidden; }
        .pw-strength-fill { height: 100%; border-radius: 2px; transition: width .3s ease, background .3s ease; width: 0; }
        .pw-reqs { display: grid; grid-template-columns: 1fr 1fr; gap: 4px 12px; margin-top: 10px; }
        .pw-req  { font-size: 11.5px; color: var(--text-muted); display: flex; align-items: center; gap: 5px; }
        .pw-req.met  { color: #1a8a5c; }
        .pw-req::before { content: '○'; font-size: 10px; }
        .pw-req.met::before { content: '●'; }
        .pw-eye {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer; color: var(--text-muted);
            padding: 2px;
        }
        .pw-eye:hover { color: var(--text-primary); }
        .pw-eye svg { width: 16px; height: 16px; stroke: currentColor; pointer-events: none; }
        .field-relative { position: relative; }
        .field-relative input { padding-right: 40px; }

        /* ── Danger zone ── */
        .danger-card { border-color: #fecaca !important; }
        .danger-card .settings-card-hd { background: #fff5f5; border-bottom-color: #fecaca; }
        .danger-card .settings-card-hd h3 { color: #dc2626; }
        .danger-item { display: flex; align-items: center; justify-content: space-between; gap: 20px; padding: 14px 0; border-bottom: 1px solid var(--border); }
        .danger-item:last-child { border-bottom: none; padding-bottom: 0; }
        .danger-item-info h4  { font-size: 14px; font-weight: 600; color: var(--text-primary); }
        .danger-item-info p   { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
        .btn-danger {
            padding: 8px 18px; border: 1.5px solid #dc2626; border-radius: var(--radius-sm);
            background: transparent; color: #dc2626; font-family: 'DM Sans', sans-serif;
            font-size: 13px; font-weight: 600; cursor: pointer; white-space: nowrap;
            transition: all var(--transition); flex-shrink: 0;
        }
        .btn-danger:hover { background: #dc2626; color: #fff; }

        /* ── Success inline msg ── */
        .save-success { display:none; font-size:12px; color:#1a8a5c; font-weight:600; }
        .save-success.show { display:inline-flex; align-items:center; gap:4px; }

        /* ── Confirm dialog overlay ── */
        .confirm-overlay {
            position: fixed; inset: 0; background: rgba(26,18,24,.55);
            backdrop-filter: blur(4px); z-index: 300; display: flex;
            align-items: center; justify-content: center;
            opacity: 0; pointer-events: none; transition: opacity var(--transition);
        }
        .confirm-overlay.open { opacity: 1; pointer-events: all; }
        .confirm-box {
            background: var(--surface); border-radius: 16px; padding: 32px;
            max-width: 400px; width: 90%; box-shadow: var(--shadow-lg);
            transform: scale(.96); transition: transform var(--transition);
        }
        .confirm-overlay.open .confirm-box { transform: scale(1); }
        .confirm-box h3 { font-family: 'Playfair Display', serif; font-size: 18px; color: #dc2626; margin-bottom: 8px; }
        .confirm-box p  { font-size: 14px; color: var(--text-secondary); line-height: 1.6; margin-bottom: 20px; }
        .confirm-input-wrap { margin-bottom: 20px; }
        .confirm-input-wrap label { font-size: 12px; font-weight: 600; color: var(--text-primary); display: block; margin-bottom: 6px; }
        .confirm-input-wrap input { width: 100%; padding: 10px 14px; border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-family: 'DM Sans', sans-serif; font-size: 14px; outline: none; transition: border-color var(--transition); }
        .confirm-input-wrap input:focus { border-color: #dc2626; }
        .confirm-actions { display: flex; gap: 10px; justify-content: flex-end; }

        @media(max-width: 900px) {
            .settings-layout { grid-template-columns: 1fr; }
            .settings-nav { position: static; display: flex; overflow-x: auto; }
            .settings-nav-item { border-bottom: none; border-right: 1px solid var(--border); white-space: nowrap; }
            .settings-form-row { grid-template-columns: 1fr; }
            .org-stats { grid-template-columns: repeat(2,1fr); }
        }
    </style>
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/../partials/sidebar.php'; ?>

    <div class="main">
        <header class="topbar">
            <div class="topbar-title">Settings</div>
            <div class="topbar-right">
                <span class="topbar-date" id="topbarDate"></span>
                <div class="topbar-avatar"><?= htmlspecialchars($adminInitial) ?></div>
            </div>
        </header>

        <main class="content">

            <div class="page-header" style="margin-bottom:24px">
                <h1>Settings</h1>
                <p>Manage your organization, account, and workspace preferences.</p>
            </div>

            <div class="settings-layout">

                <!-- ── Left nav ── -->
                <nav class="settings-nav">
                    <button class="settings-nav-item active" data-tab="organization">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>
                        </svg>
                        Organization
                    </button>
                    <button class="settings-nav-item" data-tab="account">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                        </svg>
                        Account
                    </button>
                    <button class="settings-nav-item" data-tab="danger">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                            <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                        </svg>
                        Danger Zone
                    </button>
                </nav>

                <!-- ── Right panels ── -->
                <div>

                    <!-- ═══════ ORGANIZATION TAB ═══════ -->
                    <div class="settings-panel active" id="tab-organization">

                        <!-- Overview stats -->
                        <div class="settings-card">
                            <div class="settings-card-hd">
                                <div>
                                    <h3>Organization Overview</h3>
                                    <p>Your workspace at a glance</p>
                                </div>
                            </div>
                            <div class="settings-card-body">
                                <div class="org-stats">
                                    <div class="org-stat"><div class="org-stat-val" id="ov-members">—</div><div class="org-stat-lbl">Members</div></div>
                                    <div class="org-stat"><div class="org-stat-val" id="ov-projects">—</div><div class="org-stat-lbl">Projects</div></div>
                                    <div class="org-stat"><div class="org-stat-val" id="ov-tasks">—</div><div class="org-stat-lbl">Tasks</div></div>
                                    <div class="org-stat"><div class="org-stat-val" id="ov-since">—</div><div class="org-stat-lbl">Since</div></div>
                                </div>
                            </div>
                        </div>

                        <!-- Logo -->
                        <div class="settings-card">
                            <div class="settings-card-hd">
                                <div>
                                    <h3>Organization Logo</h3>
                                    <p>JPG, PNG or WEBP · max 5MB</p>
                                </div>
                            </div>
                            <div class="settings-card-body">
                                <div class="logo-section">
                                    <div class="logo-preview" id="logo-preview-box">
                                        <span id="logo-fallback-initial"></span>
                                    </div>
                                    <div class="logo-actions">
                                        <div style="display:flex;gap:8px;flex-wrap:wrap">
                                            <label class="btn-primary" style="padding:8px 16px;font-size:13px;cursor:pointer">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                                    <polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>
                                                </svg>
                                                Upload Logo
                                                <input type="file" id="logo-file-input" accept="image/jpeg,image/png,image/webp" style="display:none" onchange="previewAndUploadLogo(this)">
                                            </label>
                                            <button class="btn-ghost" id="btn-remove-logo" style="padding:8px 16px;font-size:13px;display:none" onclick="removeLogo()">
                                                Remove
                                            </button>
                                        </div>
                                        <p id="logo-upload-status" style="font-size:12px;color:var(--text-muted)">PNG, JPG or WEBP — max 5 MB</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Info -->
                        <div class="settings-card">
                            <div class="settings-card-hd">
                                <div><h3>Organization Details</h3><p>Update your workspace name and description</p></div>
                            </div>
                            <div class="settings-card-body">
                                <form id="form-org-info">
                                    <div class="form-field">
                                        <label>Organization Name <span style="color:var(--brand)">*</span></label>
                                        <input type="text" id="org-name" name="name" maxlength="255" placeholder="Acme Corp" required>
                                    </div>
                                    <div class="form-field">
                                        <label>Slogan <span style="font-weight:400;color:var(--text-muted)">(optional)</span></label>
                                        <input type="text" id="org-slogan" name="slogan" maxlength="255" placeholder="Building the future, together">
                                    </div>
                                    <div class="form-footer">
                                        <span class="save-success" id="org-info-saved">✓ Saved</span>
                                        <button type="submit" class="btn-primary" id="btn-save-org-info" style="padding:9px 22px;font-size:13px">
                                            <div class="btn-spin"></div>
                                            <span class="btn-text">Save Changes</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                    </div><!-- /#tab-organization -->


                    <!-- ═══════ ACCOUNT TAB ═══════ -->
                    <div class="settings-panel" id="tab-account">

                        <!-- Admin profile -->
                        <div class="settings-card">
                            <div class="settings-card-hd">
                                <div><h3>Admin Profile</h3><p>Your administrator account details</p></div>
                            </div>
                            <div class="settings-card-body">
                                <div style="display:flex;align-items:center;gap:16px;padding:4px 0 20px;border-bottom:1px solid var(--border);margin-bottom:20px">
                                    <div style="width:52px;height:52px;border-radius:50%;background:var(--brand);color:#fff;font-family:'Playfair Display',serif;font-size:20px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                        <?= htmlspecialchars($adminInitial) ?>
                                    </div>
                                    <div>
                                        <div style="font-size:15px;font-weight:700;color:var(--text-primary)"><?= htmlspecialchars($adminHandle) ?></div>
                                        <div style="font-size:12px;color:var(--text-muted)"><?= htmlspecialchars($adminEmail) ?></div>
                                        <div style="margin-top:4px"><span style="font-size:11px;font-weight:600;background:var(--brand-pale);color:var(--brand);padding:2px 8px;border-radius:20px">Administrator</span></div>
                                    </div>
                                </div>
                                <div class="form-field" style="margin-bottom:0">
                                    <label>Email Address</label>
                                    <input type="text" value="<?= htmlspecialchars($adminEmail) ?>" disabled style="background:var(--surface-2);color:var(--text-muted);cursor:not-allowed">
                                    <div class="field-hint">Email cannot be changed. It is your login identifier.</div>
                                </div>
                            </div>
                        </div>

                        <!-- Change password -->
                        <div class="settings-card">
                            <div class="settings-card-hd">
                                <div><h3>Change Password</h3><p>Must be at least 8 characters</p></div>
                            </div>
                            <div class="settings-card-body">
                                <div class="form-error" id="pw-error" style="display:none;background:#fef2f2;border:1px solid #fecaca;border-radius:var(--radius-sm);padding:10px 14px;font-size:13px;color:#b91c1c;margin-bottom:16px"></div>
                                <form id="form-change-pw">
                                    <div class="form-field">
                                        <label>Current Password</label>
                                        <div class="field-relative">
                                            <input type="password" id="pw-current" placeholder="Your current password" required>
                                            <button type="button" class="pw-eye" onclick="toggleEye('pw-current',this)">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="form-field">
                                        <label>New Password</label>
                                        <div class="field-relative">
                                            <input type="password" id="pw-new" placeholder="New password" required oninput="checkPwStrength(this.value)">
                                            <button type="button" class="pw-eye" onclick="toggleEye('pw-new',this)">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                            </button>
                                        </div>
                                        <div class="pw-strength-bar"><div class="pw-strength-fill" id="pw-strength-fill"></div></div>
                                        <div class="pw-reqs">
                                            <div class="pw-req" id="req-len">At least 8 characters</div>
                                            <div class="pw-req" id="req-upper">Uppercase letter</div>
                                            <div class="pw-req" id="req-num">Number</div>
                                            <div class="pw-req" id="req-special">Special character</div>
                                        </div>
                                    </div>
                                    <div class="form-field">
                                        <label>Confirm New Password</label>
                                        <div class="field-relative">
                                            <input type="password" id="pw-confirm" placeholder="Repeat new password" required>
                                            <button type="button" class="pw-eye" onclick="toggleEye('pw-confirm',this)">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="form-footer">
                                        <span class="save-success" id="pw-saved">✓ Password updated</span>
                                        <button type="submit" class="btn-primary" id="btn-change-pw" style="padding:9px 22px;font-size:13px">
                                            <div class="btn-spin"></div>
                                            <span class="btn-text">Update Password</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                    </div><!-- /#tab-account -->


                    <!-- ═══════ DANGER ZONE TAB ═══════ -->
                    <div class="settings-panel" id="tab-danger">
                        <div class="settings-card danger-card">
                            <div class="settings-card-hd">
                                <div><h3>Danger Zone</h3><p>These actions are permanent and cannot be undone</p></div>
                            </div>
                            <div class="settings-card-body">

                                <div class="danger-item">
                                    <div class="danger-item-info">
                                        <h4>Remove Organization Logo</h4>
                                        <p>Deletes the current logo from cloud storage and resets it to initials.</p>
                                    </div>
                                    <button class="btn-danger" onclick="removeLogo()">Remove Logo</button>
                                </div>

                                <div class="danger-item">
                                    <div class="danger-item-info">
                                        <h4>Delete All Members</h4>
                                        <p>Permanently removes all team members from your organization. Projects and tasks remain.</p>
                                    </div>
                                    <button class="btn-danger" onclick="openConfirm('deleteMembers')">Delete Members</button>
                                </div>

                                <div class="danger-item">
                                    <div class="danger-item-info">
                                        <h4>Delete Organization</h4>
                                        <p>Permanently deletes your organization, all projects, tasks, members, and data. Your admin account remains.</p>
                                    </div>
                                    <button class="btn-danger" onclick="openConfirm('deleteOrg')">Delete Organization</button>
                                </div>

                            </div>
                        </div>
                    </div><!-- /#tab-danger -->

                </div>
            </div>

        </main>
    </div>
</div>

<!-- ══ Confirm dialog ══ -->
<div class="confirm-overlay" id="confirm-overlay">
    <div class="confirm-box">
        <h3 id="confirm-title">Are you sure?</h3>
        <p  id="confirm-desc"></p>
        <div class="confirm-input-wrap" id="confirm-input-wrap" style="display:none">
            <label id="confirm-input-label"></label>
            <input type="text" id="confirm-input" placeholder="">
        </div>
        <div class="confirm-actions">
            <button class="btn-ghost" onclick="closeConfirm()" style="padding:9px 18px;font-size:13px">Cancel</button>
            <button class="btn-danger" id="confirm-ok-btn" onclick="executeConfirm()">
                <span id="confirm-ok-text">Confirm</span>
            </button>
        </div>
    </div>
</div>

<script> window.WH_BASE = '<?= $basePath ?>'; </script>
<script src="<?= $basePath ?>/js/app.js"></script>
<script src="<?= $basePath ?>/js/settings.js"></script>
</body>
</html>