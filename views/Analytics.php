<?php
// views/Analytics.php
$basePath     = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$adminEmail   = $_SESSION['admin_email'] ?? 'admin@workhub.com';
$adminInitial = strtoupper(substr($adminEmail, 0, 1));
$adminHandle  = explode('@', $adminEmail)[0];
$activePage   = 'analytics';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics — WorkHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $basePath ?>/css/dashboard.css">
    <style>
        .analytics-grid { display: grid; grid-template-columns: repeat(auto-fill,minmax(200px,1fr)); gap:14px; margin-bottom:28px; }
        .a-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); padding:20px; box-shadow:var(--shadow-sm); display:flex; align-items:center; gap:14px; transition:box-shadow var(--transition),transform var(--transition); }
        .a-card:hover { box-shadow:var(--shadow-md); transform:translateY(-2px); }
        .a-icon { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .a-icon svg { width:22px; height:22px; stroke:currentColor; }
        .a-icon.members  { background:var(--brand-pale);  color:var(--brand); }
        .a-icon.projects { background:#eff6ff;              color:#1a56db; }
        .a-icon.tasks    { background:#e6f9f1;              color:#1a8a5c; }
        .a-icon.overdue  { background:#fef2f2;              color:#dc2626; }
        .a-icon.done     { background:#e6f9f1;              color:#1a8a5c; }
        .a-val { font-family:'Playfair Display',serif; font-size:26px; font-weight:700; color:var(--text-primary); line-height:1; }
        .a-lbl { font-size:11px; color:var(--text-muted); font-weight:600; text-transform:uppercase; letter-spacing:.5px; margin-top:3px; }

        .two-col { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px; }
        .three-col { display:grid; grid-template-columns:1fr 1fr 1fr; gap:20px; margin-bottom:20px; }

        .panel { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); box-shadow:var(--shadow-sm); overflow:hidden; }
        .panel-hd { padding:16px 20px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; }
        .panel-hd h3 { font-family:'Playfair Display',serif; font-size:16px; color:var(--text-primary); }

        /* Chart canvas wrappers */
        .chart-box { padding:20px; position:relative; height:240px; }

        /* Progress bars */
        .prog-row { display:flex; align-items:center; gap:10px; padding:10px 20px; border-bottom:1px solid var(--border); }
        .prog-row:last-child { border-bottom:none; }
        .prog-name { font-size:13px; font-weight:500; color:var(--text-primary); width:130px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; flex-shrink:0; }
        .prog-track { flex:1; height:6px; background:var(--border); border-radius:3px; overflow:hidden; }
        .prog-fill { height:100%; border-radius:3px; background:linear-gradient(90deg,var(--brand),var(--brand-light)); transition:width .6s ease; }
        .prog-pct { font-size:12px; font-weight:700; color:var(--brand); width:34px; text-align:right; flex-shrink:0; }

        /* Performer rows */
        .perf-row { display:flex; align-items:center; gap:10px; padding:12px 20px; border-bottom:1px solid var(--border); }
        .perf-row:last-child { border-bottom:none; }
        .perf-avatar { width:34px; height:34px; border-radius:50%; background:var(--brand); color:#fff; font-size:12px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; overflow:hidden; }
        .perf-avatar img { width:100%; height:100%; object-fit:cover; }
        .perf-info { flex:1; min-width:0; }
        .perf-name { font-size:13px; font-weight:600; color:var(--text-primary); }
        .perf-sub  { font-size:11px; color:var(--text-muted); }
        .perf-badge { font-size:12px; font-weight:700; color:var(--brand); font-family:'Playfair Display',serif; }

        /* Activity log */
        .log-filters { display:flex; gap:8px; flex-wrap:wrap; padding:12px 20px; border-bottom:1px solid var(--border); }
        .log-filters select { padding:6px 10px; border:1.5px solid var(--border); border-radius:8px; font-family:'DM Sans',sans-serif; font-size:12px; color:var(--text-primary); background:var(--surface-2); outline:none; cursor:pointer; transition:border-color var(--transition); }
        .log-filters select:focus { border-color:var(--brand); }

        .log-row { display:flex; align-items:flex-start; gap:12px; padding:12px 20px; border-bottom:1px solid var(--border); transition:background var(--transition); }
        .log-row:last-child { border-bottom:none; }
        .log-row:hover { background:var(--surface-2); }
        .log-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; margin-top:5px; }
        .log-dot.admin { background:var(--brand); }
        .log-dot.user  { background:#1a56db; }
        .log-body { flex:1; min-width:0; }
        .log-text { font-size:13px; color:var(--text-primary); line-height:1.5; }
        .log-text b { color:var(--brand); }
        .log-meta { font-size:11px; color:var(--text-muted); margin-top:2px; }
        .log-time { font-size:11px; color:var(--text-muted); white-space:nowrap; flex-shrink:0; }

        .load-more { text-align:center; padding:14px; border-top:1px solid var(--border); }
        .btn-load { background:var(--surface-2); border:1.5px solid var(--border); border-radius:8px; padding:7px 20px; font-family:'DM Sans',sans-serif; font-size:13px; font-weight:600; color:var(--text-secondary); cursor:pointer; transition:all var(--transition); }
        .btn-load:hover { border-color:var(--brand); color:var(--brand); }

        .sk-line { background:var(--border); border-radius:4px; animation:shimmer 1.4s infinite; }
        @keyframes shimmer { 0%,100%{opacity:.5} 50%{opacity:1} }
        @media(max-width:1100px) { .two-col,.three-col { grid-template-columns:1fr; } }
    </style>
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/../partials/sidebar.php'; ?>
    <div class="main">
        <header class="topbar">
            <div class="topbar-title">Analytics</div>
            <div class="topbar-right">
                <span class="topbar-date" id="topbarDate"></span>
                <div class="topbar-avatar"><?= htmlspecialchars($adminInitial) ?></div>
            </div>
        </header>
        <main class="content">

            <!-- Summary cards -->
            <div class="analytics-grid" id="summary-cards">
                <?php for($i=0;$i<6;$i++): ?>
                <div class="a-card"><div class="sk-line" style="width:44px;height:44px;border-radius:12px"></div><div><div class="sk-line" style="width:60px;height:22px;margin-bottom:6px"></div><div class="sk-line" style="width:80px;height:10px"></div></div></div>
                <?php endfor; ?>
            </div>

            <!-- Charts row 1 -->
            <div class="two-col">
                <div class="panel">
                    <div class="panel-hd"><h3>Task Creation — Last 30 Days</h3></div>
                    <div class="chart-box"><canvas id="chart-trend"></canvas></div>
                </div>
                <div class="panel">
                    <div class="panel-hd"><h3>Task Status Breakdown</h3></div>
                    <div class="chart-box" style="display:flex;align-items:center;justify-content:center">
                        <canvas id="chart-status" style="max-width:220px;max-height:220px"></canvas>
                    </div>
                </div>
            </div>

            <!-- Charts row 2 -->
            <div class="three-col">
                <div class="panel">
                    <div class="panel-hd"><h3>Priority Split</h3></div>
                    <div class="chart-box"><canvas id="chart-priority"></canvas></div>
                </div>
                <div class="panel">
                    <div class="panel-hd"><h3>Top Performers</h3></div>
                    <div id="performers-list">
                        <div style="padding:20px"><div class="sk-line" style="height:40px;margin-bottom:8px;border-radius:8px"></div><div class="sk-line" style="height:40px;margin-bottom:8px;border-radius:8px"></div><div class="sk-line" style="height:40px;border-radius:8px"></div></div>
                    </div>
                </div>
                <div class="panel">
                    <div class="panel-hd"><h3>Project Progress</h3></div>
                    <div id="project-progress-list">
                        <div style="padding:20px"><div class="sk-line" style="height:12px;margin-bottom:10px;border-radius:6px"></div><div class="sk-line" style="height:12px;margin-bottom:10px;border-radius:6px"></div><div class="sk-line" style="height:12px;border-radius:6px"></div></div>
                    </div>
                </div>
            </div>

            <!-- Activity log -->
            <div class="panel" style="margin-bottom:28px">
                <div class="panel-hd">
                    <h3>Activity Log</h3>
                    <span id="log-count" style="font-size:12px;color:var(--text-muted)"></span>
                </div>
                <div class="log-filters">
                    <select id="filter-action" onchange="reloadLogs()"><option value="">All Actions</option></select>
                    <select id="filter-entity" onchange="reloadLogs()">
                        <option value="">All Entities</option>
                        <option value="task">Tasks</option>
                        <option value="project">Projects</option>
                        <option value="member">Members</option>
                        <option value="meeting">Meetings</option>
                        <option value="comment">Comments</option>
                        <option value="attachment">Attachments</option>
                    </select>
                    <select id="filter-actor" onchange="reloadLogs()">
                        <option value="">All Actors</option>
                        <option value="admin">Admin</option>
                        <option value="user">Members</option>
                    </select>
                </div>
                <div id="log-list">
                    <div style="padding:24px;text-align:center"><div class="sk-line" style="height:14px;width:70%;margin:0 auto 12px"></div><div class="sk-line" style="height:14px;width:50%;margin:0 auto"></div></div>
                </div>
                <div class="load-more" id="load-more-wrap" style="display:none">
                    <button class="btn-load" onclick="loadMoreLogs()">Load More</button>
                </div>
            </div>

        </main>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script> window.WH_BASE = '<?= $basePath ?>'; </script>
<script src="<?= $basePath ?>/js/app.js"></script>
<script src="<?= $basePath ?>/js/analytics.js"></script>
</body>
</html>