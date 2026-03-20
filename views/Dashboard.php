<?php
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

$adminEmail   = $_SESSION['admin_email'] ?? 'admin@workhub.com';
$adminId      = $_SESSION['admin_Id']    ?? '1';
$adminInitial = strtoupper(substr($adminEmail, 0, 1));
$adminHandle  = explode('@', $adminEmail)[0];
$activePage   = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — WorkHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $basePath ?>/css/dashboard.css">
    <style>
        /* ── Dashboard-specific extras ── */
        .dash-grid       { display:grid; grid-template-columns:1fr 1fr 1fr; gap:20px; margin-bottom:24px; }
        .dash-grid-2     { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:24px; }
        .dash-full       { margin-bottom:24px; }

        /* Stat cards */
        .stat-card       { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); padding:20px; display:flex; align-items:center; gap:14px; box-shadow:var(--shadow-sm); transition:box-shadow var(--transition),transform var(--transition); }
        .stat-card:hover { box-shadow:var(--shadow-md); transform:translateY(-2px); }
        .stat-icon       { width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0; }
        .stat-icon svg   { width:22px;height:22px;stroke:currentColor; }
        .stat-icon.members   { background:var(--brand-pale); color:var(--brand); }
        .stat-icon.projects  { background:#eff6ff; color:#1a56db; }
        .stat-icon.tasks     { background:#e6f9f1; color:#1a8a5c; }
        .stat-icon.overdue   { background:#fef2f2; color:#dc2626; }
        .stat-icon.completed { background:#e6f9f1; color:#1a8a5c; }
        .stat-icon.active    { background:#fff7ed; color:#ea580c; }
        .stat-value          { font-family:'Playfair Display',serif; font-size:28px; font-weight:700; color:var(--text-primary); line-height:1; }
        .stat-label          { font-size:11px; color:var(--text-muted); font-weight:600; text-transform:uppercase; letter-spacing:.5px; margin-top:3px; }
        .stat-sub            { font-size:11px; color:var(--text-muted); margin-top:2px; }

        /* Panel */
        .panel           { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); box-shadow:var(--shadow-sm); overflow:hidden; }
        .panel-hd        { padding:16px 20px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; }
        .panel-hd h3     { font-family:'Playfair Display',serif; font-size:15px; color:var(--text-primary); }
        .panel-hd a      { font-size:12px; color:var(--brand); text-decoration:none; font-weight:600; }
        .panel-hd a:hover{ text-decoration:underline; }

        /* Quick actions */
        .qa-grid         { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; padding:16px; }
        .qa-btn          { display:flex;flex-direction:column;align-items:center;gap:8px;padding:16px 12px;border-radius:var(--radius-sm);background:var(--surface-2);border:1.5px solid var(--border);text-decoration:none;color:var(--text-secondary);font-size:12px;font-weight:600;transition:all var(--transition);cursor:pointer; }
        .qa-btn:hover    { border-color:var(--brand);color:var(--brand);background:var(--brand-pale);transform:translateY(-2px);box-shadow:var(--shadow-sm); }
        .qa-btn svg      { width:22px;height:22px;stroke:currentColor; }
        .qa-btn.disabled { opacity:.45;pointer-events:none; }

        /* Project rows */
        .proj-row        { display:flex;align-items:center;gap:12px;padding:12px 20px;border-bottom:1px solid var(--border);transition:background var(--transition); }
        .proj-row:last-child { border-bottom:none; }
        .proj-row:hover  { background:var(--surface-2); }
        .proj-dot        { width:8px;height:8px;border-radius:50%;flex-shrink:0; }
        .proj-dot.active    { background:#1a8a5c; }
        .proj-dot.completed { background:#1a56db; }
        .proj-dot.archived  { background:var(--text-muted); }
        .proj-info       { flex:1;min-width:0; }
        .proj-name       { font-size:13px;font-weight:600;color:var(--text-primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
        .proj-sub        { font-size:11px;color:var(--text-muted);margin-top:1px; }
        .proj-pct        { font-size:12px;font-weight:700;color:var(--brand);flex-shrink:0; }
        .proj-bar-wrap   { width:60px;height:4px;background:var(--border);border-radius:2px;overflow:hidden;flex-shrink:0; }
        .proj-bar-fill   { height:100%;border-radius:2px;background:linear-gradient(90deg,var(--brand),var(--brand-light)); }

        /* Activity log mini */
        .act-row         { display:flex;align-items:flex-start;gap:10px;padding:10px 20px;border-bottom:1px solid var(--border);transition:background var(--transition); }
        .act-row:last-child { border-bottom:none; }
        .act-row:hover   { background:var(--surface-2); }
        .act-dot         { width:7px;height:7px;border-radius:50%;flex-shrink:0;margin-top:5px; }
        .act-dot.admin   { background:var(--brand); }
        .act-dot.user    { background:#1a56db; }
        .act-body        { flex:1;min-width:0; }
        .act-text        { font-size:12.5px;color:var(--text-primary);line-height:1.5; }
        .act-text b      { color:var(--brand); }
        .act-time        { font-size:11px;color:var(--text-muted);white-space:nowrap;flex-shrink:0; }

        /* Org profile card */
        .org-profile-card    { background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow-sm);margin-bottom:24px;overflow:hidden; }
        .org-card-banner     { height:80px;background:linear-gradient(135deg,var(--brand) 0%,var(--brand-light) 60%,var(--accent) 100%); }
        .org-card-body       { padding:0 24px 24px;display:flex;align-items:flex-end;gap:20px;flex-wrap:wrap; }
        .org-card-logo       { width:64px;height:64px;border-radius:14px;border:3px solid var(--surface);object-fit:cover;margin-top:-32px;flex-shrink:0; }
        .org-card-logo-fallback { width:64px;height:64px;border-radius:14px;border:3px solid var(--surface);background:var(--brand);color:#fff;font-family:'Playfair Display',serif;font-size:28px;font-weight:700;display:flex;align-items:center;justify-content:center;margin-top:-32px;flex-shrink:0; }
        .org-card-text       { flex:1;min-width:0;padding-top:10px; }
        .org-card-name       { font-family:'Playfair Display',serif;font-size:20px;font-weight:700;color:var(--text-primary); }
        .org-card-slogan     { font-size:13px;color:var(--text-muted);margin-top:2px; }
        .org-card-meta       { display:flex;gap:28px;margin-top:10px; }
        .org-meta-item       { text-align:left; }
        .org-meta-value      { font-family:'Playfair Display',serif;font-size:18px;font-weight:700;color:var(--text-primary); }
        .org-meta-label      { font-size:11px;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.4px; }

        /* Skeleton */
        .sk-line          { background:var(--border);border-radius:4px;animation:shimmer 1.4s infinite; }
        @keyframes shimmer { 0%,100%{opacity:.5} 50%{opacity:1} }
        @keyframes fadeUp  { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }

        /* States */
        .state              { display:none;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:80px 24px; }
        .state.active       { display:flex; }
        .spinner            { width:36px;height:36px;border:3px solid var(--border);border-top-color:var(--brand);border-radius:50%;animation:spin .8s linear infinite;margin-bottom:16px; }
        @keyframes spin { to{transform:rotate(360deg)} }

        .no-org-icon svg  { width:64px;height:64px;stroke:var(--text-muted);margin-bottom:16px; }
        .no-org-title     { font-family:'Playfair Display',serif;font-size:22px;color:var(--text-primary);margin-bottom:8px; }
        .no-org-subtitle  { font-size:14px;color:var(--text-muted);max-width:380px;line-height:1.6;margin-bottom:24px; }

        @media(max-width:1100px) { .dash-grid { grid-template-columns:1fr 1fr; } }
        @media(max-width:780px)  { .dash-grid,.dash-grid-2 { grid-template-columns:1fr; } .qa-grid { grid-template-columns:repeat(2,1fr); } }
    </style>
</head>
<body>
<div class="app-shell">

    <?php include __DIR__ . '/../partials/sidebar.php'; ?>

    <div class="main">
        <header class="topbar">
            <div class="topbar-title">Dashboard</div>
            <div class="topbar-right">
                <span class="topbar-date" id="topbarDate"></span>
                <div class="topbar-avatar"><?= htmlspecialchars($adminInitial) ?></div>
            </div>
        </header>

        <main class="content">

            <!-- Loading -->
            <div id="main-loading" class="state active">
                <div class="spinner"></div>
                <p>Loading your workspace…</p>
            </div>

            <!-- No Organisation -->
            <div id="main-no-org" class="state">
                <div class="no-org-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="7" width="20" height="14" rx="2"/>
                        <path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>
                        <line x1="12" y1="12" x2="12" y2="16"/>
                        <line x1="10" y1="14" x2="14" y2="14"/>
                    </svg>
                </div>
                <h1 class="no-org-title">Create your organization</h1>
                <p class="no-org-subtitle">Set up your WorkHub workspace to start managing projects, inviting team members, and tracking progress.</p>
                <button class="btn-primary" onclick="openModal('modalBackdrop')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Create Organization
                </button>
            </div>

            <!-- Has Organisation -->
            <div id="main-has-org" style="display:none">

                <!-- Welcome -->
                <div class="page-header">
                    <h1>Welcome back, <?= htmlspecialchars($adminHandle) ?> 👋</h1>
                    <p id="org-welcome-sub">Here's your organization overview.</p>
                </div>

                <!-- Org profile card -->
                <div class="org-profile-card">
                    <div class="org-card-banner"></div>
                    <div class="org-card-body">
                        <div id="org-logo-slot"></div>
                        <div class="org-card-text">
                            <div class="org-card-name"   id="org-card-name"></div>
                            <div class="org-card-slogan" id="org-card-slogan"></div>
                            <div class="org-card-meta">
                                <div class="org-meta-item">
                                    <div class="org-meta-value" id="meta-since">—</div>
                                    <div class="org-meta-label">Since</div>
                                </div>
                                <div class="org-meta-item">
                                    <div class="org-meta-value" id="meta-members">—</div>
                                    <div class="org-meta-label">Members</div>
                                </div>
                                <div class="org-meta-item">
                                    <div class="org-meta-value" id="meta-projects">—</div>
                                    <div class="org-meta-label">Projects</div>
                                </div>
                                <div class="org-meta-item">
                                    <div class="org-meta-value" id="meta-tasks">—</div>
                                    <div class="org-meta-label">Total Tasks</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- KPI stat cards -->
                <div class="dash-grid" id="stat-cards">
                    <?php for($i=0;$i<6;$i++): ?>
                    <div class="stat-card">
                        <div class="sk-line" style="width:44px;height:44px;border-radius:12px;flex-shrink:0"></div>
                        <div style="flex:1">
                            <div class="sk-line" style="width:50px;height:22px;margin-bottom:6px"></div>
                            <div class="sk-line" style="width:80px;height:10px"></div>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>

                <!-- Quick actions -->
                <div class="panel dash-full">
                    <div class="panel-hd"><h3>Quick Actions</h3></div>
                    <div class="qa-grid">
                        <a href="<?= $basePath ?>/projects" class="qa-btn" id="qa-projects">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                            </svg>
                            Projects
                        </a>
                        <a href="<?= $basePath ?>/members" class="qa-btn" id="qa-members">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                <circle cx="8.5" cy="7" r="4"/>
                                <line x1="20" y1="8" x2="20" y2="14"/>
                                <line x1="23" y1="11" x2="17" y2="11"/>
                            </svg>
                            Invite Member
                        </a>
                        <a href="<?= $basePath ?>/analytics" class="qa-btn" id="qa-analytics">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="20" x2="18" y2="10"/>
                                <line x1="12" y1="20" x2="12" y2="4"/>
                                <line x1="6"  y1="20" x2="6"  y2="14"/>
                                <line x1="2"  y1="20" x2="22" y2="20"/>
                            </svg>
                            Analytics
                        </a>
                        <button class="qa-btn" onclick="openModal('modalBackdrop')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="12" y1="8" x2="12" y2="16"/>
                                <line x1="8"  y1="12" x2="16" y2="12"/>
                            </svg>
                            Edit Org
                        </button>
                    </div>
                </div>

                <!-- Recent projects + Activity -->
                <div class="dash-grid-2">

                    <!-- Recent projects -->
                    <div class="panel">
                        <div class="panel-hd">
                            <h3>Recent Projects</h3>
                            <a href="<?= $basePath ?>/projects">View all →</a>
                        </div>
                        <div id="recent-projects">
                            <?php for($i=0;$i<4;$i++): ?>
                            <div class="proj-row">
                                <div class="sk-line" style="width:8px;height:8px;border-radius:50%;flex-shrink:0"></div>
                                <div style="flex:1"><div class="sk-line" style="width:70%;height:12px;margin-bottom:5px"></div><div class="sk-line" style="width:40%;height:9px"></div></div>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <!-- Recent activity -->
                    <div class="panel">
                        <div class="panel-hd">
                            <h3>Recent Activity</h3>
                            <a href="<?= $basePath ?>/analytics">View all →</a>
                        </div>
                        <div id="recent-activity">
                            <?php for($i=0;$i<5;$i++): ?>
                            <div class="act-row">
                                <div class="sk-line" style="width:7px;height:7px;border-radius:50%;flex-shrink:0;margin-top:5px"></div>
                                <div style="flex:1"><div class="sk-line" style="width:85%;height:11px;margin-bottom:5px"></div><div class="sk-line" style="width:30%;height:9px"></div></div>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>

                </div>

            </div><!-- /#main-has-org -->

            <!-- Error -->
            <div id="main-error" class="state">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8"  x2="12"   y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <p id="error-msg" style="color:var(--text-muted);margin-top:12px">Something went wrong.</p>
                <button class="btn-ghost" onclick="fetchOrg()" style="margin-top:12px">Try again</button>
            </div>

        </main>
    </div>
</div>

<!-- ══ Create / Edit Org Modal ══ -->
<div class="modal-backdrop" id="modalBackdrop" onclick="closeModalOutside(event,'modalBackdrop')">
    <div class="modal">
        <div>
            <h2 id="modal-org-title">Create Organization</h2>
            <p>Set up your WorkHub workspace and start collaborating with your team.</p>
        </div>

        <div class="form-error" id="form-error"></div>

        <form id="create-org-form" enctype="multipart/form-data">
            <div class="form-group">
                <label>Organization Logo <span class="opt">(optional)</span></label>
                <div class="logo-upload-area" id="upload-area">
                    <input type="file" name="organization_logo" id="logo-input" accept="image/*" onchange="previewLogo(event)">
                    <img class="upload-preview" id="upload-preview" src="" alt="Preview"
                         style="display:none;width:64px;height:64px;border-radius:12px;object-fit:cover;margin:0 auto 8px">
                    <div id="upload-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                             style="width:32px;height:32px;stroke:var(--text-muted);display:block;margin:0 auto 8px">
                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                            <circle cx="8.5" cy="8.5" r="1.5"/>
                            <polyline points="21 15 16 10 5 21"/>
                        </svg>
                        <div class="upload-label">
                            <strong>Click to upload</strong> your logo<br>
                            <span style="font-size:11px;color:var(--text-muted)">PNG, JPG or WEBP — max 5MB</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="org_name">Organization Name <span style="color:var(--brand)">*</span></label>
                <input type="text" id="org_name" name="name" placeholder="e.g. Acme Corp" required maxlength="255">
            </div>

            <div class="form-group">
                <label for="org_slogan">Slogan <span class="opt">(optional)</span></label>
                <input type="text" id="org_slogan" name="slogan" placeholder="e.g. Building the future, together" maxlength="255">
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-ghost" onclick="closeModal('modalBackdrop')">Cancel</button>
                <button type="submit" class="btn-primary" id="submit-btn">
                    <div class="btn-spin"></div>
                    <span class="btn-text" style="display:flex;align-items:center;gap:8px">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        Create Organization
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>

<script> window.WH_BASE = '<?= $basePath ?>'; </script>
<script src="<?= $basePath ?>/js/app.js"></script>
<script src="<?= $basePath ?>/js/dashboard.js"></script>
</body>
</html>