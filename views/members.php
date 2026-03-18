<?php
// views/Members.php
$basePath     = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$adminEmail   = $_SESSION['admin_email'] ?? 'admin@workhub.com';
$adminInitial = strtoupper(substr($adminEmail, 0, 1));
$activePage   = 'members';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Members — WorkHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $basePath ?>/css/dashboard.css">
    <style>
        /* ── Page header ── */
        .members-header {
            display: flex; align-items: flex-start; justify-content: space-between;
            margin-bottom: 28px; gap: 16px; flex-wrap: wrap;
        }
        .members-header h1 { font-family: 'Playfair Display', serif; font-size: 28px; color: var(--text-primary); margin-bottom: 4px; }
        .members-header p  { color: var(--text-secondary); font-size: 14px; }

        /* ── Toolbar ── */
        .members-toolbar {
            display: flex; align-items: center; gap: 10px;
            margin-bottom: 22px; flex-wrap: wrap;
        }
        .role-filter-btn {
            padding: 7px 16px; border-radius: 20px;
            border: 1.5px solid var(--border); background: var(--surface);
            font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 500;
            color: var(--text-secondary); cursor: pointer; transition: all var(--transition);
        }
        .role-filter-btn:hover  { border-color: var(--brand); color: var(--brand); }
        .role-filter-btn.active { background: var(--brand); color: #fff; border-color: var(--brand); }

        .members-search { margin-left: auto; position: relative; }
        .members-search input {
            padding: 8px 14px 8px 36px; border: 1.5px solid var(--border); border-radius: 20px;
            font-family: 'DM Sans', sans-serif; font-size: 13px; color: var(--text-primary);
            background: var(--surface); outline: none; width: 220px; transition: border-color var(--transition);
        }
        .members-search input:focus { border-color: var(--brand); }
        .members-search svg { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); width: 15px; height: 15px; stroke: var(--text-muted); pointer-events: none; }

        /* ── Members grid ── */
        .members-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 16px;
        }

        .member-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 10px;
            box-shadow: var(--shadow-sm);
            transition: box-shadow var(--transition), transform var(--transition);
            animation: fadeUp .3s ease both;
            position: relative;
        }
        .member-card:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }

        .mc-avatar-wrap { position: relative; }
        .mc-avatar {
            width: 64px; height: 64px; border-radius: 50%;
            object-fit: cover; display: block;
            border: 3px solid var(--brand-pale2);
        }
        .mc-avatar-fallback {
            width: 64px; height: 64px; border-radius: 50%;
            background: linear-gradient(135deg, var(--brand), var(--brand-light));
            color: #fff; font-family: 'Playfair Display', serif;
            font-size: 22px; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            border: 3px solid var(--brand-pale2);
        }

        .mc-name  { font-size: 15px; font-weight: 700; color: var(--text-primary); line-height: 1.2; }
        .mc-email { font-size: 12px; color: var(--text-muted); margin-top: -4px; word-break: break-all; }

        .role-badge { font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 20px; text-transform: capitalize; }
        .role-badge.manager { background: var(--brand-pale2); color: var(--brand); }
        .role-badge.member  { background: var(--surface-2);   color: var(--text-muted); border: 1px solid var(--border); }

        .mc-joined { font-size: 11px; color: var(--text-muted); margin-top: 2px; }

        .mc-actions {
            display: flex; gap: 6px; margin-top: 4px;
        }
        .btn-mc-action {
            flex: 1; padding: 7px 10px;
            border-radius: var(--radius-sm); border: 1.5px solid var(--border);
            background: transparent; font-family: 'DM Sans', sans-serif;
            font-size: 12px; font-weight: 600; cursor: pointer;
            color: var(--text-secondary);
            transition: all var(--transition);
            display: flex; align-items: center; justify-content: center; gap: 4px;
        }
        .btn-mc-action:hover       { border-color: var(--brand); color: var(--brand); background: var(--brand-pale); }
        .btn-mc-action.danger:hover { border-color: #dc2626; color: #dc2626; background: #fef2f2; }
        .btn-mc-action svg { width: 12px; height: 12px; stroke: currentColor; }

        /* ── Skeleton ── */
        .member-skeleton {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 20px;
            display: flex; flex-direction: column; align-items: center; gap: 10px;
        }
        .sk-circle { width: 64px; height: 64px; border-radius: 50%; background: var(--border); animation: shimmer 1.4s infinite; }
        .sk-block   { background: var(--border); border-radius: 6px; animation: shimmer 1.4s infinite; }
        @keyframes shimmer { 0%,100%{opacity:.5} 50%{opacity:1} }

        /* ── Empty state ── */
        .members-empty {
            grid-column: 1 / -1;
            display: flex; flex-direction: column; align-items: center;
            padding: 64px 24px; text-align: center; color: var(--text-muted);
        }
        .members-empty-icon {
            width: 72px; height: 72px; background: var(--brand-pale);
            border-radius: 20px; display: flex; align-items: center; justify-content: center;
            margin-bottom: 20px;
        }
        .members-empty-icon svg { width: 36px; height: 36px; stroke: var(--brand); }
        .members-empty h3 { font-family: 'Playfair Display', serif; font-size: 20px; color: var(--text-primary); margin-bottom: 8px; }
        .members-empty p  { font-size: 14px; margin-bottom: 24px; }

        @keyframes fadeUp { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }

        /* ── Credential reveal modal ── */
        #modal-credentials .modal { max-width: 420px; text-align: center; }

        .cred-icon {
            width: 60px; height: 60px; border-radius: 16px;
            background: linear-gradient(135deg, var(--brand-pale), var(--brand-pale2));
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px;
        }
        .cred-icon svg { width: 28px; height: 28px; stroke: var(--brand); }

        .cred-box {
            background: var(--surface-2); border: 1.5px solid var(--border);
            border-radius: var(--radius-sm); padding: 14px 16px;
            margin: 10px 0; text-align: left;
        }
        .cred-box label { font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: .6px; display: block; margin-bottom: 4px; }
        .cred-value-row  { display: flex; align-items: center; gap: 8px; }
        .cred-value      { flex: 1; font-size: 14px; font-weight: 600; color: var(--text-primary); font-family: monospace; word-break: break-all; }

        .btn-copy {
            background: transparent; border: 1.5px solid var(--border);
            border-radius: 6px; padding: 4px 8px;
            cursor: pointer; font-size: 11px; font-weight: 600;
            color: var(--text-muted); transition: all var(--transition);
            white-space: nowrap;
        }
        .btn-copy:hover   { border-color: var(--brand); color: var(--brand); }
        .btn-copy.copied  { border-color: #1a8a5c; color: #1a8a5c; }

        .cred-warning {
            background: #fffbeb; border: 1px solid #fde68a;
            border-radius: var(--radius-sm); padding: 10px 14px;
            font-size: 12px; color: #92400e; margin: 14px 0 0;
            display: flex; align-items: flex-start; gap: 8px; text-align: left;
        }
        .cred-warning svg { width: 14px; height: 14px; stroke: currentColor; flex-shrink: 0; margin-top: 1px; }

        /* ── Add Member modal form ── */
        .photo-upload-row {
            display: flex; align-items: center; gap: 16px; margin-bottom: 18px;
        }
        .photo-preview-circle {
            width: 56px; height: 56px; border-radius: 50%;
            background: var(--brand-pale2); border: 2px dashed var(--border);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; overflow: hidden; cursor: pointer; transition: border-color var(--transition);
        }
        .photo-preview-circle:hover { border-color: var(--brand); }
        .photo-preview-circle img { width: 100%; height: 100%; object-fit: cover; display: none; }
        .photo-preview-circle svg { width: 20px; height: 20px; stroke: var(--text-muted); }
        .photo-upload-info { flex: 1; }
        .photo-upload-info p { font-size: 13px; color: var(--text-primary); font-weight: 500; }
        .photo-upload-info span { font-size: 11px; color: var(--text-muted); }

        .role-select-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .role-option {
            border: 1.5px solid var(--border); border-radius: var(--radius-sm);
            padding: 12px 14px; cursor: pointer; transition: all var(--transition);
            position: relative;
        }
        .role-option input[type="radio"] { position: absolute; opacity: 0; pointer-events: none; }
        .role-option.selected { border-color: var(--brand); background: var(--brand-pale); }
        .role-option-label { font-size: 13px; font-weight: 700; color: var(--text-primary); }
        .role-option-desc  { font-size: 11px; color: var(--text-muted); margin-top: 2px; }
        .role-option.selected .role-option-label { color: var(--brand); }
    </style>
</head>
<body>
<div class="app-shell">

    <?php include __DIR__ . '/../partials/sidebar.php'; ?>

    <div class="main">
        <header class="topbar">
            <div class="topbar-title">Members</div>
            <div class="topbar-right">
                <span class="topbar-date" id="topbarDate"></span>
                <div class="topbar-avatar"><?= htmlspecialchars($adminInitial) ?></div>
            </div>
        </header>

        <main class="content">

            <div class="members-header">
                <div>
                    <h1>Members</h1>
                    <p id="members-subtitle">Loading members…</p>
                </div>
                <button class="btn-primary" onclick="openModal('modal-add-member')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Add Member
                </button>
            </div>

            <div class="members-toolbar">
                <button class="role-filter-btn active" data-role="all">All</button>
                <button class="role-filter-btn" data-role="manager">Managers</button>
                <button class="role-filter-btn" data-role="member">Members</button>
                <div class="members-search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <input type="text" id="member-search" placeholder="Search members…">
                </div>
            </div>

            <div class="members-grid" id="members-grid">
                <!-- skeleton -->
                <?php for ($i = 0; $i < 8; $i++): ?>
                <div class="member-skeleton">
                    <div class="sk-circle"></div>
                    <div class="sk-block" style="width:60%;height:13px"></div>
                    <div class="sk-block" style="width:80%;height:10px"></div>
                    <div class="sk-block" style="width:40%;height:10px;border-radius:20px"></div>
                </div>
                <?php endfor; ?>
            </div>

        </main>
    </div>
</div>


<!-- ══ Add Member Modal ══ -->
<div class="modal-backdrop" id="modal-add-member"
     onclick="closeModalOutside(event, 'modal-add-member')">
    <div class="modal">
        <h2>Add Member</h2>
        <p style="font-size:14px;color:var(--text-secondary);margin-bottom:24px">
            Create credentials for a new team member.
        </p>

        <div class="form-error" id="add-member-error"></div>

        <form id="add-member-form" enctype="multipart/form-data">

            <!-- Photo upload -->
            <div class="photo-upload-row">
                <div class="photo-preview-circle" id="photo-circle"
                     onclick="document.getElementById('member-photo-input').click()">
                    <img id="photo-preview-img" alt="">
                    <svg id="photo-preview-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                        <circle cx="12" cy="13" r="4"/>
                    </svg>
                </div>
                <div class="photo-upload-info">
                    <p>Profile photo</p>
                    <span>Optional · JPG, PNG, WEBP</span>
                </div>
                <input type="file" id="member-photo-input" name="image"
                       accept="image/jpeg,image/png,image/webp" style="display:none"
                       onchange="previewMemberPhoto(this)">
            </div>

            <!-- Name + Email -->
            <div class="form-group">
                <label for="m-name">Full Name <span style="color:var(--brand)">*</span></label>
                <input type="text" id="m-name" name="name" placeholder="e.g. Priya Nair" required maxlength="255">
            </div>
            <div class="form-group">
                <label for="m-email">Email Address <span style="color:var(--brand)">*</span></label>
                <input type="email" id="m-email" name="email" placeholder="priya@yourcompany.com" required>
            </div>

            <!-- Role -->
            <div class="form-group">
                <label>Role <span style="color:var(--brand)">*</span></label>
                <div class="role-select-grid">
                    <label class="role-option selected" id="role-member-opt">
                        <input type="radio" name="role" value="member" checked onchange="selectRole(this)">
                        <div class="role-option-label">Member</div>
                        <div class="role-option-desc">Regular team member</div>
                    </label>
                    <label class="role-option" id="role-manager-opt">
                        <input type="radio" name="role" value="manager" onchange="selectRole(this)">
                        <div class="role-option-label">Manager</div>
                        <div class="role-option-desc">Can manage projects</div>
                    </label>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-ghost" onclick="closeModal('modal-add-member')">Cancel</button>
                <button type="submit" class="btn-primary" id="add-member-btn">
                    <div class="btn-spin"></div>
                    <span class="btn-text">Create Member</span>
                </button>
            </div>
        </form>
    </div>
</div>


<!-- ══ Credentials Reveal Modal ══ -->
<div class="modal-backdrop" id="modal-credentials"
     onclick="closeModalOutside(event, 'modal-credentials')">
    <div class="modal">
        <div class="cred-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
        </div>

        <h2 style="font-size:20px;margin-bottom:6px">Member Created!</h2>
        <p style="font-size:14px;color:var(--text-secondary);margin-bottom:20px">
            Share these credentials with <strong id="cred-member-name"></strong>.
        </p>

        <div class="cred-box">
            <label>Email</label>
            <div class="cred-value-row">
                <span class="cred-value" id="cred-email"></span>
                <button class="btn-copy" onclick="copyCredField('cred-email', this)">Copy</button>
            </div>
        </div>

        <div class="cred-box">
            <label>Password (one-time)</label>
            <div class="cred-value-row">
                <span class="cred-value" id="cred-password"></span>
                <button class="btn-copy" onclick="copyCredField('cred-password', this)">Copy</button>
            </div>
        </div>

        <div class="cred-warning">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
            This password is shown <strong>only once</strong>. Copy it before closing.
        </div>

        <div class="modal-actions" style="margin-top:24px;justify-content:flex-end">
            <button class="btn-primary" onclick="closeModal('modal-credentials')">Done</button>
        </div>
    </div>
</div>


<script> window.WH_BASE = '<?= $basePath ?>'; </script>
<script src="<?= $basePath ?>/js/app.js"></script>
<script src="<?= $basePath ?>/js/member.js"></script>
</body>
</html>