<?php
// views/user/Notifications.php
$basePath    = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$userName    = $_SESSION['user_name'] ?? 'Team Member';
$userInitial = strtoupper(substr($userName, 0, 1));
$userRole    = $_SESSION['role']      ?? 'member';
$isManager   = $userRole === 'manager';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications — WorkHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
        :root {
            --brand:#6A0031; --brand-mid:#8a1144; --brand-light:#b8245f;
            --brand-pale:#fdf2f6; --brand-pale2:#f5e6ed; --accent:#E8A045;
            --text-primary:#1a1218; --text-secondary:#6b5b65; --text-muted:#a08898;
            --border:#e8dde3; --surface:#ffffff; --surface-2:#faf7f9;
            --sidebar-w:260px; --header-h:64px;
            --shadow-sm:0 1px 3px rgba(106,0,49,.08),0 1px 2px rgba(0,0,0,.04);
            --shadow-md:0 4px 16px rgba(106,0,49,.10),0 2px 8px rgba(0,0,0,.06);
            --radius:12px; --radius-sm:8px; --transition:0.22s cubic-bezier(.4,0,.2,1);
            --green:#1a8a5c; --green-pale:#e6f9f1;
            --red:#dc2626;   --red-pale:#fef2f2;
            --orange:#ea580c; --orange-pale:#fff7ed;
        }
        html,body { height:100%; font-family:'DM Sans',sans-serif; background:var(--surface-2); color:var(--text-primary); font-size:15px; line-height:1.6; }
        .app-shell { display:flex; min-height:100vh; }

        /* ── Sidebar (same pattern as other views) ── */
        .sidebar { width:var(--sidebar-w); background:var(--brand); display:flex; flex-direction:column; position:fixed; top:0; left:0; bottom:0; z-index:100; }
        .sidebar::before { content:''; position:absolute; inset:0; background:repeating-linear-gradient(135deg,transparent,transparent 40px,rgba(255,255,255,.018) 40px,rgba(255,255,255,.018) 80px); pointer-events:none; }
        .sidebar-logo { display:flex; align-items:center; gap:12px; padding:22px 20px 20px; border-bottom:1px solid rgba(255,255,255,.1); }
        .logo-mark { width:36px; height:36px; background:var(--accent); border-radius:10px; display:flex; align-items:center; justify-content:center; font-family:'Playfair Display',serif; font-weight:700; font-size:18px; color:var(--brand); flex-shrink:0; }
        .logo-text { font-family:'Playfair Display',serif; font-size:20px; color:#fff; }
        .logo-text span { color:var(--accent); }
        .sidebar-nav { flex:1; padding:8px 10px; overflow-y:auto; }
        .nav-section-label { font-size:10px; font-weight:600; letter-spacing:1.2px; text-transform:uppercase; color:rgba(255,255,255,.35); padding:14px 10px 6px; }
        .nav-item { display:flex; align-items:center; gap:12px; padding:10px 12px; border-radius:var(--radius-sm); color:rgba(255,255,255,.75); text-decoration:none; font-size:14px; font-weight:500; cursor:pointer; border:none; background:transparent; width:100%; transition:background var(--transition),color var(--transition); position:relative; }
        .nav-item:hover  { background:rgba(255,255,255,.1); color:#fff; }
        .nav-item.active { background:rgba(255,255,255,.18); color:#fff; font-weight:600; }
        .nav-item.active::before { content:''; position:absolute; left:0; top:20%; bottom:20%; width:3px; background:var(--accent); border-radius:0 3px 3px 0; }
        .nav-icon { width:20px; height:20px; flex-shrink:0; }
        .nav-projects-toggle { cursor:pointer; user-select:none; }
        .nav-project-item { display:flex; align-items:center; gap:8px; padding:7px 12px 7px 20px; border-radius:var(--radius-sm); color:rgba(255,255,255,.65); text-decoration:none; font-size:13px; font-weight:500; cursor:pointer; transition:background var(--transition),color var(--transition); position:relative; }
        .nav-project-item:hover { background:rgba(255,255,255,.08); color:#fff; }
        .nav-project-dot { width:7px; height:7px; border-radius:50%; flex-shrink:0; }
        .nav-project-dot.active    { background:#34d399; }
        .nav-project-dot.completed { background:rgba(255,255,255,.4); }
        .nav-project-dot.archived  { background:rgba(255,255,255,.2); }
        .nav-projects-empty { padding:8px 12px 8px 20px; font-size:12px; color:rgba(255,255,255,.3); font-style:italic; }
        @keyframes navShimmer { 0%,100%{opacity:.4} 50%{opacity:.8} }
        .sidebar-footer { border-top:1px solid rgba(255,255,255,.1); padding:14px 10px; }
        .user-card { display:flex; align-items:center; gap:10px; padding:8px 10px; border-radius:var(--radius-sm); cursor:pointer; }
        .user-card:hover { background:rgba(255,255,255,.08); }
        .user-avatar { width:34px; height:34px; border-radius:50%; background:var(--brand-light); display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:700; color:#fff; flex-shrink:0; border:2px solid rgba(255,255,255,.2); }
        .user-info { flex:1; min-width:0; }
        .user-name { font-size:13px; font-weight:600; color:#fff; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .user-role { font-size:11px; color:rgba(255,255,255,.45); text-transform:capitalize; }
        .btn-logout-sm { background:transparent; border:none; color:rgba(255,255,255,.45); cursor:pointer; padding:4px; border-radius:6px; }
        .btn-logout-sm:hover { color:#fff; }

        /* ── Main ── */
        .main { margin-left:var(--sidebar-w); flex:1; display:flex; flex-direction:column; min-height:100vh; }
        .topbar { height:var(--header-h); background:var(--surface); border-bottom:1px solid var(--border); display:flex; align-items:center; padding:0 32px; gap:20px; position:sticky; top:0; z-index:50; box-shadow:var(--shadow-sm); }
        .topbar-title { font-family:'Playfair Display',serif; font-size:22px; color:var(--brand); font-weight:600; }
        .topbar-right { margin-left:auto; display:flex; align-items:center; gap:12px; }
        .topbar-date  { font-size:13px; color:var(--text-muted); }
        .topbar-avatar { width:36px; height:36px; border-radius:50%; background:var(--brand); color:#fff; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:700; border:2px solid var(--brand-pale2); }
        .content { flex:1; padding:32px; max-width:760px; }

        /* ── Page header ── */
        .page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:28px; gap:16px; flex-wrap:wrap; }
        .page-header h1 { font-family:'Playfair Display',serif; font-size:30px; color:var(--text-primary); }
        .page-header p  { font-size:14px; color:var(--text-secondary); margin-top:2px; }

        .btn-mark-all {
            display:inline-flex; align-items:center; gap:7px;
            padding:8px 18px; border-radius:var(--radius-sm);
            border:1.5px solid var(--border); background:var(--surface);
            font-family:'DM Sans',sans-serif; font-size:13px; font-weight:600;
            color:var(--text-secondary); cursor:pointer;
            transition:all var(--transition);
        }
        .btn-mark-all:hover { border-color:var(--brand); color:var(--brand); }
        .btn-mark-all:disabled { opacity:.5; cursor:not-allowed; }

        /* ── Filter tabs ── */
        .filter-tabs { display:flex; gap:4px; background:var(--surface); border:1px solid var(--border); border-radius:10px; padding:3px; margin-bottom:20px; width:fit-content; box-shadow:var(--shadow-sm); }
        .filter-tab { padding:6px 16px; border-radius:8px; border:none; background:transparent; font-family:'DM Sans',sans-serif; font-size:13px; font-weight:600; cursor:pointer; color:var(--text-muted); transition:all var(--transition); }
        .filter-tab.active { background:var(--brand); color:#fff; box-shadow:var(--shadow-sm); }

        /* ── Notification card ── */
        .notif-group-label { font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:.8px; padding:16px 0 8px; }
        .notif-group-label:first-child { padding-top:0; }

        .notif-card {
            background:var(--surface); border:1px solid var(--border);
            border-radius:var(--radius); padding:16px 20px;
            display:flex; align-items:flex-start; gap:14px;
            margin-bottom:10px; cursor:pointer;
            transition:box-shadow var(--transition), transform var(--transition), border-color var(--transition);
            animation:fadeUp .3s ease both;
            position:relative;
        }
        .notif-card:hover { box-shadow:var(--shadow-md); transform:translateY(-1px); }
        .notif-card.unread { border-left:3px solid var(--brand); background:var(--brand-pale); }
        .notif-card.unread:hover { border-color:var(--brand); }

        /* Unread dot */
        .unread-dot {
            width:8px; height:8px; border-radius:50%;
            background:var(--brand); flex-shrink:0; margin-top:6px;
            animation:pulse 2s infinite;
        }
        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }
        .read-spacer { width:8px; flex-shrink:0; }

        /* Icon bubble */
        .notif-icon {
            width:40px; height:40px; border-radius:10px;
            display:flex; align-items:center; justify-content:center;
            flex-shrink:0; font-size:18px;
        }
        .notif-icon.overdue  { background:var(--red-pale); }
        .notif-icon.due_soon { background:var(--orange-pale); }
        .notif-icon.assigned { background:var(--green-pale); }
        .notif-icon.status_changed { background:var(--brand-pale2); }

        .notif-body { flex:1; min-width:0; }
        .notif-message { font-size:14px; font-weight:500; color:var(--text-primary); line-height:1.5; margin-bottom:4px; }
        .notif-card.unread .notif-message { font-weight:600; }
        .notif-meta { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
        .notif-project { font-size:12px; color:var(--brand); font-weight:600; background:var(--brand-pale); padding:1px 8px; border-radius:10px; }
        .notif-time { font-size:12px; color:var(--text-muted); }
        .notif-priority { font-size:11px; font-weight:600; padding:1px 7px; border-radius:10px; text-transform:capitalize; }
        .notif-priority.critical { background:var(--red-pale);    color:var(--red); }
        .notif-priority.high     { background:var(--orange-pale); color:var(--orange); }
        .notif-priority.medium   { background:#fef9c3;            color:#92400e; }
        .notif-priority.low      { background:var(--surface-2);   color:var(--text-muted); }

        .notif-action {
            flex-shrink:0; opacity:0; transition:opacity var(--transition);
            background:none; border:none; cursor:pointer; color:var(--text-muted);
            padding:4px; border-radius:6px;
        }
        .notif-card:hover .notif-action { opacity:1; }
        .notif-action:hover { color:var(--brand); background:var(--brand-pale); }

        /* ── Empty state ── */
        .empty-state { display:flex; flex-direction:column; align-items:center; padding:80px 24px; text-align:center; }
        .empty-icon { font-size:48px; margin-bottom:16px; }
        .empty-state h3 { font-family:'Playfair Display',serif; font-size:20px; color:var(--text-primary); margin-bottom:6px; }
        .empty-state p  { font-size:14px; color:var(--text-muted); }

        /* ── Skeleton ── */
        .sk-line { background:var(--border); border-radius:4px; animation:shimmer 1.4s infinite; }
        @keyframes shimmer { 0%,100%{opacity:.5} 50%{opacity:1} }
        @keyframes fadeUp  { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:translateY(0)} }

        /* ── Toast ── */
        .toast { position:fixed; bottom:28px; right:28px; background:#1a1218; color:#fff; padding:12px 18px; border-radius:10px; display:flex; align-items:center; gap:10px; font-size:14px; font-weight:500; box-shadow:0 8px 24px rgba(0,0,0,.2); transform:translateY(12px); opacity:0; transition:all .25s cubic-bezier(.4,0,.2,1); z-index:999; }
        .toast.show { transform:translateY(0); opacity:1; }
        .toast-dot { width:8px; height:8px; border-radius:50%; background:#34d399; flex-shrink:0; }
        .toast.error .toast-dot { background:#f87171; }
    </style>
</head>
<body>
<div class="app-shell">

    <aside class="sidebar">
        <div class="sidebar-logo">
            <div class="logo-mark">W</div>
            <div class="logo-text">Work<span>Hub</span></div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section-label">Workspace</div>
            <a href="<?= $basePath ?>/user/dashboard" class="nav-item">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                    <rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/>
                </svg>
                Dashboard
            </a>
            <a href="<?= $basePath ?>/user/tasks" class="nav-item">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 11l3 3L22 4"/>
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                </svg>
                My Tasks
            </a>

            <div class="nav-item nav-projects-toggle" id="nav-projects-toggle" onclick="toggleProjectsNav()">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                </svg>
                <span style="flex:1">Projects</span>
                <svg id="projects-chevron" style="width:14px;height:14px;stroke:currentColor;transition:transform .22s cubic-bezier(.4,0,.2,1);flex-shrink:0" viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"/>
                </svg>
            </div>
            <div id="projects-nav-list" style="overflow:hidden;max-height:0;transition:max-height .3s cubic-bezier(.4,0,.2,1)">
                <div id="projects-nav-skeleton" style="padding:4px 10px 6px">
                    <div style="height:32px;border-radius:6px;background:rgba(255,255,255,.08);margin-bottom:4px;animation:navShimmer 1.4s infinite"></div>
                    <div style="height:32px;border-radius:6px;background:rgba(255,255,255,.08);animation:navShimmer 1.4s infinite;animation-delay:.15s"></div>
                </div>
                <div id="projects-nav-items"></div>
            </div>

            <?php if ($isManager): ?>
            <div class="nav-section-label" style="margin-top:6px">Manage</div>
            <a href="<?= $basePath ?>/user/analytics" class="nav-item">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/>
                    <line x1="6" y1="20" x2="6" y2="14"/><line x1="2" y1="20" x2="22" y2="20"/>
                </svg>
                Analytics
            </a>
            <?php endif; ?>

            <div class="nav-section-label" style="margin-top:6px">You</div>
            <a href="<?= $basePath ?>/user/notifications" class="nav-item active" id="nav-notifications-link">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
                <span style="flex:1">Notifications</span>
                <span id="nav-notif-badge" data-notif-badge
                      style="display:none;min-width:18px;height:18px;padding:0 5px;border-radius:20px;background:var(--accent);color:var(--brand);font-size:10px;font-weight:700;line-height:18px;text-align:center;flex-shrink:0"></span>
            </a>
        </nav>
        <div class="sidebar-footer">
            <div class="user-card">
                <div class="user-avatar"><?= htmlspecialchars($userInitial) ?></div>
                <div class="user-info">
                    <div class="user-name"><?= htmlspecialchars($userName) ?></div>
                    <div class="user-role"><?= htmlspecialchars($userRole) ?></div>
                </div>
                <button class="btn-logout-sm" onclick="handleLogout()" title="Logout">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                </button>
            </div>
        </div>
    </aside>

    <div class="main">
        <header class="topbar">
            <div class="topbar-title">Notifications</div>
            <div class="topbar-right">
                <span class="topbar-date" id="topbar-date"></span>
                <div class="topbar-avatar"><?= htmlspecialchars($userInitial) ?></div>
            </div>
        </header>

        <main class="content">
            <div class="page-header">
                <div>
                    <h1>Notifications</h1>
                    <p id="notif-subtitle">Your task alerts and updates.</p>
                </div>
                <button class="btn-mark-all" id="btn-mark-all" onclick="markAllRead()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    Mark all as read
                </button>
            </div>

            <div class="filter-tabs">
                <button class="filter-tab active" data-filter="all"    onclick="setFilter('all',    this)">All</button>
                <button class="filter-tab"         data-filter="unread" onclick="setFilter('unread', this)">Unread</button>
                <button class="filter-tab"         data-filter="overdue" onclick="setFilter('overdue',this)">Overdue</button>
                <button class="filter-tab"         data-filter="due_soon" onclick="setFilter('due_soon',this)">Due Soon</button>
            </div>

            <div id="notif-list">
                <!-- skeleton -->
                <?php for($i=0;$i<5;$i++): ?>
                <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:16px 20px;display:flex;gap:14px;align-items:flex-start;margin-bottom:10px">
                    <div class="sk-line" style="width:40px;height:40px;border-radius:10px;flex-shrink:0"></div>
                    <div style="flex:1">
                        <div class="sk-line" style="height:14px;width:70%;margin-bottom:8px"></div>
                        <div class="sk-line" style="height:11px;width:40%"></div>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
        </main>
    </div>
</div>

<div class="toast" id="toast"><div class="toast-dot"></div><span id="toast-msg"></span></div>

<script>
    window.WH_BASE    = '<?= $basePath ?>';
    window.WH_ROLE    = '<?= $userRole ?>';
    window.WH_USER_ID = <?= (int)($_SESSION['user_id'] ?? 0) ?>;
    const BASE = window.WH_BASE;
</script>
<script src="<?= $basePath ?>/js/notif-poll.js"></script>
<script src="<?= $basePath ?>/js/user-notifications.js"></script>
</body>
</html>