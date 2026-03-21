<?php
// views/user/Dashboard.php
$basePath  = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$userName  = $_SESSION['user_name']        ?? 'Team Member';
$userInitial = strtoupper(substr($userName, 0, 1));
$userRole  = $_SESSION['role']             ?? 'member';
$isManager = $userRole === 'manager';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — WorkHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --brand:          #6A0031;
            --brand-mid:      #8a1144;
            --brand-light:    #b8245f;
            --brand-pale:     #fdf2f6;
            --brand-pale2:    #f5e6ed;
            --accent:         #E8A045;
            --text-primary:   #1a1218;
            --text-secondary: #6b5b65;
            --text-muted:     #a08898;
            --border:         #e8dde3;
            --surface:        #ffffff;
            --surface-2:      #faf7f9;
            --sidebar-w:      260px;
            --header-h:       64px;
            --shadow-sm:      0 1px 3px rgba(106,0,49,.08), 0 1px 2px rgba(0,0,0,.04);
            --shadow-md:      0 4px 16px rgba(106,0,49,.10), 0 2px 8px rgba(0,0,0,.06);
            --shadow-lg:      0 12px 40px rgba(106,0,49,.15), 0 4px 16px rgba(0,0,0,.08);
            --radius:         12px;
            --radius-sm:      8px;
            --transition:     0.22s cubic-bezier(.4,0,.2,1);
        }

        html, body { height: 100%; font-family: 'DM Sans', sans-serif; background: var(--surface-2); color: var(--text-primary); font-size: 15px; line-height: 1.6; }

        .app-shell { display: flex; min-height: 100vh; }

        /* ── Sidebar ── */
        .sidebar {
            width: var(--sidebar-w); background: var(--brand);
            display: flex; flex-direction: column;
            position: fixed; top: 0; left: 0; bottom: 0; z-index: 100;
        }
        .sidebar::before {
            content: ''; position: absolute; inset: 0;
            background: repeating-linear-gradient(135deg, transparent, transparent 40px, rgba(255,255,255,.018) 40px, rgba(255,255,255,.018) 80px);
            pointer-events: none;
        }
        .sidebar-logo { display: flex; align-items: center; gap: 12px; padding: 22px 20px 20px; border-bottom: 1px solid rgba(255,255,255,.1); }
        .logo-mark { width: 36px; height: 36px; background: var(--accent); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-family: 'Playfair Display', serif; font-weight: 700; font-size: 18px; color: var(--brand); flex-shrink: 0; }
        .logo-text { font-family: 'Playfair Display', serif; font-size: 20px; color: #fff; }
        .logo-text span { color: var(--accent); }

        .sidebar-nav { flex: 1; padding: 8px 10px; overflow-y: auto; }
        .nav-section-label { font-size: 10px; font-weight: 600; letter-spacing: 1.2px; text-transform: uppercase; color: rgba(255,255,255,.35); padding: 14px 10px 6px; }
        .nav-item {
            display: flex; align-items: center; gap: 12px; padding: 10px 12px;
            border-radius: var(--radius-sm); color: rgba(255,255,255,.75);
            text-decoration: none; font-size: 14px; font-weight: 500;
            cursor: pointer; border: none; background: transparent; width: 100%;
            transition: background var(--transition), color var(--transition);
        }
        .nav-item:hover  { background: rgba(255,255,255,.1); color: #fff; }
        .nav-item.active { background: rgba(255,255,255,.18); color: #fff; font-weight: 600; }
        .nav-item.active::before { content:''; position:absolute; left:0; top:20%; bottom:20%; width:3px; background:var(--accent); border-radius:0 3px 3px 0; }
        .nav-item { position: relative; }
        .nav-icon { width: 20px; height: 20px; flex-shrink: 0; }

        /* Role badge in sidebar */
        .role-pill {
            display: inline-flex; align-items: center;
            padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .5px;
        }
        .role-pill.manager { background: var(--accent); color: var(--brand); }
        .role-pill.member  { background: rgba(255,255,255,.12); color: rgba(255,255,255,.7); }

        .sidebar-footer { border-top: 1px solid rgba(255,255,255,.1); padding: 14px 10px; }
        .user-card { display: flex; align-items: center; gap: 10px; padding: 8px 10px; border-radius: var(--radius-sm); cursor: pointer; }
        .user-card:hover { background: rgba(255,255,255,.08); }
        .user-avatar { width: 34px; height: 34px; border-radius: 50%; background: var(--brand-light); display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; color: #fff; flex-shrink: 0; border: 2px solid rgba(255,255,255,.2); overflow: hidden; }
        .user-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .user-info { flex: 1; min-width: 0; }
        .user-name { font-size: 13px; font-weight: 600; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .user-role { font-size: 11px; color: rgba(255,255,255,.45); text-transform: capitalize; }
        .btn-logout-sm { background: transparent; border: none; color: rgba(255,255,255,.45); cursor: pointer; padding: 4px; border-radius: 6px; }
        .btn-logout-sm:hover { color: #fff; }

        /* ── Main ── */
        .main { margin-left: var(--sidebar-w); flex: 1; display: flex; flex-direction: column; min-height: 100vh; }
        .topbar { height: var(--header-h); background: var(--surface); border-bottom: 1px solid var(--border); display: flex; align-items: center; padding: 0 32px; gap: 20px; position: sticky; top: 0; z-index: 50; box-shadow: var(--shadow-sm); }
        .topbar-title { font-family: 'Playfair Display', serif; font-size: 22px; color: var(--brand); font-weight: 600; }
        .topbar-right { margin-left: auto; display: flex; align-items: center; gap: 12px; }
        .topbar-date   { font-size: 13px; color: var(--text-muted); }
        .topbar-avatar { width: 36px; height: 36px; border-radius: 50%; background: var(--brand); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; border: 2px solid var(--brand-pale2); overflow: hidden; }
        .topbar-avatar img { width: 100%; height: 100%; object-fit: cover; }

        .content { flex: 1; padding: 32px; }

        /* ── Welcome banner ── */
        .welcome-banner {
            background: linear-gradient(135deg, var(--brand) 0%, var(--brand-light) 100%);
            border-radius: var(--radius); padding: 28px 32px;
            margin-bottom: 28px; position: relative; overflow: hidden;
            animation: fadeUp .4s ease both;
        }
        .welcome-banner::after {
            content: ''; position: absolute; right: -40px; top: -40px;
            width: 200px; height: 200px; border-radius: 50%;
            background: rgba(255,255,255,.05);
        }
        .welcome-banner h1 { font-family: 'Playfair Display', serif; font-size: 26px; color: #fff; margin-bottom: 6px; }
        .welcome-banner p  { font-size: 14px; color: rgba(255,255,255,.7); }
        .welcome-role-badge { display: inline-flex; align-items: center; gap: 6px; margin-top: 12px; }

        /* ── Stats row ── */
        .stats-row { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 14px; margin-bottom: 28px; }
        .stat-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 18px 20px; display: flex; align-items: center; gap: 14px; box-shadow: var(--shadow-sm); transition: box-shadow var(--transition), transform var(--transition); animation: fadeUp .35s ease both; }
        .stat-card:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }
        .stat-icon { width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .stat-icon svg { width: 20px; height: 20px; stroke: currentColor; }
        .stat-icon.total    { background: var(--brand-pale);   color: var(--brand); }
        .stat-icon.done     { background: #e6f9f1;              color: #1a8a5c; }
        .stat-icon.pending  { background: #fff7ed;              color: #ea580c; }
        .stat-icon.overdue  { background: #fef2f2;              color: #dc2626; }
        .stat-icon.projects { background: var(--brand-pale2);   color: var(--brand-mid); }
        .stat-val { font-family: 'Playfair Display', serif; font-size: 24px; font-weight: 700; color: var(--text-primary); line-height: 1; }
        .stat-lbl { font-size: 11px; color: var(--text-muted); font-weight: 500; text-transform: uppercase; letter-spacing: .5px; margin-top: 3px; }

        /* ── Two column layout ── */
        .dash-grid { display: grid; grid-template-columns: 1fr 320px; gap: 20px; align-items: start; }

        /* ── Tasks panel ── */
        .panel { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow-sm); overflow: hidden; animation: fadeUp .35s ease both; }
        .panel-header { padding: 18px 24px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
        .panel-header h2 { font-family: 'Playfair Display', serif; font-size: 18px; color: var(--text-primary); }

        .task-filter-tabs { display: flex; gap: 4px; background: var(--surface-2); border-radius: 10px; padding: 3px; }
        .task-filter-tab { padding: 5px 12px; border-radius: 8px; border: none; background: transparent; font-family: 'DM Sans', sans-serif; font-size: 12px; font-weight: 600; cursor: pointer; color: var(--text-muted); transition: all var(--transition); white-space: nowrap; }
        .task-filter-tab.active { background: var(--surface); color: var(--brand); box-shadow: var(--shadow-sm); }

        /* ── Task row ── */
        .task-row { display: flex; align-items: center; gap: 12px; padding: 13px 24px; border-bottom: 1px solid var(--border); transition: background var(--transition); cursor: pointer; }
        .task-row:last-child { border-bottom: none; }
        .task-row:hover { background: var(--surface-2); }

        .task-check { width: 18px; height: 18px; border-radius: 50%; border: 2px solid var(--border); flex-shrink: 0; display: flex; align-items: center; justify-content: center; transition: all .15s; cursor: pointer; }
        .task-check.done { background: #1a8a5c; border-color: #1a8a5c; }
        .task-check.done::after { content: ''; width: 6px; height: 4px; border-left: 2px solid #fff; border-bottom: 2px solid #fff; transform: rotate(-45deg) translateY(-1px); display: block; }

        .task-body { flex: 1; min-width: 0; }
        .task-title { font-size: 14px; font-weight: 500; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .task-title.done { text-decoration: line-through; color: var(--text-muted); }

        .task-meta { display: flex; align-items: center; gap: 8px; margin-top: 3px; flex-wrap: wrap; }
        .task-project-chip { font-size: 11px; color: var(--brand); font-weight: 600; background: var(--brand-pale); padding: 1px 7px; border-radius: 10px; }
        .priority-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
        .priority-dot.low { background:#94a3b8; } .priority-dot.medium { background:#f59e0b; } .priority-dot.high { background:#f97316; } .priority-dot.critical { background:#dc2626; }
        .task-due { font-size: 11px; color: var(--text-muted); }
        .task-due.overdue { color: #dc2626; font-weight: 600; }

        .status-badge { font-size: 10px; font-weight: 600; padding: 2px 7px; border-radius: 20px; text-transform: capitalize; white-space: nowrap; flex-shrink: 0; }
        .status-badge.pending     { background: var(--surface-2); color: var(--text-muted); }
        .status-badge.in_progress { background: #fff7ed; color: #ea580c; }
        .status-badge.in_review   { background: var(--brand-pale2); color: var(--brand); }
        .status-badge.completed   { background: #e6f9f1; color: #1a8a5c; }

        /* Manager-only action buttons on task row */
        .task-actions { display: flex; gap: 4px; opacity: 0; transition: opacity var(--transition); }
        .task-row:hover .task-actions { opacity: 1; }
        .btn-task-sm { width: 26px; height: 26px; border-radius: 6px; border: none; background: transparent; cursor: pointer; color: var(--text-muted); display: flex; align-items: center; justify-content: center; transition: all var(--transition); }
        .btn-task-sm:hover { background: var(--brand-pale2); color: var(--brand); }
        .btn-task-sm.danger:hover { background: #fef2f2; color: #dc2626; }
        .btn-task-sm svg { width: 13px; height: 13px; stroke: currentColor; }

        .panel-empty { display: flex; flex-direction: column; align-items: center; padding: 48px 24px; text-align: center; }
        .panel-empty svg { width: 44px; height: 44px; stroke: var(--border); margin-bottom: 12px; }
        .panel-empty p { font-size: 14px; color: var(--text-muted); }

        /* ── Right panel ── */
        .right-panel { display: flex; flex-direction: column; gap: 16px; }

        /* ── Projects list ── */
        .project-item { padding: 14px 20px; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 12px; cursor: pointer; transition: background var(--transition); }
        .project-item:last-child { border-bottom: none; }
        .project-item:hover { background: var(--surface-2); }
        .project-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
        .project-dot.active    { background: #1a8a5c; }
        .project-dot.completed { background: var(--brand); }
        .project-dot.archived  { background: var(--text-muted); }
        .project-item-name  { font-size: 14px; font-weight: 600; color: var(--text-primary); }
        .project-item-tasks { font-size: 11px; color: var(--text-muted); margin-top: 1px; }
        .project-item-role  { margin-left: auto; font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 20px; text-transform: capitalize; flex-shrink: 0; }
        .project-item-role.manager { background: var(--brand-pale2); color: var(--brand); }
        .project-item-role.member  { background: var(--surface-2); color: var(--text-muted); border: 1px solid var(--border); }

        /* ── Status update quick panel (slide in from bottom of task row) ── */
        .status-popover {
            position: fixed; z-index: 300;
            background: var(--surface); border: 1.5px solid var(--border);
            border-radius: var(--radius); box-shadow: var(--shadow-lg);
            padding: 8px; display: none; flex-direction: column; gap: 4px; min-width: 160px;
        }
        .status-popover.open { display: flex; }
        .status-opt {
            padding: 8px 12px; border-radius: var(--radius-sm); border: none;
            background: transparent; font-family: 'DM Sans', sans-serif;
            font-size: 13px; font-weight: 500; cursor: pointer; text-align: left;
            transition: background var(--transition);
        }
        .status-opt:hover { background: var(--surface-2); }
        .status-opt.pending     { color: var(--text-muted); }
        .status-opt.in_progress { color: #ea580c; }
        .status-opt.in_review   { color: var(--brand); }
        .status-opt.completed   { color: #1a8a5c; }

        /* ── Modal ── */
        .modal-backdrop { position: fixed; inset: 0; background: rgba(26,18,24,.55); backdrop-filter: blur(5px); z-index: 200; display: flex; align-items: center; justify-content: center; opacity: 0; pointer-events: none; transition: opacity var(--transition); }
        .modal-backdrop.open { opacity: 1; pointer-events: all; }
        .modal { background: var(--surface); border-radius: 18px; padding: 36px; width: 100%; max-width: 500px; box-shadow: var(--shadow-lg); transform: scale(.96) translateY(12px); transition: transform var(--transition); max-height: 90vh; overflow-y: auto; }
        .modal-backdrop.open .modal { transform: scale(1) translateY(0); }
        .modal h2 { font-family: 'Playfair Display', serif; font-size: 22px; color: var(--brand); margin-bottom: 6px; }
        .modal-sub { font-size: 14px; color: var(--text-secondary); margin-bottom: 24px; }
        .form-error { background: #fef2f2; border: 1px solid #fecaca; border-radius: var(--radius-sm); padding: 10px 14px; font-size: 13px; color: #b91c1c; margin-bottom: 16px; display: none; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: var(--text-primary); margin-bottom: 6px; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 11px 14px; border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-family: 'DM Sans', sans-serif; font-size: 14px; color: var(--text-primary); background: var(--surface-2); outline: none; transition: border-color var(--transition); }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { border-color: var(--brand); background: var(--surface); }
        .form-group textarea { resize: vertical; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .modal-actions { display: flex; gap: 12px; margin-top: 28px; justify-content: flex-end; }

        .btn-primary { display: inline-flex; align-items: center; gap: 8px; background: var(--brand); color: #fff; border: none; padding: 11px 24px; border-radius: var(--radius-sm); font-size: 14px; font-weight: 600; cursor: pointer; transition: background var(--transition); font-family: 'DM Sans', sans-serif; }
        .btn-primary:hover { background: var(--brand-mid); }
        .btn-primary.loading { opacity: .7; pointer-events: none; }
        .btn-primary .btn-spin { display: none; width: 14px; height: 14px; border: 2px solid rgba(255,255,255,.4); border-top-color: #fff; border-radius: 50%; animation: spin .6s linear infinite; }
        .btn-primary.loading .btn-text { display: none; }
        .btn-primary.loading .btn-spin { display: block; }
        .btn-ghost { padding: 10px 22px; border: 1.5px solid var(--border); background: transparent; border-radius: var(--radius-sm); font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 500; cursor: pointer; color: var(--text-secondary); transition: all var(--transition); }
        .btn-ghost:hover { border-color: var(--brand); color: var(--brand); }

        /* Skeleton */
        .sk-line { background: var(--border); border-radius: 4px; animation: shimmer 1.4s infinite; }
        @keyframes shimmer { 0%,100%{opacity:.5} 50%{opacity:1} }
        @keyframes fadeUp  { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }
        @keyframes spin    { to { transform: rotate(360deg); } }

        /* Toast */
        .toast { position: fixed; bottom: 28px; right: 28px; background: #1a1218; color: #fff; padding: 12px 18px; border-radius: 10px; display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 500; box-shadow: 0 8px 24px rgba(0,0,0,.2); transform: translateY(12px); opacity: 0; transition: all .25s cubic-bezier(.4,0,.2,1); z-index: 999; }
        .toast.show { transform: translateY(0); opacity: 1; }
        .toast.error .toast-dot { background: #f87171; }
        .toast.success .toast-dot { background: #34d399; }
        .toast-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }

        @media (max-width: 1100px) { .dash-grid { grid-template-columns: 1fr; } }

        /* ── Task side panel ── */
        .task-panel-backdrop {
            position: fixed; inset: 0;
            background: rgba(26,18,24,.4); backdrop-filter: blur(3px);
            z-index: 150; opacity: 0; pointer-events: none; transition: opacity var(--transition);
        }
        .task-panel-backdrop.open { opacity: 1; pointer-events: all; }

        .task-panel {
            position: fixed; top: 0; right: 0; bottom: 0; width: 400px;
            background: var(--surface); box-shadow: var(--shadow-lg);
            z-index: 151; display: flex; flex-direction: column;
            transform: translateX(100%); transition: transform var(--transition);
        }
        .task-panel-backdrop.open .task-panel { transform: translateX(0); }

        .udp-header {
            padding: 20px 24px; border-bottom: 1px solid var(--border);
            display: flex; align-items: flex-start; justify-content: space-between; gap: 10px;
        }
        .udp-header h3 { font-family: 'Playfair Display', serif; font-size: 17px; color: var(--text-primary); line-height: 1.3; flex: 1; }
        .udp-close { background: none; border: none; cursor: pointer; color: var(--text-muted); padding: 4px; border-radius: 6px; transition: color var(--transition); }
        .udp-close:hover { color: var(--brand); }

        .udp-body { flex: 1; overflow-y: auto; padding: 20px 24px; display: flex; flex-direction: column; gap: 20px; }

        .udp-section-label { font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: .6px; margin-bottom: 8px; }
        .udp-meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .udp-meta-item label { font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: .5px; display: block; margin-bottom: 4px; }
        .udp-meta-item span  { font-size: 13px; font-weight: 500; color: var(--text-primary); }
        .udp-empty { color: var(--text-muted); font-style: italic; font-size: 13.5px; }

        .udp-comment { padding: 10px 0; border-bottom: 1px solid var(--border); }
        .udp-comment:last-child { border-bottom: none; }
        .udp-attachment { display: flex; align-items: center; gap: 10px; padding: 8px 0; border-bottom: 1px solid var(--border); }
        .udp-attachment:last-child { border-bottom: none; }

        .udp-compose {
            display: flex; gap: 8px; align-items: flex-end; margin-top: 10px;
        }
        .udp-compose textarea {
            flex: 1; padding: 9px 12px;
            border: 1.5px solid var(--border); border-radius: var(--radius-sm);
            font-family: 'DM Sans', sans-serif; font-size: 13px;
            color: var(--text-primary); background: var(--surface-2);
            outline: none; resize: none; line-height: 1.5;
            transition: border-color var(--transition);
        }
        .udp-compose textarea:focus { border-color: var(--brand); background: var(--surface); }
        .udp-send-btn {
            width: 34px; height: 34px; border-radius: 8px;
            background: var(--brand); border: none; color: #fff;
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; transition: background var(--transition);
        }
        .udp-send-btn:hover    { background: var(--brand-mid); }
        .udp-send-btn:disabled { opacity: .5; cursor: not-allowed; }

        .udp-upload-label {
            display: inline-flex; align-items: center; gap: 7px;
            margin-top: 10px; padding: 7px 14px;
            border: 1.5px dashed var(--border); border-radius: var(--radius-sm);
            font-size: 13px; font-weight: 500; color: var(--text-muted);
            cursor: pointer; transition: all var(--transition); background: var(--surface-2);
            width: 100%; justify-content: center;
        }
        .udp-upload-label:hover { border-color: var(--brand); color: var(--brand); background: var(--brand-pale); }
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

            <a href="<?= $basePath ?>/user/dashboard" class="nav-item active">
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

          
        </nav>

        <div class="sidebar-footer">
            <div class="user-card">
                <div class="user-avatar" id="sidebar-avatar"><?= htmlspecialchars($userInitial) ?></div>
                <div class="user-info">
                    <div class="user-name" id="sidebar-name"><?= htmlspecialchars($userName) ?></div>
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
            <div class="topbar-title">Dashboard</div>
            <div class="topbar-right">
                <span class="topbar-date" id="topbar-date"></span>
                <div class="topbar-avatar" id="topbar-avatar"><?= htmlspecialchars($userInitial) ?></div>
            </div>
        </header>

        <main class="content">

            <!-- Welcome banner -->
            <div class="welcome-banner">
                <h1>Welcome back, <span id="welcome-name"><?= htmlspecialchars($userName) ?></span> 👋</h1>
                <p id="welcome-sub">Here's what's on your plate today.</p>
                <div class="welcome-role-badge">
                    <span class="role-pill <?= $isManager ? 'manager' : 'member' ?>">
                        <?= $isManager ? '⚡ Manager' : '👤 Member' ?>
                    </span>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-row">
                <div class="stat-card" style="animation-delay:.05s">
                    <div class="stat-icon total">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                        </svg>
                    </div>
                    <div><div class="stat-val" id="stat-total">—</div><div class="stat-lbl">My Tasks</div></div>
                </div>
                <div class="stat-card" style="animation-delay:.1s">
                    <div class="stat-icon done">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                    </div>
                    <div><div class="stat-val" id="stat-done">—</div><div class="stat-lbl">Completed</div></div>
                </div>
                <div class="stat-card" style="animation-delay:.15s">
                    <div class="stat-icon pending">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </div>
                    <div><div class="stat-val" id="stat-pending">—</div><div class="stat-lbl">In Progress</div></div>
                </div>
                <div class="stat-card" style="animation-delay:.2s">
                    <div class="stat-icon overdue">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                            <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                        </svg>
                    </div>
                    <div><div class="stat-val" id="stat-overdue">—</div><div class="stat-lbl">Overdue</div></div>
                </div>
                <div class="stat-card" style="animation-delay:.25s">
                    <div class="stat-icon projects">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                        </svg>
                    </div>
                    <div><div class="stat-val" id="stat-projects">—</div><div class="stat-lbl">Projects</div></div>
                </div>
            </div>

            <!-- Dashboard grid -->
            <div class="dash-grid">

                <!-- Tasks panel -->
                <div class="panel" style="animation-delay:.1s">
                    <div class="panel-header">
                        <h2><?= $isManager ? 'All Tasks' : 'My Tasks' ?></h2>
                        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                            <div class="task-filter-tabs">
                                <button class="task-filter-tab active" data-filter="all">All</button>
                                <button class="task-filter-tab" data-filter="pending">Pending</button>
                                <button class="task-filter-tab" data-filter="in_progress">In Progress</button>
                                <button class="task-filter-tab" data-filter="completed">Done</button>
                            </div>
                            <?php if ($isManager): ?>
                            <button class="btn-primary" style="padding:7px 14px;font-size:13px"
                                    onclick="openCreateTask()">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                                </svg>
                                New Task
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div id="tasks-list">
                        <?php for($i=0;$i<5;$i++): ?>
                        <div style="padding:13px 24px;border-bottom:1px solid var(--border);display:flex;gap:12px;align-items:center">
                            <div class="sk-line" style="width:18px;height:18px;border-radius:50%;flex-shrink:0"></div>
                            <div style="flex:1">
                                <div class="sk-line" style="height:13px;width:60%;margin-bottom:6px"></div>
                                <div class="sk-line" style="height:10px;width:40%"></div>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- Right panel -->
                <div class="right-panel">

                    <!-- My Projects -->
                    <div class="panel" style="animation-delay:.15s">
                        <div class="panel-header">
                            <h2>My Projects</h2>
                        </div>
                        <div id="projects-list">
                            <div style="padding:16px 20px">
                                <div class="sk-line" style="height:44px;border-radius:8px;margin-bottom:8px"></div>
                                <div class="sk-line" style="height:44px;border-radius:8px;margin-bottom:8px"></div>
                                <div class="sk-line" style="height:44px;border-radius:8px"></div>
                            </div>
                        </div>
                    </div>

                    <?php if ($isManager): ?>
                    <!-- Manager: quick team overview -->
                    <div class="panel" style="animation-delay:.2s">
                        <div class="panel-header">
                            <h2>Project Filter</h2>
                        </div>
                        <div style="padding:12px 20px">
                            <select id="project-filter-select" class="form-group"
                                    style="width:100%;padding:10px 14px;border:1.5px solid var(--border);border-radius:var(--radius-sm);font-family:'DM Sans',sans-serif;font-size:14px;background:var(--surface-2);color:var(--text-primary);outline:none"
                                    onchange="filterTasksByProject(this.value)">
                                <option value="all">All Projects</option>
                            </select>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
            </div>

        </main>
    </div>
</div>


<!-- ── Status popover ── -->
<div class="status-popover" id="status-popover">
    <button class="status-opt pending"     onclick="setStatus('pending')">Pending</button>
    <button class="status-opt in_progress" onclick="setStatus('in_progress')">In Progress</button>
    <button class="status-opt in_review"   onclick="setStatus('in_review')">In Review</button>
    <button class="status-opt completed"   onclick="setStatus('completed')">Completed</button>
</div>


<?php if ($isManager): ?>
<!-- ── Create / Edit Task Modal (manager only) ── -->
<div class="modal-backdrop" id="modal-task" onclick="closeModalOutside(event, 'modal-task')">
    <div class="modal">
        <h2 id="task-modal-title">New Task</h2>
        <p class="modal-sub" id="task-modal-sub">Create a task for your project.</p>
        <div class="form-error" id="task-error"></div>
        <form id="task-form">
            <input type="hidden" id="tf-task-id">
            <div class="form-group">
                <label for="tf-project">Project <span style="color:var(--brand)">*</span></label>
                <select id="tf-project" required onchange="loadProjectMembers(this.value)">
                    <option value="">— Select project —</option>
                </select>
            </div>
            <div class="form-group">
                <label for="tf-title">Title <span style="color:var(--brand)">*</span></label>
                <input type="text" id="tf-title" placeholder="What needs to be done?" required maxlength="255">
            </div>
            <div class="form-group">
                <label for="tf-desc">Description <span style="font-weight:400;color:var(--text-muted)">(optional)</span></label>
                <textarea id="tf-desc" rows="3" placeholder="More details…"></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="tf-assignee">Assign To <span style="color:var(--brand)">*</span></label>
                    <select id="tf-assignee" required>
                        <option value="">— Select member —</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="tf-due">Due Date</label>
                    <input type="date" id="tf-due">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="tf-priority">Priority</label>
                    <select id="tf-priority">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="tf-status">Status</label>
                    <select id="tf-status">
                        <option value="pending" selected>Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="in_review">In Review</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
            </div>
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
<?php endif; ?>


<!-- ── Toast ── -->
<div class="toast" id="toast">
    <div class="toast-dot"></div>
    <span id="toast-msg"></span>
</div>


<!-- ── Task side panel ── -->
<div class="task-panel-backdrop" id="task-panel-backdrop" onclick="closeTaskPanel(event)">
    <div class="task-panel">

        <div class="udp-header">
            <h3 id="udp-title">Task</h3>
            <button class="udp-close" onclick="document.getElementById('task-panel-backdrop').classList.remove('open'); currentTask=null;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        <div class="udp-body">

            <!-- Status -->
            <div>
                <div class="udp-section-label">Status</div>
                <div id="udp-status-display"></div>
            </div>

            <!-- Description -->
            <div>
                <div class="udp-section-label">Description</div>
                <div id="udp-desc" class="udp-empty"></div>
            </div>

            <!-- Meta -->
            <div class="udp-meta-grid">
                <div class="udp-meta-item">
                    <label>Priority</label>
                    <span id="udp-priority"></span>
                </div>
                <div class="udp-meta-item">
                    <label>Due Date</label>
                    <span id="udp-due"></span>
                </div>
                <div class="udp-meta-item">
                    <label>Assigned To</label>
                    <span id="udp-assignee"></span>
                </div>
                <div class="udp-meta-item">
                    <label>Project</label>
                    <span id="udp-project"></span>
                </div>
            </div>

            <!-- Attachments -->
            <div>
                <div class="udp-section-label">Attachments</div>
                <div id="udp-attachments-list"></div>
                <label class="udp-upload-label">
                    <input type="file" style="display:none"
                           accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.csv"
                           onchange="uploadPanelAttachment(this)">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66L9.41 17.41a2 2 0 0 1-2.83-2.83l8.49-8.48"/>
                    </svg>
                    <span id="udp-upload-label">Attach file</span>
                </label>
            </div>

            <!-- Comments -->
            <div>
                <div class="udp-section-label">Comments</div>
                <div id="udp-comments-list"></div>
                <div class="udp-compose">
                    <textarea id="udp-comment-input" rows="2"
                        placeholder="Write a comment… (Enter to send)"></textarea>
                    <button class="udp-send-btn" id="udp-comment-submit" onclick="postPanelComment()">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                        </svg>
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>


<script>
    window.WH_BASE   = '<?= $basePath ?>';
    window.WH_ROLE   = '<?= $userRole ?>';
    window.WH_USER_ID = <?= (int) ($_SESSION['user_id'] ?? 0) ?>;
    const BASE        = window.WH_BASE;
    const IS_MANAGER  = window.WH_ROLE === 'manager';
</script>
<script src="<?= $basePath ?>/js/user-dashboard.js"></script>
</body>
</html>