<?php
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Login — WorkHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $basePath ?>/css/auth.css">
    <style>
        /* Member login uses a warm accent to visually differentiate from admin */
        :root {
            --member-accent: #f59e0b;
            --member-accent-dim: rgba(245,158,11,.15);
            --member-accent-border: rgba(245,158,11,.25);
            --member-glow: rgba(245,158,11,.2);
        }

        /* Override the cyan theme for member page */
        .title h1 {
            background: linear-gradient(135deg, #ffffff 0%, var(--member-accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .wrapper {
            border-color: var(--member-accent-border);
            box-shadow:
                0 8px 32px rgba(0,0,0,.4),
                0 0 0 1px rgba(245,158,11,.04);
        }

        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: rgba(245,158,11,.5) !important;
            box-shadow: 0 0 0 3px rgba(245,158,11,.1) !important;
        }

        button[type="submit"] {
            background: linear-gradient(135deg, var(--member-accent) 0%, #d97706 100%);
            color: #1a0a00;
        }

        button[type="submit"]:hover {
            box-shadow: 0 8px 20px var(--member-glow);
        }

        .role-switch {
            display: flex;
            background: rgba(255,255,255,.04);
            border: 1px solid var(--member-accent-border);
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
            background: var(--member-accent-dim);
            color: var(--member-accent);
            border: 1px solid var(--member-accent-border);
        }

        .role-switch a:hover:not(.active) { color: rgba(255,255,255,.7); }

        .member-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--member-accent-dim);
            border: 1px solid var(--member-accent-border);
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 12px;
            font-weight: 600;
            color: var(--member-accent);
            margin-bottom: 16px;
        }

        .member-badge svg { width: 12px; height: 12px; stroke: currentColor; }

        .input-wrap {
            position: relative;
        }

        .input-wrap .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            width: 16px; height: 16px;
            stroke: rgba(245,158,11,.4);
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

        .eye-toggle:hover { color: rgba(245,158,11,.8); transform: translateY(-50%); box-shadow: none; }
        .eye-toggle svg   { width: 16px; height: 16px; stroke: currentColor; display: block; }

        .field-error {
            font-size: 11.5px;
            color: #f87171;
            margin-top: -12px;
            display: none;
        }

        .info-box {
            background: rgba(245,158,11,.06);
            border: 1px solid rgba(245,158,11,.15);
            border-radius: 8px;
            padding: 12px 14px;
            font-size: 13px;
            color: rgba(255,255,255,.55);
            line-height: 1.5;
            margin-bottom: 4px;
        }

        .info-box strong { color: rgba(245,158,11,.9); }

        .footer-link a { color: rgba(245,158,11,.8); }
        .footer-link a:hover { color: var(--member-accent); }
    </style>
</head>
<body>
<main>
    <div class="wrapper">

        <!-- Back -->
        <a href="<?= $basePath ?>/login" class="back-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
            </svg>
            Admin login
        </a>

        <!-- Brand -->
        <div class="brand">
            <span class="brand-name">Work<span style="-webkit-text-fill-color:#f59e0b">Hub</span></span>
        </div>

        <!-- Member badge -->
        <div style="margin-bottom:4px">
            <span class="member-badge">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                </svg>
                Team Member
            </span>
        </div>

        <div class="title">
            <h1>Member login</h1>
            <p>Sign in with your team credentials</p>
        </div>

        <!-- Role switch -->
        <div class="role-switch">
            <a href="<?= $basePath ?>/login">Admin</a>
            <a href="#" class="active">Member</a>
        </div>

        <!-- Info box -->
        <div class="info-box">
            Your login credentials were <strong>provided by your organization admin.</strong>
            Check your email for your temporary password.
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

        <form action="<?= $basePath ?>/user/login" method="POST" id="userLoginForm" novalidate>

            <!-- Email -->
            <div class="form-group">
                <label for="email">Email address</label>
                <div class="input-wrap">
                    <input type="email" name="email" id="email"
                           placeholder="your@email.com"
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

            <button type="submit" id="submit-btn">
                <span class="btn-text">Sign in as Member</span>
                <span class="btn-spinner" style="display:none"></span>
            </button>

        </form>

        <p class="footer-link" style="text-align:center;margin-top:20px;font-size:13px;color:rgba(255,255,255,.4)">
            Not a member yet? Ask your <a href="<?= $basePath ?>/login">organization admin</a> to invite you.
        </p>

    </div>
</main>

<script>
    function togglePwd(inputId, btn) {
        const input = document.getElementById(inputId);
        const show  = input.type === 'password';
        input.type  = show ? 'text' : 'password';
        btn.style.color = show ? 'rgba(245,158,11,.8)' : 'rgba(255,255,255,.3)';
    }

    function fieldErr(id, msg) {
    const el = document.getElementById(id);
    el.textContent = msg;
    msg ? el.classList.add('visible') : el.classList.remove('visible');
    }

    document.getElementById('userLoginForm').addEventListener('submit', function (e) {
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