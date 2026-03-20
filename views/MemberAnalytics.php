<?php
// views/MemberAnalytics.php
$basePath     = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$adminEmail   = $_SESSION['admin_email'] ?? 'admin@workhub.com';
$adminInitial = strtoupper(substr($adminEmail, 0, 1));
$adminHandle  = explode('@', $adminEmail)[0];
$activePage   = 'members';

$userId = (int) ($_GET['id'] ?? 0);
if (!$userId) {
    header("Location: $basePath/members");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Analytics — WorkHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $basePath ?>/css/dashboard.css">
    <style>
        /* ── Hero ── */
        .member-hero {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: var(--radius); box-shadow: var(--shadow-sm);
            overflow: hidden; margin-bottom: 24px;
        }
        .hero-banner {
            height: 100px;
            background: linear-gradient(135deg, var(--brand) 0%, var(--brand-light) 60%, var(--accent) 100%);
        }
        .hero-body {
            padding: 0 28px 24px;
            display: flex; align-items: flex-end; gap: 20px; flex-wrap: wrap;
        }
        .hero-avatar {
            width: 80px; height: 80px; border-radius: 50%;
            border: 4px solid var(--surface); object-fit: cover;
            margin-top: -40px; flex-shrink: 0; background: var(--brand);
            display: flex; align-items: center; justify-content: center;
            font-family: 'Playfair Display', serif; font-size: 28px;
            font-weight: 700; color: #fff; overflow: hidden;
        }
        .hero-avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .hero-info { flex: 1; min-width: 0; padding-top: 12px; }
        .hero-name { font-family: 'Playfair Display', serif; font-size: 22px; font-weight: 700; color: var(--text-primary); }
        .hero-email { font-size: 13px; color: var(--text-muted); margin-top: 2px; }
        .hero-meta  { display: flex; gap: 10px; margin-top: 10px; flex-wrap: wrap; }
        .hero-chip  {
            padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600;
            text-transform: capitalize; border: 1.5px solid var(--border);
            background: var(--surface-2); color: var(--text-secondary);
        }
        .hero-chip.manager { background: var(--brand-pale2); color: var(--brand); border-color: var(--brand-pale2); }
        .hero-chip.member  { background: var(--surface-2); color: var(--text-muted); }
        .hero-back {
            align-self: flex-start; margin-top: 16px;
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 16px; border-radius: var(--radius-sm);
            border: 1.5px solid var(--border); background: var(--surface);
            font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 600;
            color: var(--text-secondary); cursor: pointer; text-decoration: none;
            transition: all var(--transition);
        }
        .hero-back:hover { border-color: var(--brand); color: var(--brand); }
        .hero-back svg { width: 14px; height: 14px; stroke: currentColor; }

        /* ── KPI row ── */
        .kpi-row { display: grid; grid-template-columns: repeat(6, 1fr); gap: 14px; margin-bottom: 24px; }
        .kpi-card {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 16px 14px; text-align: center;
            box-shadow: var(--shadow-sm); transition: box-shadow var(--transition), transform var(--transition);
        }
        .kpi-card:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }
        .kpi-val   { font-family: 'Playfair Display', serif; font-size: 26px; font-weight: 700; color: var(--text-primary); line-height: 1; }
        .kpi-lbl   { font-size: 10.5px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: .5px; margin-top: 5px; }
        .kpi-card.overdue .kpi-val  { color: #dc2626; }
        .kpi-card.done .kpi-val     { color: #1a8a5c; }
        .kpi-card.progress .kpi-val { color: #ea580c; }

        /* ── Panels ── */
        .two-col   { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .three-col { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .panel     { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow-sm); overflow: hidden; }
        .panel-hd  { padding: 15px 20px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
        .panel-hd h3 { font-family: 'Playfair Display', serif; font-size: 15px; color: var(--text-primary); }
        .chart-box { padding: 20px; height: 220px; position: relative; }

        /* ── Progress bars (status/priority) ── */
        .bar-row   { padding: 12px 20px; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 10px; }
        .bar-row:last-child { border-bottom: none; }
        .bar-label { font-size: 12.5px; color: var(--text-primary); font-weight: 500; width: 90px; flex-shrink: 0; text-transform: capitalize; }
        .bar-track { flex: 1; height: 7px; background: var(--border); border-radius: 4px; overflow: hidden; }
        .bar-fill  { height: 100%; border-radius: 4px; transition: width .6s ease; }
        .bar-count { font-size: 12px; font-weight: 700; color: var(--text-secondary); width: 28px; text-align: right; flex-shrink: 0; }

        /* status colors */
        .fill-completed  { background: #1a8a5c; }
        .fill-in_progress{ background: #ea580c; }
        .fill-in_review  { background: var(--brand); }
        .fill-pending    { background: var(--border); border: 1px solid var(--text-muted); }
        /* priority colors */
        .fill-critical   { background: #dc2626; }
        .fill-high       { background: #f97316; }
        .fill-medium     { background: #f59e0b; }
        .fill-low        { background: #94a3b8; }

        /* ── Task list ── */
        .task-row {
            display: flex; align-items: center; gap: 12px; padding: 11px 20px;
            border-bottom: 1px solid var(--border); transition: background var(--transition);
        }
        .task-row:last-child { border-bottom: none; }
        .task-row:hover { background: var(--surface-2); }
        .task-priority-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
        .task-info  { flex: 1; min-width: 0; }
        .task-title { font-size: 13px; font-weight: 600; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .task-proj  { font-size: 11px; color: var(--text-muted); margin-top: 1px; }
        .task-status-badge {
            font-size: 10.5px; font-weight: 600; padding: 3px 9px; border-radius: 20px;
            flex-shrink: 0; text-transform: capitalize; white-space: nowrap;
        }
        .badge-completed   { background: #e6f9f1; color: #1a8a5c; }
        .badge-in_progress { background: #fff7ed; color: #ea580c; }
        .badge-in_review   { background: var(--brand-pale); color: var(--brand); }
        .badge-pending     { background: var(--surface-2); color: var(--text-muted); border: 1px solid var(--border); }
        .task-due   { font-size: 11px; color: var(--text-muted); flex-shrink: 0; }
        .task-due.overdue { color: #dc2626; font-weight: 600; }

        /* ── Projects list ── */
        .proj-row  { display: flex; align-items: center; gap: 12px; padding: 12px 20px; border-bottom: 1px solid var(--border); }
        .proj-row:last-child { border-bottom: none; }
        .proj-dot  { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
        .proj-dot.active    { background: #1a8a5c; }
        .proj-dot.completed { background: #1a56db; }
        .proj-dot.archived  { background: var(--text-muted); }
        .proj-info { flex: 1; min-width: 0; }
        .proj-name { font-size: 13px; font-weight: 600; color: var(--text-primary); }
        .proj-sub  { font-size: 11px; color: var(--text-muted); }
        .proj-role { font-size: 10.5px; font-weight: 600; padding: 2px 8px; border-radius: 20px; flex-shrink: 0; text-transform: capitalize; }
        .proj-role.manager { background: var(--brand-pale2); color: var(--brand); }
        .proj-role.member  { background: var(--surface-2); color: var(--text-muted); border: 1px solid var(--border); }

        /* ── Skeleton ── */
        .sk-line { background: var(--border); border-radius: 4px; animation: shimmer 1.4s infinite; }
        @keyframes shimmer { 0%,100%{opacity:.5} 50%{opacity:1} }

        /* ── Completion rate ring ── */
        .rate-wrap { display: flex; align-items: center; justify-content: center; padding: 20px; gap: 28px; }
        .rate-ring { position: relative; flex-shrink: 0; }
        .rate-ring svg { transform: rotate(-90deg); }
        .rate-ring-bg   { fill: none; stroke: var(--border); stroke-width: 10; }
        .rate-ring-fill { fill: none; stroke: var(--brand); stroke-width: 10; stroke-linecap: round; transition: stroke-dashoffset .8s ease; }
        .rate-center { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .rate-pct  { font-family: 'Playfair Display', serif; font-size: 26px; font-weight: 700; color: var(--text-primary); line-height: 1; }
        .rate-lbl  { font-size: 10px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: .5px; margin-top: 2px; }
        .rate-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .rate-stat-item { text-align: center; }
        .rate-stat-val  { font-family: 'Playfair Display', serif; font-size: 20px; font-weight: 700; color: var(--text-primary); }
        .rate-stat-lbl  { font-size: 10.5px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: .4px; }

        @media(max-width:1100px) { .kpi-row { grid-template-columns: repeat(3,1fr); } .two-col,.three-col { grid-template-columns:1fr; } }
        @media(max-width:640px)  { .kpi-row { grid-template-columns: repeat(2,1fr); } }
    </style>
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/../partials/sidebar.php'; ?>

    <div class="main">
        <header class="topbar">
            <div class="topbar-title" id="topbar-member-name">Member Analytics</div>
            <div class="topbar-right">
                <span class="topbar-date" id="topbarDate"></span>
                <div class="topbar-avatar"><?= htmlspecialchars($adminInitial) ?></div>
            </div>
        </header>

        <main class="content">

            <!-- Hero profile card -->
            <div class="member-hero">
                <div class="hero-banner"></div>
                <div class="hero-body">
                    <div class="hero-avatar" id="hero-avatar">
                        <div class="sk-line" style="width:100%;height:100%;border-radius:50%"></div>
                    </div>
                    <div class="hero-info" id="hero-info">
                        <div class="sk-line" style="width:200px;height:20px;margin-bottom:8px"></div>
                        <div class="sk-line" style="width:140px;height:12px"></div>
                    </div>
                    <a href="<?= $basePath ?>/members" class="hero-back">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="15 18 9 12 15 6"/>
                        </svg>
                        Back to Members
                    </a>
                </div>
            </div>

            <!-- KPI cards -->
            <div class="kpi-row" id="kpi-row">
                <?php for($i=0;$i<6;$i++): ?>
                <div class="kpi-card">
                    <div class="sk-line" style="height:26px;width:50px;margin:0 auto 8px"></div>
                    <div class="sk-line" style="height:10px;width:70px;margin:0 auto"></div>
                </div>
                <?php endfor; ?>
            </div>

            <!-- Row 1: completion ring + trend chart -->
            <div class="two-col">
                <div class="panel">
                    <div class="panel-hd"><h3>Completion Overview</h3></div>
                    <div class="rate-wrap" id="rate-wrap">
                        <div class="sk-line" style="width:120px;height:120px;border-radius:50%"></div>
                        <div class="rate-stats">
                            <div class="sk-line" style="width:60px;height:40px"></div>
                            <div class="sk-line" style="width:60px;height:40px"></div>
                        </div>
                    </div>
                </div>
                <div class="panel">
                    <div class="panel-hd"><h3>Tasks Completed — Last 30 Days</h3></div>
                    <div class="chart-box"><canvas id="chart-trend"></canvas></div>
                </div>
            </div>

            <!-- Row 2: status breakdown + priority breakdown + projects -->
            <div class="three-col">
                <div class="panel">
                    <div class="panel-hd"><h3>Task Status</h3></div>
                    <div id="status-bars">
                        <?php for($i=0;$i<4;$i++): ?>
                        <div class="bar-row">
                            <div class="sk-line" style="width:80px;height:11px"></div>
                            <div class="sk-line" style="flex:1;height:7px;margin:0 10px"></div>
                            <div class="sk-line" style="width:20px;height:11px"></div>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="panel">
                    <div class="panel-hd"><h3>Task Priority</h3></div>
                    <div id="priority-bars">
                        <?php for($i=0;$i<4;$i++): ?>
                        <div class="bar-row">
                            <div class="sk-line" style="width:70px;height:11px"></div>
                            <div class="sk-line" style="flex:1;height:7px;margin:0 10px"></div>
                            <div class="sk-line" style="width:20px;height:11px"></div>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="panel">
                    <div class="panel-hd"><h3>Projects</h3></div>
                    <div id="projects-list">
                        <?php for($i=0;$i<4;$i++): ?>
                        <div class="proj-row">
                            <div class="sk-line" style="width:8px;height:8px;border-radius:50%"></div>
                            <div style="flex:1"><div class="sk-line" style="height:12px;width:80%;margin-bottom:5px"></div><div class="sk-line" style="height:9px;width:50%"></div></div>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>

            <!-- Row 3: full task list -->
            <div class="panel" style="margin-bottom:28px">
                <div class="panel-hd">
                    <h3>All Assigned Tasks</h3>
                    <span id="task-count" style="font-size:12px;color:var(--text-muted)"></span>
                </div>
                <div id="task-list">
                    <?php for($i=0;$i<6;$i++): ?>
                    <div class="task-row">
                        <div class="sk-line" style="width:8px;height:8px;border-radius:50%"></div>
                        <div style="flex:1"><div class="sk-line" style="height:12px;width:60%;margin-bottom:5px"></div><div class="sk-line" style="height:9px;width:30%"></div></div>
                        <div class="sk-line" style="width:70px;height:20px;border-radius:20px"></div>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>

        </main>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
    window.WH_BASE  = '<?= $basePath ?>';
    window.MEMBER_ID = <?= $userId ?>;
</script>
<script src="<?= $basePath ?>/js/app.js"></script>
<script src="<?= $basePath ?>/js/member-analytics.js"></script>
</body>
</html>