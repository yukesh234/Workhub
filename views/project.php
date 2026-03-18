<?php
// views/Projects.php
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
    <title>Projects — WorkHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $basePath ?>/css/dashboard.css">
    <style>
        /* ── Page layout ── */
        .projects-header {
            display: flex; align-items: flex-start; justify-content: space-between;
            margin-bottom: 28px; gap: 16px; flex-wrap: wrap;
        }
        .projects-header h1 { font-family: 'Playfair Display', serif; font-size: 28px; color: var(--text-primary); margin-bottom: 4px; }
        .projects-header p  { color: var(--text-secondary); font-size: 14px; }

        .filters-bar { display: flex; align-items: center; gap: 10px; margin-bottom: 22px; flex-wrap: wrap; }

        .filter-btn {
            padding: 7px 16px; border-radius: 20px;
            border: 1.5px solid var(--border); background: var(--surface);
            font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 500;
            color: var(--text-secondary); cursor: pointer; transition: all var(--transition);
        }
        .filter-btn:hover  { border-color: var(--brand); color: var(--brand); }
        .filter-btn.active { background: var(--brand); color: #fff; border-color: var(--brand); }

        .search-box { margin-left: auto; position: relative; }
        .search-box input {
            padding: 8px 14px 8px 36px; border: 1.5px solid var(--border); border-radius: 20px;
            font-family: 'DM Sans', sans-serif; font-size: 13px; color: var(--text-primary);
            background: var(--surface); outline: none; width: 220px; transition: border-color var(--transition);
        }
        .search-box input:focus { border-color: var(--brand); }
        .search-box svg { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); width: 15px; height: 15px; stroke: var(--text-muted); pointer-events: none; }

        .projects-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 18px; }

        /* ── Project card ── */
        .project-card {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 20px 22px;
            box-shadow: var(--shadow-sm);
            transition: box-shadow var(--transition), transform var(--transition);
            cursor: pointer; display: flex; flex-direction: column; gap: 12px;
            animation: fadeUp .35s ease both;
        }
        .project-card:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }

        .project-card-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; }
        .project-name  { font-size: 16px; font-weight: 600; color: var(--text-primary); line-height: 1.3; }
        .project-desc  { font-size: 13.5px; color: var(--text-secondary); line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

        .project-footer {
            display: flex; align-items: center; justify-content: space-between;
            margin-top: auto; padding-top: 12px; border-top: 1px solid var(--border);
        }
        .project-date { font-size: 12px; color: var(--text-muted); }

        /* ── Action buttons ── */
        .project-actions { display: flex; gap: 4px; align-items: center; }

        .project-action-btn {
            width: 28px; height: 28px; border-radius: 6px; border: none;
            background: transparent; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: background var(--transition), color var(--transition), opacity var(--transition);
            color: var(--text-muted);
        }
        .project-action-btn svg { width: 14px; height: 14px; stroke: currentColor; }

        .project-action-btn.edit-btn,
        .project-action-btn.delete { opacity: 0; transition: opacity var(--transition); }
        .project-card:hover .project-action-btn.edit-btn,
        .project-card:hover .project-action-btn.delete { opacity: 1; }

        .project-action-btn.edit-btn:hover { background: var(--brand-pale2); color: var(--brand); }
        .project-action-btn.delete:hover   { background: #fef2f2; color: #dc2626; }

        .project-action-btn.add-member {
            opacity: 1;
            background: #e6f9f1;
            color: #1a8a5c;
        }
        .project-action-btn.add-member:hover { background: #bbf0d8; color: #166548; }

        /* ── Status pills ── */
        .status-pill { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 600; text-transform: capitalize; white-space: nowrap; }
        .status-pill::before { content: ''; width: 6px; height: 6px; border-radius: 50%; }
        .status-pill.active    { background: #e6f9f1; color: #1a8a5c; }
        .status-pill.active::before    { background: #1a8a5c; }
        .status-pill.completed { background: var(--brand-pale2); color: var(--brand); }
        .status-pill.completed::before { background: var(--brand); }
        .status-pill.archived  { background: var(--surface-2); color: var(--text-muted); }
        .status-pill.archived::before  { background: var(--text-muted); }

        /* ── Empty state ── */
        .projects-empty { grid-column: 1 / -1; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 64px 24px; text-align: center; }
        .projects-empty-icon { width: 72px; height: 72px; background: var(--brand-pale); border-radius: 20px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; }
        .projects-empty-icon svg { width: 36px; height: 36px; stroke: var(--brand); }
        .projects-empty h3 { font-family: 'Playfair Display', serif; font-size: 20px; color: var(--text-primary); margin-bottom: 8px; }
        .projects-empty p  { font-size: 14px; color: var(--text-secondary); margin-bottom: 24px; }

        /* ── Skeleton ── */
        .project-skeleton { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 20px 22px; }
        .sk-block { background: var(--border); border-radius: 6px; animation: shimmer 1.4s infinite; }
        @keyframes shimmer { 0%,100%{opacity:.5} 50%{opacity:1} }
        @keyframes fadeUp  { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }

        /* ── Members Modal ── */
        #modal-members .modal {
            max-width: 480px;
            padding: 0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            max-height: 82vh;
        }

        .members-modal-header {
            padding: 24px 28px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .members-modal-header-left h2 {
            font-family: 'Playfair Display', serif;
            font-size: 20px;
            color: var(--text-primary);
            margin-bottom: 2px;
        }

        .members-modal-header-left p {
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: .6px;
        }

        .members-modal-header-right { display: flex; align-items: center; gap: 8px; }

        .btn-add-member-modal {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 7px 14px;
            background: var(--brand); color: #fff;
            border: none; border-radius: var(--radius-sm);
            font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 600;
            cursor: pointer;
            transition: background var(--transition), transform var(--transition);
        }
        .btn-add-member-modal:hover { background: var(--brand-mid); transform: translateY(-1px); }
        .btn-add-member-modal svg   { width: 13px; height: 13px; stroke: currentColor; }

        .btn-close-modal-sm {
            width: 32px; height: 32px;
            border: none; background: var(--surface-2); border-radius: 8px;
            cursor: pointer; color: var(--text-muted);
            display: flex; align-items: center; justify-content: center;
            transition: background var(--transition), color var(--transition);
        }
        .btn-close-modal-sm:hover { background: var(--brand-pale2); color: var(--brand); }
        .btn-close-modal-sm svg   { width: 15px; height: 15px; stroke: currentColor; }

        #members-modal-body {
            flex: 1;
            overflow-y: auto;
            padding: 8px 0;
        }

        /* Member rows inside modal */
        .mm-member-row {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 28px;
            transition: background var(--transition);
        }
        .mm-member-row:hover { background: var(--surface-2); }

        .mm-avatar { flex-shrink: 0; width: 40px; height: 40px; border-radius: 50%; overflow: hidden; }
        .mm-avatar-img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .mm-avatar-fallback {
            width: 40px; height: 40px; border-radius: 50%;
            background: var(--brand); color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 14px; font-weight: 700;
        }

        .mm-info { flex: 1; min-width: 0; }
        .mm-name  { font-size: 14px; font-weight: 600; color: var(--text-primary); }
        .mm-email { font-size: 12px; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        .role-badge { font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 20px; text-transform: capitalize; white-space: nowrap; }
        .role-badge.manager { background: var(--brand-pale2); color: var(--brand); }
        .role-badge.member  { background: var(--surface-2);   color: var(--text-muted); }

        .btn-remove-member {
            background: transparent; border: none; color: var(--text-muted);
            cursor: pointer; padding: 6px; border-radius: 6px;
            transition: color var(--transition), background var(--transition);
            flex-shrink: 0;
        }
        .btn-remove-member:hover { color: #dc2626; background: #fef2f2; }
        .btn-remove-member svg   { width: 14px; height: 14px; stroke: currentColor; display: block; }

        .members-modal-loading {
            display: flex; align-items: center; justify-content: center;
            padding: 48px 0;
        }

        .members-modal-empty {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            padding: 48px 24px; text-align: center; color: var(--text-muted);
        }
        .members-modal-empty svg { width: 44px; height: 44px; stroke: var(--border); margin-bottom: 12px; }
        .members-modal-empty p   { font-size: 14px; }

        /* ── Member Picker Modal ── */
        #modal-add-member .modal { max-width: 400px; padding: 0; overflow: hidden; max-height: 90vh; display: flex; flex-direction: column; }
        .picker-header { padding: 20px 24px 12px; }
        .picker-header h2 { font-family: 'Playfair Display', serif; font-size: 20px; color: var(--text-primary); margin-bottom: 2px; }
        .picker-header p  { font-size: 13px; color: var(--brand); font-weight: 600; }

        .picker-search-wrap { padding: 0 24px 12px; position: relative; }
        .picker-search-wrap svg { position: absolute; left: 36px; top: 50%; transform: translateY(-50%); width: 15px; height: 15px; stroke: var(--text-muted); pointer-events: none; }
        #picker-search { width: 100%; padding: 10px 14px 10px 38px; background: var(--surface-2); border: 1.5px solid var(--border); border-radius: 12px; font-family: 'DM Sans', sans-serif; font-size: 14px; color: var(--text-primary); outline: none; transition: border-color var(--transition); }
        #picker-search:focus { border-color: var(--brand); }
        #picker-search::placeholder { color: var(--text-muted); }

        .picker-divider { height: 1px; background: var(--border); }

        #picker-user-list { flex: 1; overflow-y: auto; padding: 8px 0; min-height: 240px; max-height: 340px; }

        .picker-user { display: flex; align-items: center; gap: 12px; padding: 10px 24px; cursor: pointer; transition: background var(--transition); user-select: none; }
        .picker-user:hover    { background: var(--surface-2); }
        .picker-user.selected { background: var(--brand-pale); }

        .picker-avatar-wrap { position: relative; flex-shrink: 0; }
        .picker-avatar { width: 42px; height: 42px; border-radius: 50%; object-fit: cover; display: block; }
        .picker-avatar-fallback { width: 42px; height: 42px; border-radius: 50%; background: var(--brand); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 15px; font-weight: 700; }

        .picker-user-info  { flex: 1; min-width: 0; }
        .picker-user-name  { font-size: 14px; font-weight: 600; color: var(--text-primary); }
        .picker-user-email { font-size: 12px; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        .picker-check { width: 24px; height: 24px; border-radius: 50%; border: 2px solid var(--border); display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: all .18s ease; }
        .picker-check svg { width: 12px; height: 12px; stroke: #fff; opacity: 0; transition: opacity .15s; }
        .picker-check.visible { background: var(--brand); border-color: var(--brand); }
        .picker-check.visible svg { opacity: 1; }
        .picker-empty { text-align: center; padding: 32px 24px; color: var(--text-muted); font-size: 14px; }

        .picker-footer { padding: 14px 24px; border-top: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; gap: 12px; background: var(--surface); }
        #picker-selected-count { font-size: 13px; font-weight: 600; color: var(--brand); min-width: 80px; }
        #picker-confirm-btn:disabled { opacity: .45; cursor: not-allowed; transform: none !important; box-shadow: none !important; }
    </style>
</head>
<body>
<div class="app-shell">

    <?php include __DIR__ . '/../partials/sidebar.php'; ?>

    <div class="main">
        <header class="topbar">
            <div class="topbar-title">Projects</div>
            <div class="topbar-right">
                <span class="topbar-date" id="topbarDate"></span>
                <div class="topbar-avatar"><?= htmlspecialchars($adminInitial) ?></div>
            </div>
        </header>

        <main class="content">
            <div class="projects-header">
                <div>
                    <h1>Projects</h1>
                    <p id="projects-subtitle">Loading your projects…</p>
                </div>
                <button class="btn-primary" onclick="openModal('modal-create-project')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    New Project
                </button>
            </div>

            <div class="filters-bar">
                <button class="filter-btn active" data-filter="all">All</button>
                <button class="filter-btn" data-filter="active">Active</button>
                <button class="filter-btn" data-filter="completed">Completed</button>
                <button class="filter-btn" data-filter="archived">Archived</button>
                <div class="search-box">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <input type="text" id="search-input" placeholder="Search projects…">
                </div>
            </div>

            <div class="projects-grid" id="projects-grid">
                <?php for ($i = 0; $i < 6; $i++): ?>
                <div class="project-skeleton">
                    <div class="sk-block" style="height:14px;width:60%;margin-bottom:10px"></div>
                    <div class="sk-block" style="height:10px;width:90%;margin-bottom:6px"></div>
                    <div class="sk-block" style="height:10px;width:75%;margin-bottom:18px"></div>
                    <div class="sk-block" style="height:10px;width:40%"></div>
                </div>
                <?php endfor; ?>
            </div>
        </main>
    </div>
</div>


<!-- ══ Members Modal (card click) ══ -->
<div class="modal-backdrop" id="modal-members"
     onclick="closeModalOutside(event, 'modal-members')">
    <div class="modal">

        <div class="members-modal-header">
            <div class="members-modal-header-left">
                <h2 id="members-modal-title">Project Members</h2>
                <p>Team</p>
            </div>
            <div class="members-modal-header-right">
                <button class="btn-add-member-modal"
                        onclick="closeModal('modal-members'); openMemberPicker(activeMembersProjectId, document.getElementById('members-modal-title').textContent)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                         stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Add Member
                </button>
                <button class="btn-close-modal-sm" onclick="closeModal('modal-members')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
        </div>

        <div id="members-modal-body">
            <!-- filled by JS -->
        </div>

    </div>
</div>


<!-- ══ Add Member Picker Modal ══ -->
<div class="modal-backdrop" id="modal-add-member"
     onclick="closeModalOutside(event, 'modal-add-member')">
    <div class="modal">
        <div class="picker-header">
            <h2>Add Members</h2>
            <p id="picker-project-name"></p>
        </div>
        <div class="picker-search-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input type="text" id="picker-search" placeholder="Search by name or email…" autocomplete="off">
        </div>
        <div class="picker-divider"></div>
        <div id="picker-user-list"></div>
        <div class="picker-footer">
            <span id="picker-selected-count"></span>
            <div style="display:flex;gap:10px">
                <button class="btn-ghost" onclick="closeModal('modal-add-member')">Cancel</button>
                <button class="btn-primary" id="picker-confirm-btn" disabled>
                    <div class="btn-spin"></div>
                    <span class="btn-text">Add to Project</span>
                </button>
            </div>
        </div>
    </div>
</div>


<!-- ══ Create Project Modal ══ -->
<div class="modal-backdrop" id="modal-create-project"
     onclick="closeModalOutside(event, 'modal-create-project')">
    <div class="modal">
        <h2>New Project</h2>
        <p style="font-size:14px;color:var(--text-secondary);margin-bottom:24px">Create a new project for your organization.</p>
        <div class="form-error" id="create-project-error"></div>
        <form id="create-project-form">
            <div class="form-group">
                <label for="cp-name">Project Name <span style="color:var(--brand)">*</span></label>
                <input type="text" id="cp-name" name="name" placeholder="e.g. Q3 Marketing Campaign" required maxlength="255">
            </div>
            <div class="form-group">
                <label for="cp-desc">Description <span class="opt">(optional)</span></label>
                <textarea id="cp-desc" name="description" rows="3" placeholder="What is this project about?"></textarea>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-ghost" onclick="closeModal('modal-create-project')">Cancel</button>
                <button type="submit" class="btn-primary" id="create-project-btn">
                    <div class="btn-spin"></div>
                    <span class="btn-text" style="display:flex;align-items:center;gap:8px">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>Create Project
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>


<!-- ══ Edit Project Modal ══ -->
<div class="modal-backdrop" id="modal-edit-project"
     onclick="closeModalOutside(event, 'modal-edit-project')">
    <div class="modal">
        <h2>Edit Project</h2>
        <p style="font-size:14px;color:var(--text-secondary);margin-bottom:24px">Update the project details.</p>
        <div class="form-error" id="edit-project-error"></div>
        <form id="edit-project-form">
            <input type="hidden" id="ep-id" name="project_id">
            <div class="form-group">
                <label for="ep-name">Project Name <span style="color:var(--brand)">*</span></label>
                <input type="text" id="ep-name" name="name" required maxlength="255">
            </div>
            <div class="form-group">
                <label for="ep-desc">Description <span class="opt">(optional)</span></label>
                <textarea id="ep-desc" name="description" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label for="ep-status">Status</label>
                <select id="ep-status" name="status" style="width:100%;padding:11px 14px;border:1.5px solid var(--border);border-radius:var(--radius-sm);font-family:'DM Sans',sans-serif;font-size:14px;background:var(--surface-2);color:var(--text-primary);outline:none;">
                    <option value="active">Active</option>
                    <option value="completed">Completed</option>
                    <option value="archived">Archived</option>
                </select>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-ghost" onclick="closeModal('modal-edit-project')">Cancel</button>
                <button type="submit" class="btn-primary" id="edit-project-btn">
                    <div class="btn-spin"></div>
                    <span class="btn-text">Save Changes</span>
                </button>
            </div>
        </form>
    </div>
</div>


<!-- ══ Delete Confirm Modal ══ -->
<div class="modal-backdrop" id="modal-delete-project"
     onclick="closeModalOutside(event, 'modal-delete-project')">
    <div class="modal" style="max-width:420px;text-align:center">
        <div style="width:60px;height:60px;background:#fef2f2;border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 20px">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="3 6 5 6 21 6"/>
                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                <path d="M10 11v6"/><path d="M14 11v6"/>
                <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
            </svg>
        </div>
        <h2 style="font-size:20px;margin-bottom:8px">Delete Project?</h2>
        <p id="delete-project-msg" style="font-size:14px;color:var(--text-secondary);margin-bottom:28px">This will permanently delete the project and all its tasks.</p>
        <input type="hidden" id="delete-project-id">
        <div class="modal-actions" style="justify-content:center">
            <button class="btn-ghost" onclick="closeModal('modal-delete-project')">Cancel</button>
            <button class="btn-primary" id="confirm-delete-btn" style="background:#dc2626;box-shadow:0 4px 18px rgba(220,38,38,.3)">
                <div class="btn-spin"></div>
                <span class="btn-text">Delete Project</span>
            </button>
        </div>
    </div>
</div>


<script> window.WH_BASE = '<?= $basePath ?>'; </script>
<script src="<?= $basePath ?>/js/app.js"></script>
<script src="<?= $basePath ?>/js/Projects.js"></script>
</body>
</html>
