
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/public/css/global.css">
    <title>Document</title>
</head>
<body>
    <?php
    require_once __DIR__ . '/../partials/nav.php';  
    ?>
     <section class="hero">
        <h1>Manage Projects, <span class="highlight">Empower Teams</span></h1>
        <p>WorkHub simplifies project management for teams of all sizes. Create organizations, assign roles, and track progress effortlessly.</p>
        <div class="cta-buttons">
            <a href="<?= $basePath ?>/register" class="btn btn-primary">Get Started Free</a>
            <a href="#features" class="btn btn-secondary">Learn More</a>
        </div>
    </section>

    <section class="features" id="features">
        <h2>Everything You Need</h2>
        <div class="feature-grid">
            <div class="feature-card">
                <h3>🏢 Organization Management</h3>
                <p>Create and manage multiple organizations with ease. Keep your teams organized and productive.</p>
            </div>
            <div class="feature-card">
                <h3>👥 Team Collaboration</h3>
                <p>Invite team members, assign roles, and collaborate seamlessly on projects.</p>
            </div>
            <div class="feature-card">
                <h3>📊 Project Tracking</h3>
                <p>Track project progress, assign tasks, and meet deadlines with powerful project management tools.</p>
            </div>
            <div class="feature-card">
                <h3>✅ Task Management</h3>
                <p>Create, assign, and track todos. Managers create tasks, members update status.</p>
            </div>
            <div class="feature-card">
                <h3>🔐 Role-Based Access</h3>
                <p>Secure your workspace with role-based permissions. Managers and members have distinct capabilities.</p>
            </div>
            <div class="feature-card">
                <h3>📧 Email Verification</h3>
                <p>Secure authentication with email verification. Keep your organization safe.</p>
            </div>
        </div>
    </section>

    <footer>
        <p>&copy; <?= date('Y') ?> WorkHub. All rights reserved.</p>
    </footer>
</body>
</html>
</body>
</html>