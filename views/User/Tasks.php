<?php
// views/User/Tasks.php
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
    <title><?= $isManager ? 'All Tasks' : 'My Tasks' ?> — WorkHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
        :root {
            --brand:#6A0031; --brand-mid:#8a1144; --brand-light:#b8245f;
            --brand-pale:#fdf2f6; --brand-pale2:#f5e6ed; --accent:#E8A045;
            --text-primary:#1a1218; --text-secondary:#6b5b65; --text-muted:#a08898;
            --border:#e8dde3; --surface:#fff; --surface-2:#faf7f9;
            --sidebar-w:260px; --header-h:64px;
            --shadow-sm:0 1px 3px rgba(106,0,49,.08),0 1px 2px rgba(0,0,0,.04);
            --shadow-md:0 4px 16px rgba(106,0,49,.10),0 2px 8px rgba(0,0,0,.06);
            --shadow-lg:0 12px 40px rgba(106,0,49,.15),0 4px 16px rgba(0,0,0,.08);
            --radius:12px; --radius-sm:8px;
            --transition:0.22s cubic-bezier(.4,0,.2,1);
        }
        html,body { height:100%; font-family:'DM Sans',sans-serif; background:var(--surface-2); color:var(--text-primary); font-size:15px; line-height:1.6; }
        .app-shell { display:flex; min-height:100vh; }

        /* ── Sidebar (same as dashboard) ── */
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
        .topbar-date   { font-size:13px; color:var(--text-muted); }
        .topbar-avatar { width:36px; height:36px; border-radius:50%; background:var(--brand); color:#fff; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:700; border:2px solid var(--brand-pale2); }
        .content { flex:1; padding:32px; }

        /* ── Page header ── */
        .page-header { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; margin-bottom:24px; flex-wrap:wrap; }
        .page-header h1 { font-family:'Playfair Display',serif; font-size:28px; color:var(--text-primary); }
        .page-header p  { font-size:14px; color:var(--text-secondary); margin-top:2px; }

        /* ── Toolbar ── */
        .tasks-toolbar { display:flex; align-items:center; gap:10px; margin-bottom:20px; flex-wrap:wrap; }
        .search-wrap { position:relative; flex:1; min-width:200px; max-width:320px; }
        .search-wrap svg { position:absolute; left:11px; top:50%; transform:translateY(-50%); width:15px; height:15px; stroke:var(--text-muted); pointer-events:none; }
        .search-wrap input { width:100%; padding:9px 14px 9px 36px; border:1.5px solid var(--border); border-radius:20px; font-family:'DM Sans',sans-serif; font-size:13px; color:var(--text-primary); background:var(--surface); outline:none; transition:border-color var(--transition); }
        .search-wrap input:focus { border-color:var(--brand); }

        .filter-select { padding:8px 14px; border:1.5px solid var(--border); border-radius:20px; font-family:'DM Sans',sans-serif; font-size:13px; color:var(--text-secondary); background:var(--surface); outline:none; cursor:pointer; transition:border-color var(--transition); }
        .filter-select:focus { border-color:var(--brand); }

        .results-count { font-size:13px; color:var(--text-muted); margin-left:auto; white-space:nowrap; }

        /* ── Task list ── */
        .tasks-panel { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); box-shadow:var(--shadow-sm); overflow:hidden; }

        .task-row { display:flex; align-items:center; gap:12px; padding:13px 24px; border-bottom:1px solid var(--border); transition:background var(--transition); cursor:pointer; }
        .task-row:last-child { border-bottom:none; }
        .task-row:hover { background:var(--surface-2); }

        .task-check { width:18px; height:18px; border-radius:50%; border:2px solid var(--border); flex-shrink:0; display:flex; align-items:center; justify-content:center; transition:all .15s; }
        .task-check.done { background:#1a8a5c; border-color:#1a8a5c; }
        .task-check.done::after { content:''; width:6px; height:4px; border-left:2px solid #fff; border-bottom:2px solid #fff; transform:rotate(-45deg) translateY(-1px); display:block; }

        .task-body { flex:1; min-width:0; }
        .task-title { font-size:14px; font-weight:500; color:var(--text-primary); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .task-title.done { text-decoration:line-through; color:var(--text-muted); }
        .task-meta { display:flex; align-items:center; gap:8px; margin-top:3px; flex-wrap:wrap; }
        .task-project-chip { font-size:11px; color:var(--brand); font-weight:600; background:var(--brand-pale); padding:1px 7px; border-radius:10px; }
        .priority-dot { width:7px; height:7px; border-radius:50%; flex-shrink:0; }
        .priority-dot.low{background:#94a3b8} .priority-dot.medium{background:#f59e0b} .priority-dot.high{background:#f97316} .priority-dot.critical{background:#dc2626}
        .task-due { font-size:11px; color:var(--text-muted); }
        .task-due.overdue { color:#dc2626; font-weight:600; }

        .status-badge { font-size:10px; font-weight:600; padding:2px 7px; border-radius:20px; text-transform:capitalize; white-space:nowrap; flex-shrink:0; }
        .status-badge.pending     { background:var(--surface-2); color:var(--text-muted); }
        .status-badge.in_progress { background:#fff7ed; color:#ea580c; }
        .status-badge.in_review   { background:var(--brand-pale2); color:var(--brand); }
        .status-badge.completed   { background:#e6f9f1; color:#1a8a5c; }

        .task-actions { display:flex; gap:4px; opacity:0; transition:opacity var(--transition); }
        .task-row:hover .task-actions { opacity:1; }
        .btn-task-sm { width:26px; height:26px; border-radius:6px; border:none; background:transparent; cursor:pointer; color:var(--text-muted); display:flex; align-items:center; justify-content:center; transition:all var(--transition); }
        .btn-task-sm:hover { background:var(--brand-pale2); color:var(--brand); }
        .btn-task-sm.danger:hover { background:#fef2f2; color:#dc2626; }
        .btn-task-sm svg { width:13px; height:13px; stroke:currentColor; }

        /* ── Empty / loading ── */
        .panel-empty { display:flex; flex-direction:column; align-items:center; padding:64px 24px; text-align:center; }
        .panel-empty svg { width:48px; height:48px; stroke:var(--border); margin-bottom:14px; }
        .panel-empty h3 { font-family:'Playfair Display',serif; font-size:18px; color:var(--text-primary); margin-bottom:6px; }
        .panel-empty p { font-size:14px; color:var(--text-muted); }

        .sk-line { background:var(--border); border-radius:4px; animation:shimmer 1.4s infinite; }
        @keyframes shimmer { 0%,100%{opacity:.5} 50%{opacity:1} }
        @keyframes fadeUp { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:translateY(0)} }
        @keyframes spin { to{transform:rotate(360deg)} }

        /* ── Pagination ── */
        .pagination { display:flex; align-items:center; justify-content:center; gap:8px; padding:20px; }
        .page-btn { width:34px; height:34px; border-radius:var(--radius-sm); border:1.5px solid var(--border); background:var(--surface); font-family:'DM Sans',sans-serif; font-size:13px; font-weight:600; cursor:pointer; color:var(--text-secondary); transition:all var(--transition); display:flex; align-items:center; justify-content:center; }
        .page-btn:hover   { border-color:var(--brand); color:var(--brand); }
        .page-btn.active  { background:var(--brand); color:#fff; border-color:var(--brand); }
        .page-btn:disabled { opacity:.4; cursor:not-allowed; }
        .page-info { font-size:13px; color:var(--text-muted); padding:0 8px; }

        /* ── Status popover ── */
        .status-popover { position:fixed; z-index:300; background:var(--surface); border:1.5px solid var(--border); border-radius:var(--radius); box-shadow:var(--shadow-lg); padding:8px; display:none; flex-direction:column; gap:4px; min-width:160px; }
        .status-popover.open { display:flex; }
        .status-opt { padding:8px 12px; border-radius:var(--radius-sm); border:none; background:transparent; font-family:'DM Sans',sans-serif; font-size:13px; font-weight:500; cursor:pointer; text-align:left; transition:background var(--transition); }
        .status-opt:hover { background:var(--surface-2); }
        .status-opt.pending{color:var(--text-muted)} .status-opt.in_progress{color:#ea580c} .status-opt.in_review{color:var(--brand)} .status-opt.completed{color:#1a8a5c}

        /* ── Toast ── */
        .toast { position:fixed; bottom:28px; right:28px; background:#1a1218; color:#fff; padding:12px 18px; border-radius:10px; display:flex; align-items:center; gap:10px; font-size:14px; font-weight:500; box-shadow:0 8px 24px rgba(0,0,0,.2); transform:translateY(12px); opacity:0; transition:all .25s cubic-bezier(.4,0,.2,1); z-index:999; }
        .toast.show { transform:translateY(0); opacity:1; }
        .toast-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
        .toast.error .toast-dot { background:#f87171; } .toast.success .toast-dot { background:#34d399; }

        /* ── Modal (manager create/edit task) ── */
        .modal-backdrop { position:fixed; inset:0; background:rgba(26,18,24,.55); backdrop-filter:blur(5px); z-index:200; display:flex; align-items:center; justify-content:center; opacity:0; pointer-events:none; transition:opacity var(--transition); }
        .modal-backdrop.open { opacity:1; pointer-events:all; }
        .modal { background:var(--surface); border-radius:18px; padding:36px; width:100%; max-width:500px; box-shadow:var(--shadow-lg); transform:scale(.96) translateY(12px); transition:transform var(--transition); max-height:90vh; overflow-y:auto; }
        .modal-backdrop.open .modal { transform:scale(1) translateY(0); }
        .modal h2 { font-family:'Playfair Display',serif; font-size:22px; color:var(--brand); margin-bottom:6px; }
        .modal-sub { font-size:14px; color:var(--text-secondary); margin-bottom:24px; }
        .form-error { background:#fef2f2; border:1px solid #fecaca; border-radius:var(--radius-sm); padding:10px 14px; font-size:13px; color:#b91c1c; margin-bottom:16px; display:none; }
        .form-group { margin-bottom:18px; }
        .form-group label { display:block; font-size:13px; font-weight:600; color:var(--text-primary); margin-bottom:6px; }
        .form-group input, .form-group textarea, .form-group select { width:100%; padding:11px 14px; border:1.5px solid var(--border); border-radius:var(--radius-sm); font-family:'DM Sans',sans-serif; font-size:14px; color:var(--text-primary); background:var(--surface-2); outline:none; transition:border-color var(--transition); }
        .form-group input:focus,.form-group textarea:focus,.form-group select:focus { border-color:var(--brand); background:var(--surface); }
        .form-group textarea { resize:vertical; }
        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
        .modal-actions { display:flex; gap:12px; margin-top:28px; justify-content:flex-end; }
        .btn-primary { display:inline-flex; align-items:center; gap:8px; background:var(--brand); color:#fff; border:none; padding:11px 24px; border-radius:var(--radius-sm); font-size:14px; font-weight:600; cursor:pointer; transition:background var(--transition); font-family:'DM Sans',sans-serif; }
        .btn-primary:hover { background:var(--brand-mid); }
        .btn-primary.loading { opacity:.7; pointer-events:none; }
        .btn-primary .btn-spin { display:none; width:14px; height:14px; border:2px solid rgba(255,255,255,.4); border-top-color:#fff; border-radius:50%; animation:spin .6s linear infinite; }
        .btn-primary.loading .btn-text { display:none; }
        .btn-primary.loading .btn-spin { display:block; }
        .btn-ghost { padding:10px 22px; border:1.5px solid var(--border); background:transparent; border-radius:var(--radius-sm); font-family:'DM Sans',sans-serif; font-size:14px; font-weight:500; cursor:pointer; color:var(--text-secondary); transition:all var(--transition); }
        .btn-ghost:hover { border-color:var(--brand); color:var(--brand); }
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
            <a href="<?= $basePath ?>/user/tasks" class="nav-item active">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                </svg>
                My Tasks
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
            <div class="topbar-title"><?= $isManager ? 'All Tasks' : 'My Tasks' ?></div>
            <div class="topbar-right">
                <span class="topbar-date" id="topbar-date"></span>
                <div class="topbar-avatar"><?= htmlspecialchars($userInitial) ?></div>
            </div>
        </header>

        <main class="content">

            <div class="page-header">
                <div>
                    <h1><?= $isManager ? 'All Tasks' : 'My Tasks' ?></h1>
                    <p id="page-subtitle">Loading tasks…</p>
                </div>
                <?php if ($isManager): ?>
                <button class="btn-primary" onclick="openCreateTask()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    New Task
                </button>
                <?php endif; ?>
            </div>

            <!-- Toolbar -->
            <div class="tasks-toolbar">
                <div class="search-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <input type="text" id="search-input" placeholder="Search tasks…">
                </div>

                <select class="filter-select" id="filter-status">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="in_review">In Review</option>
                    <option value="completed">Completed</option>
                </select>

                <select class="filter-select" id="filter-priority">
                    <option value="">All Priorities</option>
                    <option value="critical">Critical</option>
                    <option value="high">High</option>
                    <option value="medium">Medium</option>
                    <option value="low">Low</option>
                </select>

                <select class="filter-select" id="filter-project">
                    <option value="">All Projects</option>
                </select>

                <select class="filter-select" id="filter-sort">
                    <option value="urgency">Sort: Urgency</option>
                    <option value="due_asc">Due Date ↑</option>
                    <option value="due_desc">Due Date ↓</option>
                    <option value="priority">Priority</option>
                    <option value="title">Title A–Z</option>
                </select>

                <?php if ($isManager): ?>
                <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:var(--text-secondary);cursor:pointer;white-space:nowrap;padding:0 4px">
                    <input type="checkbox" id="toggle-assigned" checked style="accent-color:var(--brand);width:15px;height:15px;cursor:pointer">
                    My tasks only
                </label>
                <?php endif; ?>

                <span class="results-count" id="results-count"></span>
            </div>

            <!-- Task list -->
            <div class="tasks-panel">
                <div id="tasks-list">
                    <?php for($i=0;$i<8;$i++): ?>
                    <div style="padding:13px 24px;border-bottom:1px solid var(--border);display:flex;gap:12px;align-items:center">
                        <div class="sk-line" style="width:18px;height:18px;border-radius:50%;flex-shrink:0"></div>
                        <div style="flex:1">
                            <div class="sk-line" style="height:13px;width:55%;margin-bottom:6px"></div>
                            <div class="sk-line" style="height:10px;width:35%"></div>
                        </div>
                        <div class="sk-line" style="width:70px;height:20px;border-radius:20px"></div>
                    </div>
                    <?php endfor; ?>
                </div>
                <div id="pagination-wrap" class="pagination" style="display:none"></div>
            </div>

        </main>
    </div>
</div>

<!-- Status popover -->
<div class="status-popover" id="status-popover">
    <button class="status-opt pending"     onclick="setStatus('pending')">Pending</button>
    <button class="status-opt in_progress" onclick="setStatus('in_progress')">In Progress</button>
    <button class="status-opt in_review"   onclick="setStatus('in_review')">In Review</button>
    <button class="status-opt completed"   onclick="setStatus('completed')">Completed</button>
</div>

<?php if ($isManager): ?>
<div class="modal-backdrop" id="modal-task" onclick="closeModalOutside(event,'modal-task')">
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
                    <select id="tf-assignee" required><option value="">— Select member —</option></select>
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

<div class="toast" id="toast"><div class="toast-dot"></div><span id="toast-msg"></span></div>

<script>
    window.WH_BASE    = '<?= $basePath ?>';
    window.WH_ROLE    = '<?= $userRole ?>';
    window.WH_USER_ID = <?= (int) ($_SESSION['user_id'] ?? 0) ?>;
    const BASE       = window.WH_BASE;
    const IS_MANAGER = window.WH_ROLE === 'manager';
</script>
<script src="<?= $basePath ?>/js/user-tasks.js"></script>
</body>
</html>