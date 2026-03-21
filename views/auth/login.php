<?php
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — WorkHub Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $basePath ?>/css/auth.css">
    <style>
        .role-switch {
            display: flex;
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(125,249,255,.1);
            border-radius: 10px;
            padding: 4px;
            gap: 4px;
            margin-bottom: 28px;
        }

        .role-switch a {
            flex: 1;
            text-align: center;
            padding: 8px 0;
            border-radius: 7px;
            font-size: 13px;
            font-weight: 500;
            color: rgba(255,255,255,.45);
            text-decoration: none;
            transition: all .2s ease;
        }

        .role-switch a.active {
            background: rgba(125,249,255,.12);
            color: #7df9ff;
            border: 1px solid rgba(125,249,255,.2);
        }

        .role-switch a:hover:not(.active) { color: rgba(255,255,255,.7); }

        .input-wrap {
            position: relative;
        }

        .input-wrap .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            width: 16px; height: 16px;
            stroke: rgba(125,249,255,.4);
            pointer-events: none;
        }

        .input-wrap input { padding-left: 42px; }

        .eye-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            color: rgba(255,255,255,.3);
            transition: color .2s;
            width: auto;
            margin: 0;
        }

        .eye-toggle:hover { color: rgba(125,249,255,.7); transform: translateY(-50%); box-shadow: none; }
        .eye-toggle svg   { width: 16px; height: 16px; stroke: currentColor; display: block; }

        .field-error {
            font-size: 11.5px;
            color: #f87171;
            margin-top: -12px;
            display: none;
        }

        .form-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: rgba(255,255,255,.5);
            cursor: pointer;
        }

        .remember-me input[type="checkbox"] {
            width: 15px; height: 15px;
            accent-color: #7df9ff;
            cursor: pointer;
        }

        .forgot-link {
            font-size: 13px;
            color: rgba(125,249,255,.7);
            text-decoration: none;
            transition: color .2s;
        }

        .forgot-link:hover { color: #7df9ff; }

        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            color: rgba(255,255,255,.2);
            font-size: 12px;
        }

        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(255,255,255,.08);
        }

    </style>
</head>
<body>
<main>
    <div class="wrapper">

        <!-- Back -->
        <a href="<?= $basePath ?>" class="back-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
            </svg>
            Back
        </a>

        <!-- Brand -->
        <div class="brand">
            <span class="brand-name">Work<span>Hub</span></span>
        </div>

        <div class="title">
            <h1>Welcome back</h1>
            <p>Sign in to your admin account</p>
        </div>

        <!-- Role Switch -->
        <div class="role-switch">
            <a href="#" class="active">Admin</a>
            <a href="<?= $basePath ?>/user/login">Member Login</a>
        </div>

        <!-- Server error -->
        <?php if (isset($error)): ?>
            <div class="server-error">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="<?= $basePath ?>/login" method="POST" id="loginForm" novalidate>

            <!-- Email -->
            <div class="form-group">
                <label for="email">Email address</label>
                <div class="input-wrap">
                    <input type="email" name="email" id="email"
                           placeholder="you@company.com"
                           autocomplete="email" required
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                        <polyline points="22,6 12,13 2,6"/>
                    </svg>
                </div>
                <span class="field-error" id="email-error"></span>
            </div>

            <!-- Password -->
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrap">
                    <input type="password" name="password" id="password"
                           placeholder="Your password"
                           autocomplete="current-password" required>
                    <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    <button type="button" class="eye-toggle" onclick="togglePwd('password', this)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
                <span class="field-error" id="password-error"></span>
            </div>

            <!-- Remember + Forgot -->
            <div class="form-footer">
                <label class="remember-me">
                    <input type="checkbox" name="remember" value="1">
                    Remember me
                </label>
                <a href="<?= $basePath ?>/forgot-password" class="forgot-link">Forgot password?<a/>
            </div>

            <button type="submit" id="submit-btn">
                <span class="btn-text">Sign in</span>
                <span class="btn-spinner" style="display:none"></span>
            </button>

        </form>

        <div class="divider">or</div>

        <p class="footer-link">Don't have an account? <a href="<?= $basePath ?>/register">Create one</a></p>

    </div>
</main>

<script>
    function togglePwd(inputId, btn) {
        const input = document.getElementById(inputId);
        const show  = input.type === 'password';
        input.type  = show ? 'text' : 'password';
        btn.style.color = show ? 'rgba(125,249,255,.7)' : 'rgba(255,255,255,.3)';
    }

    function fieldErr(id, msg) {
    const el = document.getElementById(id);
    el.textContent = msg;
    msg ? el.classList.add('visible') : el.classList.remove('visible');
    }

    // ── Fixed: was 'loginform' (lowercase f) → now matches id="loginForm" ──
    document.getElementById('loginForm').addEventListener('submit', function (e) {
        e.preventDefault();

        const email    = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;
        let valid = true;

        fieldErr('email-error',    '');
        fieldErr('password-error', '');

        if (!email) {
            fieldErr('email-error', 'Email is required.');
            valid = false;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            fieldErr('email-error', 'Enter a valid email address.');
            valid = false;
        }

        if (!password) {
            fieldErr('password-error', 'Password is required.');
            valid = false;
        } else if (password.length < 6) {
            fieldErr('password-error', 'Password must be at least 6 characters.');
            valid = false;
        }

        if (!valid) return;

        const btn = document.getElementById('submit-btn');
        btn.querySelector('.btn-text').style.display    = 'none';
        btn.querySelector('.btn-spinner').style.display = 'inline-block';
        btn.disabled = true;
        this.submit();
    });
</script>
</body>
</html>