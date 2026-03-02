<?php
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

// Pull admin info from session (set during login)
$adminEmail   = $_SESSION['admin_email'] ?? 'admin@workhub.com';
$adminId      = $_SESSION['admin_Id']    ?? '1';
$adminInitial = strtoupper(substr($adminEmail, 0, 1));
$adminHandle  = explode('@', $adminEmail)[0];

// Active page for sidebar highlight
$activePage = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — WorkHub</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

    <!-- Styles -->
  <link rel="stylesheet" href="<?= $basePath ?>/css/dashboard.css">
</head>
<body>
<div class="app-shell">

    <!-- ══════════════ SIDEBAR (included partial) ══════════════ -->
    <?php include __DIR__ . '/../partials/sidebar.php' ?>

    <!-- ══════════════ MAIN CONTENT ══════════════ -->
    <div class="main">

        <!-- ── Top Bar ── -->
        <header class="topbar">
            <div class="topbar-title">Dashboard</div>
            <div class="topbar-right">
                <span class="topbar-date" id="topbarDate"></span>
                <div class="topbar-avatar"><?= htmlspecialchars($adminInitial) ?></div>
            </div>
        </header>

        <!-- ── Page Content ── -->
        <main class="content">

            <!-- State: Loading -->
            <div id="main-loading" class="state active">
                <div class="spinner"></div>
                <p>Loading your workspace…</p>
            </div>

            <!-- State: No Organization -->
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
                <p class="no-org-subtitle">Set up your WorkHub workspace to start managing projects, inviting team members, and tracking progress across your team.</p>
                <button class="btn-primary" onclick="openModal()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Create Organization
                </button>
            </div>

            <!-- State: Has Organization -->
            <div id="main-has-org">

                <!-- Welcome header -->
                <div class="page-header">
                    <h1>Welcome back, <?= htmlspecialchars($adminHandle) ?> 👋</h1>
                    <p id="org-welcome-sub">Here's your organization overview.</p>
                </div>

                <!-- Org profile card -->
                <div class="org-profile-card">
                    <div class="org-card-banner"></div>
                    <div class="org-card-body">
                        <div id="org-logo-slot"></div>
                        <div class="org-card-name"   id="org-card-name"></div>
                        <div class="org-card-slogan" id="org-card-slogan"></div>
                        <div class="org-card-meta">
                            <div class="org-meta-item">
                                <div class="org-meta-value" id="org-meta-since">—</div>
                                <div class="org-meta-label">Since</div>
                            </div>
                            <div class="org-meta-item">
                                <div class="org-meta-value">—</div>
                                <div class="org-meta-label">Members</div>
                            </div>
                            <div class="org-meta-item">
                                <div class="org-meta-value">—</div>
                                <div class="org-meta-label">Projects</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick stat cards -->
                <div class="stats-row">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value">—</div>
                            <div class="stat-label">Members</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value">—</div>
                            <div class="stat-label">Projects</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 11l3 3L22 4"/>
                                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value">—</div>
                            <div class="stat-label">Tasks</div>
                        </div>
                    </div>
                </div>

                <!-- Quick actions -->
                <div class="quick-actions">
                    <div class="section-title">Quick Actions</div>
                    <div class="actions-grid">
                        <a href="#" class="action-btn disabled" id="qa-projects">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                            </svg>
                            New Project
                        </a>
                        <a href="#" class="action-btn disabled" id="qa-tasks">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                            </svg>
                            Add Task
                        </a>
                        <a href="#" class="action-btn disabled" id="qa-members">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                <circle cx="8.5" cy="7" r="4"/>
                                <line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/>
                            </svg>
                            Invite Member
                        </a>
                        <a href="#" class="action-btn disabled" id="qa-settings">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="3"/>
                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                            </svg>
                            Settings
                        </a>
                    </div>
                </div>

            </div><!-- /#main-has-org -->

            <!-- State: Error -->
            <div id="main-error" class="state">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <p id="error-msg">Something went wrong loading your workspace.</p>
                <button class="btn-ghost" onclick="fetchOrg()">Try again</button>
            </div>

        </main>
    </div><!-- /.main -->

</div><!-- /.app-shell -->


<!-- ══════════════ CREATE ORG MODAL ══════════════ -->
<div class="modal-backdrop" id="modalBackdrop" onclick="closeModalOutside(event)">
    <div class="modal">
        <div>
            <h2>Create Organization</h2>
            <p>Set up your WorkHub workspace and start collaborating with your team.</p>
        </div>

        <div class="form-error" id="form-error"></div>

        <form id="create-org-form" enctype="multipart/form-data">

            <div class="form-group">
                <label>Organization Logo <span class="opt">(optional)</span></label>
                <div class="logo-upload-area" id="upload-area">
                    <input type="file" name="organization_logo" id="logo-input" accept="image/*" onchange="previewLogo(event)">
                    <img class="upload-preview" id="upload-preview" src="" alt="Preview">
                    <div id="upload-icon">
                        <div class="upload-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="18" height="18" rx="2"/>
                                <circle cx="8.5" cy="8.5" r="1.5"/>
                                <polyline points="21 15 16 10 5 21"/>
                            </svg>
                        </div>
                        <div class="upload-label">
                            <strong>Click to upload</strong> your logo<br>
                            <span style="font-size:11px;color:var(--text-muted)">PNG, JPG or SVG — max 5MB</span>
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
                <button type="button" class="btn-ghost" onclick="closeModal()">Cancel</button>
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

<!-- Pass PHP base path to JS -->
<script>
    window.WH_BASE = '<?= $basePath ?>';
</script>

<!-- Dashboard JS -->
<script src="<?= $basePath ?>/js/dashboard.js"></script>

</body>
</html>