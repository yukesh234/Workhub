<?php
// views/user/Analytics.php
$basePath    = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$userName    = $_SESSION['user_name'] ?? 'Manager';
$userInitial = strtoupper(substr($userName, 0, 1));
$userRole    = $_SESSION['role']      ?? 'manager';
$isManager   = true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics — WorkHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --brand:#6A0031; --brand-mid:#8a1144; --brand-light:#b8245f;
            --brand-pale:#fdf2f6; --brand-pale2:#f5e6ed; --accent:#E8A045;
            --text-primary:#1a1218; --text-secondary:#6b5b65; --text-muted:#a08898;
            --border:#e8dde3; --surface:#ffffff; --surface-2:#faf7f9;
            --sidebar-w:260px; --header-h:64px;
            --shadow-sm:0 1px 3px rgba(106,0,49,.08),0 1px 2px rgba(0,0,0,.04);
            --shadow-md:0 4px 16px rgba(106,0,49,.10),0 2px 8px rgba(0,0,0,.06);
            --shadow-lg:0 12px 40px rgba(106,0,49,.15),0 4px 16px rgba(0,0,0,.08);
            --radius:12px; --radius-sm:8px; --transition:0.22s cubic-bezier(.4,0,.2,1);
            --green:#1a8a5c; --green-pale:#e6f9f1;
            --orange:#ea580c; --orange-pale:#fff7ed;
            --red:#dc2626; --red-pale:#fef2f2;
            --blue:#1d4ed8; --blue-pale:#eff6ff;
        }
        html,body { height:100%; font-family:'DM Sans',sans-serif; background:var(--surface-2); color:var(--text-primary); font-size:15px; line-height:1.6; }
        .app-shell { display:flex; min-height:100vh; }

        /* ── Sidebar ── */
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
        .nav-project-item.active { background:rgba(255,255,255,.14); color:#fff; font-weight:600; }
        .nav-project-item.active::before { content:''; position:absolute; left:0; top:20%; bottom:20%; width:3px; background:var(--accent); border-radius:0 3px 3px 0; }
        .nav-project-dot { width:7px; height:7px; border-radius:50%; flex-shrink:0; }
        .nav-project-dot.active    { background:#34d399; }
        .nav-project-dot.completed { background:rgba(255,255,255,.4); }
        .nav-project-dot.archived  { background:rgba(255,255,255,.2); }
        .nav-projects-empty { padding:8px 12px 8px 20px; font-size:12px; color:rgba(255,255,255,.3); font-style:italic; }
        @keyframes navShimmer { 0%,100%{opacity:.4} 50%{opacity:.8} }
        .sidebar-footer { border-top:1px solid rgba(255,255,255,.1); padding:14px 10px; }
        .user-card { display:flex; align-items:center; gap:10px; padding:8px 10px; border-radius:var(--radius-sm); cursor:pointer; }
        .user-card:hover { background:rgba(255,255,255,.08); }
        .user-avatar { width:34px; height:34px; border-radius:50%; background:var(--brand-light); display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:700; color:#fff; flex-shrink:0; border:2px solid rgba(255,255,255,.2); overflow:hidden; }
        .user-avatar img { width:100%; height:100%; object-fit:cover; }
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
        .topbar-date { font-size:13px; color:var(--text-muted); }
        .topbar-avatar { width:36px; height:36px; border-radius:50%; background:var(--brand); color:#fff; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:700; border:2px solid var(--brand-pale2); }
        .content { flex:1; padding:32px; }

        /* ── Page header ── */
        .page-header { margin-bottom:28px; }
        .page-header h1 { font-family:'Playfair Display',serif; font-size:30px; color:var(--text-primary); margin-bottom:4px; }
        .page-header p  { font-size:14px; color:var(--text-secondary); }

        /* ── Project filter pill row ── */
        .project-pills { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:28px; }
        .project-pill {
            padding:6px 16px; border-radius:20px; border:1.5px solid var(--border);
            background:var(--surface); font-family:'DM Sans',sans-serif;
            font-size:13px; font-weight:500; color:var(--text-secondary);
            cursor:pointer; transition:all var(--transition);
        }
        .project-pill:hover  { border-color:var(--brand); color:var(--brand); }
        .project-pill.active { background:var(--brand); color:#fff; border-color:var(--brand); }

        /* ── Headline stats ── */
        .headline-grid { display:grid; grid-template-columns:repeat(5,1fr); gap:14px; margin-bottom:24px; }
        .stat-card {
            background:var(--surface); border:1px solid var(--border);
            border-radius:var(--radius); padding:18px 20px;
            display:flex; align-items:center; gap:14px;
            box-shadow:var(--shadow-sm);
            transition:box-shadow var(--transition),transform var(--transition);
            animation:fadeUp .35s ease both;
        }
        .stat-card:hover { box-shadow:var(--shadow-md); transform:translateY(-2px); }
        .stat-icon { width:44px; height:44px; border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .stat-icon svg { width:22px; height:22px; stroke:currentColor; fill:none; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }
        .stat-icon.total    { background:var(--brand-pale);  color:var(--brand); }
        .stat-icon.done     { background:var(--green-pale);  color:var(--green); }
        .stat-icon.rate     { background:var(--blue-pale);   color:var(--blue); }
        .stat-icon.overdue  { background:var(--red-pale);    color:var(--red); }
        .stat-icon.members  { background:var(--orange-pale); color:var(--orange); }
        .stat-val { font-family:'Playfair Display',serif; font-size:26px; font-weight:700; color:var(--text-primary); line-height:1; }
        .stat-lbl { font-size:11px; color:var(--text-muted); font-weight:500; text-transform:uppercase; letter-spacing:.5px; margin-top:3px; }

        /* ── Charts row ── */
        .charts-row { display:grid; grid-template-columns:1fr 340px; gap:20px; margin-bottom:20px; }

        /* ── Panel ── */
        .panel { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); box-shadow:var(--shadow-sm); overflow:hidden; animation:fadeUp .35s ease both; }
        .panel-header { padding:18px 24px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; gap:12px; }
        .panel-header h2 { font-family:'Playfair Display',serif; font-size:17px; color:var(--text-primary); }
        .panel-body { padding:20px 24px; }

        /* ── Bar chart (weekly) ── */
        .bar-chart { display:flex; align-items:flex-end; gap:8px; height:160px; padding:0 4px; }
        .bar-col { flex:1; display:flex; flex-direction:column; align-items:center; gap:6px; height:100%; justify-content:flex-end; }
        .bar-fill {
            width:100%; border-radius:6px 6px 0 0;
            background:linear-gradient(180deg, var(--brand-light), var(--brand));
            min-height:4px;
            transition:height .5s cubic-bezier(.4,0,.2,1);
            position:relative;
        }
        .bar-fill:hover { opacity:.85; }
        .bar-fill .bar-tooltip {
            display:none; position:absolute; top:-28px; left:50%; transform:translateX(-50%);
            background:#1a1218; color:#fff; font-size:11px; font-weight:600;
            padding:3px 7px; border-radius:5px; white-space:nowrap;
        }
        .bar-fill:hover .bar-tooltip { display:block; }
        .bar-lbl { font-size:10px; color:var(--text-muted); font-weight:600; }

        /* ── Donut ── */
        .donut-wrap { display:flex; flex-direction:column; align-items:center; gap:16px; padding:8px 0; }
        .donut-svg-wrap { position:relative; width:160px; height:160px; }
        .donut-svg { width:160px; height:160px; transform:rotate(-90deg); }
        .donut-center { position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); text-align:center; }
        .donut-center-val { font-family:'Playfair Display',serif; font-size:30px; font-weight:700; color:var(--text-primary); line-height:1; }
        .donut-center-lbl { font-size:11px; color:var(--text-muted); }
        .donut-legend { display:grid; grid-template-columns:1fr 1fr; gap:8px; width:100%; }
        .legend-item { display:flex; align-items:center; gap:7px; }
        .legend-dot { width:9px; height:9px; border-radius:50%; flex-shrink:0; }
        .legend-lbl { font-size:12px; color:var(--text-muted); flex:1; text-transform:capitalize; }
        .legend-val { font-size:12px; font-weight:700; color:var(--text-primary); }

        /* ── Bottom row ── */
        .bottom-row { display:grid; grid-template-columns:1fr 360px; gap:20px; }

        /* ── Project health table ── */
        .health-table { width:100%; border-collapse:collapse; }
        .health-table th { font-size:11px; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:.5px; padding:10px 16px; border-bottom:1px solid var(--border); text-align:left; background:var(--surface-2); }
        .health-table td { padding:12px 16px; border-bottom:1px solid var(--border); font-size:13px; color:var(--text-primary); vertical-align:middle; }
        .health-table tr:last-child td { border-bottom:none; }
        .health-table tr:hover td { background:var(--surface-2); }
        .progress-mini { height:6px; background:var(--border); border-radius:3px; overflow:hidden; min-width:80px; }
        .progress-mini-fill { height:100%; border-radius:3px; background:linear-gradient(90deg,var(--brand),var(--brand-light)); transition:width .5s ease; }
        .pill { display:inline-flex; align-items:center; gap:4px; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600; text-transform:capitalize; }
        .pill::before { content:''; width:5px; height:5px; border-radius:50%; }
        .pill.active    { background:var(--green-pale); color:var(--green); } .pill.active::before { background:var(--green); }
        .pill.completed { background:var(--brand-pale2); color:var(--brand); } .pill.completed::before { background:var(--brand); }
        .pill.archived  { background:var(--surface-2); color:var(--text-muted); } .pill.archived::before { background:var(--text-muted); }

        /* ── Member workload ── */
        .member-workload-row { display:flex; align-items:center; gap:12px; padding:12px 20px; border-bottom:1px solid var(--border); }
        .member-workload-row:last-child { border-bottom:none; }
        .mw-avatar { width:36px; height:36px; border-radius:50%; background:var(--brand); color:#fff; font-size:12px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; overflow:hidden; }
        .mw-avatar img { width:100%; height:100%; object-fit:cover; }
        .mw-info { flex:1; min-width:0; }
        .mw-name { font-size:13px; font-weight:600; color:var(--text-primary); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .mw-bar-wrap { height:4px; background:var(--border); border-radius:2px; margin-top:5px; overflow:hidden; }
        .mw-bar-fill { height:100%; border-radius:2px; background:linear-gradient(90deg,var(--brand),var(--brand-light)); transition:width .5s ease; }
        .mw-stat { text-align:right; flex-shrink:0; }
        .mw-count { font-family:'Playfair Display',serif; font-size:16px; font-weight:700; color:var(--brand); line-height:1; }
        .mw-label { font-size:10px; color:var(--text-muted); }

        /* ── Priority bars ── */
        .priority-row { display:flex; align-items:center; gap:10px; margin-bottom:10px; }
        .priority-row:last-child { margin-bottom:0; }
        .priority-lbl { font-size:12px; font-weight:600; color:var(--text-secondary); width:60px; text-transform:capitalize; }
        .priority-track { flex:1; height:8px; background:var(--border); border-radius:4px; overflow:hidden; }
        .priority-fill { height:100%; border-radius:4px; transition:width .5s ease; }
        .priority-fill.critical { background:#dc2626; }
        .priority-fill.high     { background:#f97316; }
        .priority-fill.medium   { background:#f59e0b; }
        .priority-fill.low      { background:#94a3b8; }
        .priority-count { font-size:12px; font-weight:700; color:var(--text-primary); min-width:24px; text-align:right; }

        /* ── Skeleton ── */
        .sk-line { background:var(--border); border-radius:4px; animation:shimmer 1.4s infinite; }
        @keyframes shimmer { 0%,100%{opacity:.5} 50%{opacity:1} }
        @keyframes fadeUp  { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }

        /* ── Toast ── */
        .toast { position:fixed; bottom:28px; right:28px; background:#1a1218; color:#fff; padding:12px 18px; border-radius:10px; display:flex; align-items:center; gap:10px; font-size:14px; font-weight:500; box-shadow:0 8px 24px rgba(0,0,0,.2); transform:translateY(12px); opacity:0; transition:all .25s cubic-bezier(.4,0,.2,1); z-index:999; }
        .toast.show { transform:translateY(0); opacity:1; }
        .toast-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; background:#34d399; }
        .toast.error .toast-dot { background:#f87171; }

        /* ── Empty state ── */
        .empty-state { display:flex; flex-direction:column; align-items:center; padding:48px 24px; text-align:center; }
        .empty-state svg { width:48px; height:48px; stroke:var(--border); margin-bottom:12px; }
        .empty-state p { font-size:14px; color:var(--text-muted); }

        @media (max-width:1200px) {
            .headline-grid { grid-template-columns:repeat(3,1fr); }
            .charts-row    { grid-template-columns:1fr; }
            .bottom-row    { grid-template-columns:1fr; }
        }
        @media (max-width:760px) {
            .headline-grid { grid-template-columns:repeat(2,1fr); }
        }
    </style>
</head>
<body>
<div class="app-shell">

    <!-- ── Sidebar ── -->
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

            <!-- Projects dropdown -->
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
                    <div style="height:32px;border-radius:6px;background:rgba(255,255,255,.08);margin-bottom:4px;animation:navShimmer 1.4s infinite;animation-delay:.15s"></div>
                </div>
                <div id="projects-nav-items"></div>
            </div>

            <div class="nav-section-label" style="margin-top:6px">Manage</div>
            <a href="<?= $basePath ?>/user/analytics" class="nav-item active">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="20" x2="18" y2="10"/>
                    <line x1="12" y1="20" x2="12" y2="4"/>
                    <line x1="6"  y1="20" x2="6"  y2="14"/>
                    <line x1="2"  y1="20" x2="22" y2="20"/>
                </svg>
                Analytics
            </a>

            <div class="nav-section-label" style="margin-top:6px">You</div>
            <a href="<?= $basePath ?>/user/notifications"
               class="nav-item <?= strpos($_SERVER['REQUEST_URI'], '/notifications') !== false ? 'active' : '' ?>"
               id="nav-notifications-link">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
                <span style="flex:1">Notifications</span>
                <span id="nav-notif-badge" data-notif-badge style="display:none;min-width:18px;height:18px;padding:0 5px;border-radius:20px;background:var(--accent);color:var(--brand);font-size:10px;font-weight:700;line-height:18px;text-align:center;flex-shrink:0"></span>
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

    <!-- ── Main ── -->
    <div class="main">
        <header class="topbar">
            <div class="topbar-title">Analytics</div>
            <div class="topbar-right">
                <span class="topbar-date" id="topbar-date"></span>
                <div class="topbar-avatar"><?= htmlspecialchars($userInitial) ?></div>
            </div>
        </header>

        <main class="content">

            <div class="page-header">
                <h1>Analytics</h1>
                <p id="analytics-subtitle">Overview of your projects and team performance.</p>
            </div>

            <!-- Project filter pills -->
            <div class="project-pills" id="project-pills">
                <button class="project-pill active" data-id="all" onclick="filterByProject('all', this)">All Projects</button>
                <!-- injected by JS -->
            </div>

            <!-- Headline stats -->
            <div class="headline-grid">
                <div class="stat-card" style="animation-delay:.04s">
                    <div class="stat-icon total">
                        <svg viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    </div>
                    <div><div class="stat-val" id="h-total">—</div><div class="stat-lbl">Total Tasks</div></div>
                </div>
                <div class="stat-card" style="animation-delay:.08s">
                    <div class="stat-icon done">
                        <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <div><div class="stat-val" id="h-done">—</div><div class="stat-lbl">Completed</div></div>
                </div>
                <div class="stat-card" style="animation-delay:.12s">
                    <div class="stat-icon rate">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div><div class="stat-val" id="h-rate">—</div><div class="stat-lbl">Completion Rate</div></div>
                </div>
                <div class="stat-card" style="animation-delay:.16s">
                    <div class="stat-icon overdue">
                        <svg viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    </div>
                    <div><div class="stat-val" id="h-overdue">—</div><div class="stat-lbl">Overdue</div></div>
                </div>
                <div class="stat-card" style="animation-delay:.20s">
                    <div class="stat-icon members">
                        <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                    <div><div class="stat-val" id="h-members">—</div><div class="stat-lbl">Active Members</div></div>
                </div>
            </div>

            <!-- Charts row -->
            <div class="charts-row">

                <!-- Weekly completions bar chart -->
                <div class="panel" style="animation-delay:.12s">
                    <div class="panel-header">
                        <h2>Weekly Task Completions</h2>
                        <span style="font-size:12px;color:var(--text-muted)">Last 8 weeks</span>
                    </div>
                    <div class="panel-body">
                        <div class="bar-chart" id="bar-chart">
                            <!-- skeleton bars -->
                            <?php for($i=0;$i<8;$i++): ?>
                            <div class="bar-col">
                                <div class="sk-line" style="width:100%;height:<?= rand(20,100) ?>px;border-radius:6px 6px 0 0"></div>
                                <div class="sk-line" style="width:28px;height:9px"></div>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>

                <!-- Priority donut -->
                <div class="panel" style="animation-delay:.16s">
                    <div class="panel-header">
                        <h2>Priority Split</h2>
                    </div>
                    <div class="panel-body">
                        <div class="donut-wrap" id="donut-wrap">
                            <div class="sk-line" style="width:160px;height:160px;border-radius:50%"></div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Bottom row -->
            <div class="bottom-row">

                <!-- Project health -->
                <div class="panel" style="animation-delay:.2s">
                    <div class="panel-header">
                        <h2>Project Health</h2>
                    </div>
                    <div style="overflow-x:auto">
                        <table class="health-table" id="health-table">
                            <thead>
                                <tr>
                                    <th>Project</th>
                                    <th>Status</th>
                                    <th>Progress</th>
                                    <th style="text-align:center">Tasks</th>
                                    <th style="text-align:center">Done</th>
                                    <th style="text-align:center">Overdue</th>
                                </tr>
                            </thead>
                            <tbody id="health-body">
                                <tr><td colspan="6" style="text-align:center;padding:32px;color:var(--text-muted);font-size:13px">Loading…</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Member workload -->
                <div class="panel" style="animation-delay:.24s">
                    <div class="panel-header">
                        <h2>Team Workload</h2>
                    </div>
                    <div id="workload-list">
                        <div style="padding:16px 20px">
                            <div class="sk-line" style="height:44px;border-radius:8px;margin-bottom:10px"></div>
                            <div class="sk-line" style="height:44px;border-radius:8px;margin-bottom:10px"></div>
                            <div class="sk-line" style="height:44px;border-radius:8px"></div>
                        </div>
                    </div>
                </div>

            </div>

        </main>
    </div>
</div>

<div class="toast" id="toast"><div class="toast-dot"></div><span id="toast-msg"></span></div>

<script>
    window.WH_BASE    = '<?= $basePath ?>';
    window.WH_ROLE    = '<?= $userRole ?>';
    window.WH_USER_ID = <?= (int) ($_SESSION['user_id'] ?? 0) ?>;
    const BASE        = window.WH_BASE;
    const IS_MANAGER  = true;
</script>
<script src="<?= $basePath ?>/js/notif-poll.js"></script>
<script src="<?= $basePath ?>/js/user-analytics.js"></script>
</body>
</html>