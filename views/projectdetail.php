<?php
// views/ProjectDetail.php
$basePath     = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$adminEmail   = $_SESSION['admin_email'] ?? 'admin@workhub.com';
$adminInitial = strtoupper(substr($adminEmail, 0, 1));
$adminHandle  = explode('@', $adminEmail)[0];
$activePage   = 'projects';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project — WorkHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $basePath ?>/css/dashboard.css">
    <style>

        /* ── Back nav ── */
        .back-nav {
            display: inline-flex; align-items: center; gap: 8px;
            font-size: 13px; font-weight: 600; color: var(--text-muted);
            text-decoration: none; margin-bottom: 22px;
            transition: color var(--transition);
        }
        .back-nav:hover { color: var(--brand); }
        .back-nav svg { width: 15px; height: 15px; stroke: currentColor; }

        /* ── Project hero ── */
        .project-hero {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 28px 32px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-sm);
            position: relative;
            overflow: hidden;
        }

        .project-hero::before {
            content: '';
            position: absolute; top: 0; left: 0; right: 0; height: 4px;
            background: linear-gradient(90deg, var(--brand), var(--brand-light), var(--accent));
        }

        .project-hero-top {
            display: flex; align-items: flex-start;
            justify-content: space-between; gap: 16px;
            flex-wrap: wrap; margin-bottom: 20px;
        }

        .project-hero-title {
            font-family: 'Playfair Display', serif;
            font-size: 32px; color: var(--text-primary);
            line-height: 1.15; margin-bottom: 6px;
        }

        .project-hero-desc {
            font-size: 14px; color: var(--text-secondary);
            line-height: 1.6; max-width: 600px;
        }

        .project-hero-meta {
            display: flex; align-items: center; gap: 16px;
            flex-wrap: wrap; margin-top: 8px;
        }

        .meta-chip {
            display: inline-flex; align-items: center; gap: 5px;
            font-size: 12px; color: var(--text-muted); font-weight: 500;
        }
        .meta-chip svg { width: 13px; height: 13px; stroke: currentColor; }

        /* Status pill (reusing dashboard.css but adding archived) */
        .status-pill { display: inline-flex; align-items: center; gap: 5px; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: capitalize; }
        .status-pill::before { content:''; width:6px; height:6px; border-radius:50%; }
        .status-pill.active    { background:#e6f9f1; color:#1a8a5c; } .status-pill.active::before    { background:#1a8a5c; }
        .status-pill.completed { background:var(--brand-pale2); color:var(--brand); } .status-pill.completed::before { background:var(--brand); }
        .status-pill.archived  { background:var(--surface-2); color:var(--text-muted); } .status-pill.archived::before  { background:var(--text-muted); }

        .hero-actions { display: flex; gap: 10px; flex-shrink: 0; }

        /* ── Progress bar ── */
        .progress-section { margin-top: 20px; }
        .progress-label {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 8px;
        }
        .progress-label span { font-size: 13px; color: var(--text-secondary); font-weight: 500; }
        .progress-label strong { font-size: 13px; color: var(--brand); font-weight: 700; }
        .progress-track {
            height: 8px; background: var(--border); border-radius: 20px; overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--brand), var(--brand-light));
            border-radius: 20px;
            transition: width .6s cubic-bezier(.4,0,.2,1);
            width: 0%;
        }

        /* ── Stats row ── */
        .stats-strip {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 24px;
        }

        .stat-strip-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 16px 20px;
            display: flex; align-items: center; gap: 14px;
            box-shadow: var(--shadow-sm);
            transition: box-shadow var(--transition), transform var(--transition);
        }
        .stat-strip-card:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }

        .stat-strip-icon {
            width: 40px; height: 40px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .stat-strip-icon svg { width: 20px; height: 20px; stroke: currentColor; }
        .stat-strip-icon.total      { background: var(--brand-pale);   color: var(--brand); }
        .stat-strip-icon.done       { background: #e6f9f1;              color: #1a8a5c; }
        .stat-strip-icon.progress   { background: #fff7ed;              color: #ea580c; }
        .stat-strip-icon.overdue    { background: #fef2f2;              color: #dc2626; }

        .stat-strip-val  { font-family: 'Playfair Display', serif; font-size: 24px; font-weight: 700; color: var(--text-primary); line-height: 1; }
        .stat-strip-lbl  { font-size: 11px; color: var(--text-muted); font-weight: 500; text-transform: uppercase; letter-spacing: .5px; margin-top: 3px; }

        /* ── Main layout ── */
        .project-main {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 20px;
            align-items: start;
        }

        /* ── Tasks panel ── */
        .tasks-panel {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }

        .tasks-panel-header {
            padding: 18px 24px;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
            gap: 12px;
        }

        .tasks-panel-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 18px; color: var(--text-primary);
        }

        .task-tabs {
            display: flex; gap: 4px;
            background: var(--surface-2); border-radius: 10px; padding: 3px;
        }

        .task-tab {
            padding: 5px 12px; border-radius: 8px; border: none;
            background: transparent; font-family: 'DM Sans', sans-serif;
            font-size: 12px; font-weight: 600; cursor: pointer;
            color: var(--text-muted); transition: all var(--transition);
            white-space: nowrap;
        }
        .task-tab.active { background: var(--surface); color: var(--brand); box-shadow: var(--shadow-sm); }

        .tasks-list { padding: 8px 0; min-height: 200px; }

        /* ── Task row ── */
        .task-row {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 24px;
            border-bottom: 1px solid var(--border);
            transition: background var(--transition);
            cursor: pointer;
        }
        .task-row:last-child { border-bottom: none; }
        .task-row:hover { background: var(--surface-2); }

        .task-check {
            width: 18px; height: 18px; border-radius: 50%;
            border: 2px solid var(--border); flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            transition: all .15s;
        }
        .task-check.done { background: #1a8a5c; border-color: #1a8a5c; }
        .task-check.done::after { content: ''; width: 6px; height: 4px; border-left: 2px solid #fff; border-bottom: 2px solid #fff; transform: rotate(-45deg) translateY(-1px); display: block; }

        .task-body { flex: 1; min-width: 0; }
        .task-title { font-size: 14px; font-weight: 500; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .task-title.done { text-decoration: line-through; color: var(--text-muted); }
        .task-meta-row { display: flex; align-items: center; gap: 8px; margin-top: 3px; flex-wrap: wrap; }

        .priority-dot {
            width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0;
        }
        .priority-dot.low      { background: #94a3b8; }
        .priority-dot.medium   { background: #f59e0b; }
        .priority-dot.high     { background: #f97316; }
        .priority-dot.critical { background: #dc2626; }

        .task-due { font-size: 11px; color: var(--text-muted); }
        .task-due.overdue { color: #dc2626; font-weight: 600; }

        .task-assignee-chip {
            display: flex; align-items: center; gap: 4px;
            font-size: 11px; color: var(--text-muted);
        }

        .assignee-mini {
            width: 18px; height: 18px; border-radius: 50%;
            background: var(--brand); color: #fff;
            font-size: 9px; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }

        .task-status-badge {
            font-size: 10px; font-weight: 600; padding: 2px 7px;
            border-radius: 20px; text-transform: capitalize; white-space: nowrap; flex-shrink: 0;
        }
        .task-status-badge.pending     { background: var(--surface-2); color: var(--text-muted); }
        .task-status-badge.in_progress { background: #fff7ed; color: #ea580c; }
        .task-status-badge.in_review   { background: var(--brand-pale2); color: var(--brand); }
        .task-status-badge.completed   { background: #e6f9f1; color: #1a8a5c; }

        .task-row-actions {
            display: flex; gap: 4px; opacity: 0; transition: opacity var(--transition);
        }
        .task-row:hover .task-row-actions { opacity: 1; }

        .btn-task-action {
            width: 26px; height: 26px; border-radius: 6px; border: none;
            background: transparent; cursor: pointer; color: var(--text-muted);
            display: flex; align-items: center; justify-content: center;
            transition: all var(--transition);
        }
        .btn-task-action:hover { background: var(--brand-pale2); color: var(--brand); }
        .btn-task-action.danger:hover { background: #fef2f2; color: #dc2626; }
        .btn-task-action svg { width: 13px; height: 13px; stroke: currentColor; }

        .tasks-empty {
            display: flex; flex-direction: column; align-items: center;
            padding: 48px 24px; text-align: center;
        }
        .tasks-empty svg { width: 44px; height: 44px; stroke: var(--border); margin-bottom: 12px; }
        .tasks-empty p { font-size: 14px; color: var(--text-muted); }

        /* ── Right panel ── */
        .right-panel { display: flex; flex-direction: column; gap: 16px; }

        .panel-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }

        .panel-card-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
        }
        .panel-card-header h3 {
            font-family: 'Playfair Display', serif;
            font-size: 15px; color: var(--text-primary);
        }

        /* ── Members workload ── */
        .member-workload-row {
            padding: 12px 20px;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; gap: 10px;
        }
        .member-workload-row:last-child { border-bottom: none; }

        .mw-avatar {
            width: 34px; height: 34px; border-radius: 50%;
            background: var(--brand); color: #fff;
            font-size: 12px; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; overflow: hidden;
        }
        .mw-avatar img { width: 100%; height: 100%; object-fit: cover; }

        .mw-info { flex: 1; min-width: 0; }
        .mw-name  { font-size: 13px; font-weight: 600; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .mw-role  { font-size: 11px; color: var(--text-muted); text-transform: capitalize; }

        .mw-tasks { flex-shrink: 0; text-align: right; }
        .mw-count { font-size: 13px; font-weight: 700; color: var(--brand); font-family: 'Playfair Display', serif; }
        .mw-label { font-size: 10px; color: var(--text-muted); }

        .mw-bar-wrap { width: 100%; height: 3px; background: var(--border); border-radius: 2px; margin-top: 6px; }
        .mw-bar-fill { height: 100%; border-radius: 2px; background: linear-gradient(90deg, var(--brand), var(--brand-light)); transition: width .5s ease; }

        /* ── Donut chart ── */
        .chart-wrap { padding: 20px; display: flex; flex-direction: column; align-items: center; gap: 16px; }
        .donut-svg { width: 140px; height: 140px; transform: rotate(-90deg); }
        .donut-center {
            position: absolute; top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }
        .donut-wrapper { position: relative; width: 140px; height: 140px; }
        .donut-center-val { font-family: 'Playfair Display', serif; font-size: 28px; font-weight: 700; color: var(--text-primary); }
        .donut-center-lbl { font-size: 11px; color: var(--text-muted); }

        .chart-legend { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; width: 100%; }
        .legend-item { display: flex; align-items: center; gap: 6px; }
        .legend-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
        .legend-lbl { font-size: 11px; color: var(--text-muted); flex: 1; }
        .legend-val { font-size: 11px; font-weight: 700; color: var(--text-primary); }

        /* ── Priority bars ── */
        .priority-bars { padding: 16px 20px; display: flex; flex-direction: column; gap: 10px; }
        .priority-bar-row { display: flex; align-items: center; gap: 10px; }
        .priority-bar-lbl { font-size: 12px; font-weight: 600; color: var(--text-secondary); width: 56px; text-transform: capitalize; }
        .priority-bar-track { flex: 1; height: 6px; background: var(--border); border-radius: 3px; overflow: hidden; }
        .priority-bar-fill  { height: 100%; border-radius: 3px; transition: width .5s ease; }
        .priority-bar-fill.low      { background: #94a3b8; }
        .priority-bar-fill.medium   { background: #f59e0b; }
        .priority-bar-fill.high     { background: #f97316; }
        .priority-bar-fill.critical { background: #dc2626; }
        .priority-bar-count { font-size: 11px; font-weight: 700; color: var(--text-primary); min-width: 20px; text-align: right; }

        /* ── Skeleton ── */
        .sk-line { background: var(--border); border-radius: 4px; animation: shimmer 1.4s infinite; }
        @keyframes shimmer { 0%,100%{opacity:.5} 50%{opacity:1} }
        @keyframes fadeUp  { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:translateY(0)} }

        .animate-in { animation: fadeUp .35s ease both; }

        /* ── Create / Edit task modal ── */
        .modal h2  { font-family: 'Playfair Display', serif; font-size: 22px; color: var(--brand); margin-bottom: 6px; }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

        select.form-select {
            width: 100%; padding: 11px 14px;
            border: 1.5px solid var(--border); border-radius: var(--radius-sm);
            font-family: 'DM Sans', sans-serif; font-size: 14px;
            background: var(--surface-2); color: var(--text-primary);
            outline: none; transition: border-color var(--transition);
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23a08898' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 32px;
        }
        select.form-select:focus { border-color: var(--brand); }

        /* Task detail side panel */
        .task-detail-backdrop {
            position: fixed; inset: 0;
            background: rgba(26,18,24,.4); backdrop-filter: blur(3px);
            z-index: 150; opacity: 0; pointer-events: none; transition: opacity var(--transition);
        }
        .task-detail-backdrop.open { opacity: 1; pointer-events: all; }

        .task-detail-panel {
            position: fixed; top: 0; right: 0; bottom: 0; width: 420px;
            background: var(--surface); box-shadow: var(--shadow-lg);
            z-index: 151; display: flex; flex-direction: column;
            transform: translateX(100%); transition: transform var(--transition);
        }
        .task-detail-backdrop.open .task-detail-panel { transform: translateX(0); }

        .tdp-header {
            padding: 20px 24px; border-bottom: 1px solid var(--border);
            display: flex; align-items: flex-start; justify-content: space-between; gap: 10px;
        }
        .tdp-header h3 { font-family: 'Playfair Display', serif; font-size: 17px; color: var(--text-primary); line-height: 1.3; }

        .tdp-body { flex: 1; overflow-y: auto; padding: 20px 24px; display: flex; flex-direction: column; gap: 18px; }

        .tdp-section-label { font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: .6px; margin-bottom: 8px; }

        .tdp-status-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; }
        .tdp-status-opt {
            border: 1.5px solid var(--border); border-radius: var(--radius-sm);
            padding: 8px 10px; cursor: pointer; transition: all var(--transition);
            text-align: center; font-size: 12px; font-weight: 600; color: var(--text-muted);
        }
        .tdp-status-opt.active.pending     { border-color: var(--border);    background: var(--surface-2);   color: var(--text-muted); }
        .tdp-status-opt.active.in_progress { border-color: #ea580c;           background: #fff7ed;             color: #ea580c; }
        .tdp-status-opt.active.in_review   { border-color: var(--brand);      background: var(--brand-pale);   color: var(--brand); }
        .tdp-status-opt.active.completed   { border-color: #1a8a5c;           background: #e6f9f1;             color: #1a8a5c; }

        .tdp-desc { font-size: 13.5px; color: var(--text-secondary); line-height: 1.7; }
        .tdp-desc.empty { color: var(--text-muted); font-style: italic; }

        .tdp-meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .tdp-meta-item label { font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: .5px; display: block; margin-bottom: 4px; }
        .tdp-meta-item span  { font-size: 13px; font-weight: 500; color: var(--text-primary); }

        .tdp-footer {
            padding: 16px 24px; border-top: 1px solid var(--border);
            display: flex; gap: 10px;
        }

        @media (max-width: 1100px) {
            .project-main { grid-template-columns: 1fr; }
            .stats-strip  { grid-template-columns: repeat(2, 1fr); }
        }

        /* ── Comments ── */
        .tdp-comment {
            padding: 10px 0;
            border-bottom: 1px solid var(--border);
            transition: opacity .2s, transform .2s;
        }
        .tdp-comment:last-child { border-bottom: none; }
        .tdp-comment-header {
            display: flex; align-items: center; gap: 7px;
            margin-bottom: 5px;
        }
        .tdp-comment-author { font-size: 13px; font-weight: 600; color: var(--text-primary); flex: 1; }
        .tdp-comment-time   { font-size: 11px; color: var(--text-muted); }
        .tdp-comment-delete {
            background: none; border: none; cursor: pointer;
            color: var(--text-muted); padding: 2px; border-radius: 4px;
            opacity: 0; transition: opacity var(--transition), color var(--transition);
        }
        .tdp-comment:hover .tdp-comment-delete { opacity: 1; }
        .tdp-comment-delete:hover { color: #dc2626; }
        .tdp-comment-body { font-size: 13.5px; color: var(--text-secondary); line-height: 1.6; white-space: pre-wrap; }

        .tdp-comment-compose {
            display: flex; gap: 8px; margin-top: 10px; align-items: flex-end;
        }
        .tdp-comment-compose textarea {
            flex: 1; padding: 9px 12px;
            border: 1.5px solid var(--border); border-radius: var(--radius-sm);
            font-family: 'DM Sans', sans-serif; font-size: 13px;
            color: var(--text-primary); background: var(--surface-2);
            outline: none; resize: none; line-height: 1.5;
            transition: border-color var(--transition);
        }
        .tdp-comment-compose textarea:focus { border-color: var(--brand); background: var(--surface); }
        .tdp-comment-submit {
            width: 34px; height: 34px; border-radius: 8px;
            background: var(--brand); border: none; color: #fff;
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; transition: background var(--transition);
        }
        .tdp-comment-submit:hover    { background: var(--brand-mid); }
        .tdp-comment-submit:disabled { opacity: .5; cursor: not-allowed; }

        /* ── Attachments ── */
        .tdp-attachment {
            display: flex; align-items: center; gap: 10px;
            padding: 8px 0; border-bottom: 1px solid var(--border);
            transition: opacity .2s;
        }
        .tdp-attachment:last-child { border-bottom: none; }

        .tdp-att-thumb {
            width: 44px; height: 44px; border-radius: 6px;
            object-fit: cover; cursor: pointer; flex-shrink: 0;
            border: 1px solid var(--border);
            transition: opacity var(--transition);
        }
        .tdp-att-thumb:hover { opacity: .8; }

        .tdp-att-icon {
            width: 44px; height: 44px; border-radius: 6px;
            background: var(--surface-2); border: 1px solid var(--border);
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; flex-shrink: 0;
        }

        .tdp-att-info { flex: 1; min-width: 0; }
        .tdp-att-name {
            font-size: 13px; font-weight: 500; color: var(--brand);
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            display: block; text-decoration: none;
        }
        .tdp-att-name:hover { text-decoration: underline; }
        .tdp-att-meta { font-size: 11px; color: var(--text-muted); margin-top: 2px; }

        .tdp-att-delete {
            background: none; border: none; cursor: pointer;
            color: var(--text-muted); padding: 4px; border-radius: 4px;
            opacity: 0; transition: opacity var(--transition), color var(--transition); flex-shrink: 0;
        }
        .tdp-attachment:hover .tdp-att-delete { opacity: 1; }
        .tdp-att-delete:hover { color: #dc2626; }

        .tdp-upload-btn {
            display: inline-flex; align-items: center; gap: 7px;
            margin-top: 10px; padding: 7px 14px;
            border: 1.5px dashed var(--border); border-radius: var(--radius-sm);
            font-size: 13px; font-weight: 500; color: var(--text-muted);
            cursor: pointer; transition: all var(--transition); background: var(--surface-2);
            width: 100%; justify-content: center;
        }
        .tdp-upload-btn:hover { border-color: var(--brand); color: var(--brand); background: var(--brand-pale); }
        .tdp-upload-btn.loading { opacity: .6; pointer-events: none; }
    </style>
</head>
<body>
<div class="app-shell">

    <?php include __DIR__ . '/../partials/sidebar.php'; ?>

    <div class="main">
        <header class="topbar">
            <div class="topbar-title" id="topbar-project-name">Project</div>
            <div class="topbar-right">
                <span class="topbar-date" id="topbarDate"></span>
                <div class="topbar-avatar"><?= htmlspecialchars($adminInitial) ?></div>
            </div>
        </header>

        <main class="content">

            <a href="<?= $basePath ?>/projects" class="back-nav">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
                </svg>
                All Projects
            </a>

            <!-- Hero -->
            <div class="project-hero animate-in" id="project-hero">
                <div class="project-hero-top">
                    <div>
                        <div class="project-hero-title" id="hero-name">
                            <div class="sk-line" style="width:280px;height:32px"></div>
                        </div>
                        <div class="project-hero-desc" id="hero-desc">
                            <div class="sk-line" style="width:420px;height:14px;margin-bottom:6px"></div>
                            <div class="sk-line" style="width:300px;height:14px"></div>
                        </div>
                        <div class="project-hero-meta" id="hero-meta"></div>
                    </div>
                    <div class="hero-actions" id="hero-actions">
                        <div class="sk-line" style="width:100px;height:38px;border-radius:10px"></div>
                    </div>
                </div>
                <div class="progress-section">
                    <div class="progress-label">
                        <span>Overall Progress</span>
                        <strong id="progress-pct">0%</strong>
                    </div>
                    <div class="progress-track">
                        <div class="progress-fill" id="progress-fill"></div>
                    </div>
                </div>
            </div>

            <!-- Meeting bar (shown when a meeting is active) -->
            <div id="meeting-bar"></div>

            <!-- Stats -->
            <div class="stats-strip">
                <div class="stat-strip-card animate-in" style="animation-delay:.05s">
                    <div class="stat-strip-icon total">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                        </svg>
                    </div>
                    <div>
                        <div class="stat-strip-val" id="stat-total">—</div>
                        <div class="stat-strip-lbl">Total Tasks</div>
                    </div>
                </div>
                <div class="stat-strip-card animate-in" style="animation-delay:.1s">
                    <div class="stat-strip-icon done">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                    </div>
                    <div>
                        <div class="stat-strip-val" id="stat-done">—</div>
                        <div class="stat-strip-lbl">Completed</div>
                    </div>
                </div>
                <div class="stat-strip-card animate-in" style="animation-delay:.15s">
                    <div class="stat-strip-icon progress">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </div>
                    <div>
                        <div class="stat-strip-val" id="stat-progress">—</div>
                        <div class="stat-strip-lbl">In Progress</div>
                    </div>
                </div>
                <div class="stat-strip-card animate-in" style="animation-delay:.2s">
                    <div class="stat-strip-icon overdue">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                            <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                        </svg>
                    </div>
                    <div>
                        <div class="stat-strip-val" id="stat-overdue">—</div>
                        <div class="stat-strip-lbl">Overdue</div>
                    </div>
                </div>
            </div>

            <!-- Main grid -->
            <div class="project-main">

                <!-- Tasks panel -->
                <div class="tasks-panel animate-in" style="animation-delay:.1s">
                    <div class="tasks-panel-header">
                        <h2>Tasks</h2>
                        <div style="display:flex;align-items:center;gap:10px">
                            <div class="task-tabs" id="task-tabs">
                                <button class="task-tab active" data-status="all">All</button>
                                <button class="task-tab" data-status="pending">Pending</button>
                                <button class="task-tab" data-status="in_progress">In Progress</button>
                                <button class="task-tab" data-status="in_review">Review</button>
                                <button class="task-tab" data-status="completed">Done</button>
                            </div>
                            <button class="btn-primary" style="padding:8px 16px;font-size:13px"
                                    onclick="openCreateTaskModal()">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                                </svg>
                                New Task
                            </button>
                        </div>
                    </div>
                    <div class="tasks-list" id="tasks-list">
                        <div style="padding:32px;text-align:center">
                            <div class="sk-line" style="width:80%;height:14px;margin:0 auto 12px"></div>
                            <div class="sk-line" style="width:60%;height:14px;margin:0 auto 12px"></div>
                            <div class="sk-line" style="width:70%;height:14px;margin:0 auto"></div>
                        </div>
                    </div>
                </div>

                <!-- Right panel -->
                <div class="right-panel">

                    <!-- Members workload -->
                    <div class="panel-card animate-in" style="animation-delay:.15s">
                        <div class="panel-card-header">
                            <h3>Team Workload</h3>
                        </div>
                        <div id="members-workload">
                            <div style="padding:16px 20px">
                                <div class="sk-line" style="height:34px;border-radius:8px;margin-bottom:10px"></div>
                                <div class="sk-line" style="height:34px;border-radius:8px;margin-bottom:10px"></div>
                                <div class="sk-line" style="height:34px;border-radius:8px"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Status donut chart -->
                    <div class="panel-card animate-in" style="animation-delay:.2s">
                        <div class="panel-card-header">
                            <h3>Status Breakdown</h3>
                        </div>
                        <div class="chart-wrap" id="donut-chart-wrap">
                            <div class="sk-line" style="width:140px;height:140px;border-radius:50%"></div>
                        </div>
                    </div>

                    <!-- Priority bars -->
                    <div class="panel-card animate-in" style="animation-delay:.25s">
                        <div class="panel-card-header">
                            <h3>Priority Split</h3>
                        </div>
                        <div class="priority-bars" id="priority-bars">
                            <div class="sk-line" style="height:12px;border-radius:6px;margin-bottom:8px"></div>
                            <div class="sk-line" style="height:12px;border-radius:6px;margin-bottom:8px"></div>
                            <div class="sk-line" style="height:12px;border-radius:6px;margin-bottom:8px"></div>
                            <div class="sk-line" style="height:12px;border-radius:6px"></div>
                        </div>
                    </div>

                </div>
            </div>

        </main>
    </div>
</div>


<!-- ══ Task Detail Side Panel ══ -->
<div class="task-detail-backdrop" id="task-detail-backdrop"
     onclick="closeTaskDetail(event)">
    <div class="task-detail-panel">
        <div class="tdp-header">
            <h3 id="tdp-title">Task</h3>
            <button style="background:none;border:none;cursor:pointer;color:var(--text-muted);padding:4px"
                    onclick="closeTaskDetail()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="tdp-body">

            <!-- Status — display only, no click -->
            <div>
                <div class="tdp-section-label">Status</div>
                <div id="tdp-status-display"></div>
            </div>

            <!-- Description -->
            <div>
                <div class="tdp-section-label">Description</div>
                <div class="tdp-desc" id="tdp-desc"></div>
            </div>

            <!-- Meta -->
            <div class="tdp-meta-grid">
                <div class="tdp-meta-item">
                    <label>Priority</label>
                    <span id="tdp-priority"></span>
                </div>
                <div class="tdp-meta-item">
                    <label>Due Date</label>
                    <span id="tdp-due"></span>
                </div>
                <div class="tdp-meta-item">
                    <label>Assigned To</label>
                    <span id="tdp-assignee"></span>
                </div>
                <div class="tdp-meta-item">
                    <label>Created</label>
                    <span id="tdp-created"></span>
                </div>
            </div>

            <!-- Attachments -->
            <div>
                <div class="tdp-section-label" style="margin-bottom:10px">Attachments</div>
                <div id="tdp-attachments-list"></div>
                <label class="tdp-upload-btn" id="tdp-upload-btn">
                    <input type="file" id="tdp-file-input" style="display:none"
                           accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.csv"
                           onchange="uploadAttachment(this)">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66L9.41 17.41a2 2 0 0 1-2.83-2.83l8.49-8.48"/>
                    </svg>
                    <span id="tdp-upload-label">Attach file</span>
                </label>
            </div>

            <!-- Comments -->
            <div>
                <div class="tdp-section-label" style="margin-bottom:10px">Comments</div>
                <div id="tdp-comments-list"></div>
                <div class="tdp-comment-compose">
                    <textarea id="tdp-comment-input" placeholder="Write a comment… (Enter to send, Shift+Enter for newline)" rows="2"></textarea>
                    <button class="tdp-comment-submit" id="tdp-comment-submit" onclick="postComment()">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                        </svg>
                    </button>
                </div>
            </div>

        </div>
        <div class="tdp-footer">
            <button class="btn-ghost" style="flex:1"
                    onclick="event.stopPropagation(); openEditTaskModal(currentTask?.task_id)">Edit Task</button>
            <button class="btn-primary" style="flex:1;background:#dc2626;box-shadow:none"
                    onclick="deleteTaskFromPanel()">
                <div class="btn-spin"></div>
                <span class="btn-text">Delete</span>
            </button>
        </div>
    </div>
</div>


<!-- ══ Create / Edit Task Modal ══ -->
<div class="modal-backdrop" id="modal-task"
     onclick="closeModalOutside(event, 'modal-task')">
    <div class="modal" style="max-width:520px">
        <h2 id="task-modal-title">New Task</h2>
        <p style="font-size:14px;color:var(--text-secondary);margin-bottom:24px" id="task-modal-sub">
            Add a task to this project.
        </p>
        <div class="form-error" id="task-form-error"></div>
        <form id="task-form">
            <input type="hidden" id="tf-task-id">

            <div class="form-group">
                <label for="tf-title">Title <span style="color:var(--brand)">*</span></label>
                <input type="text" id="tf-title" name="title"
                       placeholder="e.g. Design landing page wireframes" required maxlength="255">
            </div>

            <div class="form-group">
                <label for="tf-desc">Description <span class="opt">(optional)</span></label>
                <textarea id="tf-desc" name="description" rows="3"
                          placeholder="What needs to be done?"></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="tf-assigned">Assign To <span style="color:var(--brand)">*</span></label>
                    <select id="tf-assigned" class="form-select" required>
                        <option value="">— Select member —</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="tf-due">Due Date</label>
                    <input type="date" id="tf-due" name="due_date">
                </div>
            </div>

            <div class="form-group">
                <label for="tf-priority">Priority</label>
                <select id="tf-priority" class="form-select">
                    <option value="low">Low</option>
                    <option value="medium" selected>Medium</option>
                    <option value="high">High</option>
                    <option value="critical">Critical</option>
                </select>
            </div>
            <!-- Status intentionally removed — only assigned member can update status -->

            <div class="modal-actions">
                <button type="button" class="btn-ghost" onclick="closeModal('modal-task')">Cancel</button>
                <button type="submit" class="btn-primary" id="task-submit-btn">
                    <div class="btn-spin"></div>
                    <span class="btn-text">Create Task</span>
                </button>
            </div>
        </form>
    </div>
</div>


<script>
    window.WH_BASE          = '<?= $basePath ?>';
    window._canEndMeeting   = true;
    window._meetingUserName = '<?= addslashes($adminHandle) ?>';
</script>
<script src="<?= $basePath ?>/js/app.js"></script>
<script src="<?= $basePath ?>/js/meeting.js"></script>
<script src="<?= $basePath ?>/js/project-details.js"></script>

</body>
</html>