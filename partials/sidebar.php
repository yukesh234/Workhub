<?php
/**
 * includes/sidebar.php
 * Reusable sidebar partial — include this in any admin page.
 *
 * Expects these vars to be set before including:
 *   $adminInitial  — first letter of admin email, uppercase
 *   $adminHandle   — part before @ in admin email
 *   $activePage    — which nav item is active: 'dashboard'|'projects'|'tasks'|'members'|'activity'|'settings'
 */
$activePage = $activePage ?? 'dashboard';
?>

<aside class="sidebar">

    <!-- ── Logo ── -->
    <div class="sidebar-logo">
        <div class="logo-mark">W</div>
        <div class="logo-text">Work<span>Hub</span></div>
    </div>

    <!-- ── Dynamic org block (filled by JS) ── -->
    <div id="sidebar-org">
        <div class="org-skeleton">
            <div class="sk-line short"></div>
            <div class="sk-line long"></div>
            <div class="sk-line tiny"></div>
        </div>
    </div>

    <!-- ── Navigation ── -->
    <nav class="sidebar-nav">

        <div class="nav-section-label">Workspace</div>

        <a href="<?= $basePath ?>/dashboard" class="nav-item <?= $activePage === 'dashboard' ? 'active' : '' ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                <rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/>
            </svg>
            Dashboard
        </a>

        <a href="<?= $basePath ?>/projects" class="nav-item disabled <?= $activePage === 'projects' ? 'active' : '' ?>" id="nav-projects">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
            </svg>
            Projects
        </a>

        <a href="<?= $basePath ?>/tasks" class="nav-item disabled <?= $activePage === 'tasks' ? 'active' : '' ?>" id="nav-tasks">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 11l3 3L22 4"/>
                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
            </svg>
            Tasks
        </a>

        <div class="nav-section-label">People</div>

        <a href="<?= $basePath ?>/members" class="nav-item disabled <?= $activePage === 'members' ? 'active' : '' ?>" id="nav-members">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
            Members
        </a>

        <div class="nav-section-label">Insights</div>

        <a href="<?= $basePath ?>/activity" class="nav-item disabled <?= $activePage === 'activity' ? 'active' : '' ?>" id="nav-activity">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
            </svg>
            Activity Log
        </a>

        <a href="<?= $basePath ?>/settings" class="nav-item disabled <?= $activePage === 'settings' ? 'active' : '' ?>" id="nav-settings">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="3"/>
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
            </svg>
            Settings
        </a>

    </nav>

    <!-- ── User footer ── -->
    <div class="sidebar-footer">
        <div class="user-card">
            <div class="user-avatar"><?= htmlspecialchars($adminInitial) ?></div>
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($adminHandle) ?></div>
                <div class="user-role">Administrator</div>
            </div>
            <button class="btn-logout-sm" onclick="handleLogout()" title="Logout">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
            </button>
        </div>
    </div>

</aside>