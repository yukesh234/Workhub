<?php
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — WorkHub Admin</title>
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

        .role-switch a:hover:not(.active) {
            color: rgba(255,255,255,.7);
        }

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
            transition: stroke .2s;
        }

        .input-wrap input {
            padding-left: 42px;
        }

        .input-wrap input:focus ~ .input-icon,
        .input-wrap input:focus + .input-icon {
            stroke: rgba(125,249,255,.7);
        }

        /* eye toggle */
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

        .strength-bar {
            height: 3px;
            border-radius: 3px;
            background: rgba(255,255,255,.08);
            margin-top: -12px;
            overflow: hidden;
        }

        .strength-fill {
            height: 100%;
            border-radius: 3px;
            width: 0;
            transition: width .3s, background .3s;
        }

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
            <div class="brand-mark">W</div>
            <span class="brand-name">Work<span>Hub</span></span>
        </div>

        <div class="title">
            <h1>Create account</h1>
            <p>Set up your admin workspace</p>
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

        <form action="<?= $basePath ?>/register" method="POST" id="registerForm" novalidate>

            <!-- Email -->
            <div class="form-group">
                <label for="email">Email address</label>
                <div class="input-wrap">
                    <input type="email" name="email" id="email" placeholder="you@company.com"
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
                           placeholder="Min 6 characters" autocomplete="new-password" required>
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
                <div class="strength-bar"><div class="strength-fill" id="strength-fill"></div></div>
                <span class="field-error" id="password-error"></span>
            </div>

            <!-- Confirm Password -->
            <div class="form-group">
                <label for="repassword">Confirm password</label>
                <div class="input-wrap">
                    <input type="password" name="repassword" id="repassword"
                           placeholder="Repeat your password" autocomplete="new-password" required>
                    <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 11l3 3L22 4"/>
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                    </svg>
                    <button type="button" class="eye-toggle" onclick="togglePwd('repassword', this)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
                <span class="field-error" id="repassword-error"></span>
            </div>

            <button type="submit" id="submit-btn">
                <span class="btn-text">Create account</span>
                <span class="btn-spinner" style="display:none"></span>
            </button>

        </form>

        <div class="divider">or</div>

        <p class="footer-link">Already have an account? <a href="<?= $basePath ?>/login">Sign in</a></p>

    </div>
</main>

<script>
    // ── Password strength ─────────────────────────────────────────────
    document.getElementById('password').addEventListener('input', function () {
        const val   = this.value;
        const fill  = document.getElementById('strength-fill');
        let score = 0;
        if (val.length >= 6)  score++;
        if (val.length >= 10) score++;
        if (/[A-Z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;

        const pct    = (score / 5) * 100;
        const colors = ['#f87171','#fb923c','#facc15','#34d399','#7df9ff'];
        fill.style.width      = pct + '%';
        fill.style.background = colors[Math.max(score - 1, 0)];
    });

    // ── Toggle password visibility ────────────────────────────────────
    function togglePwd(inputId, btn) {
        const input = document.getElementById(inputId);
        const show  = input.type === 'password';
        input.type  = show ? 'text' : 'password';
        btn.style.color = show ? 'rgba(125,249,255,.7)' : 'rgba(255,255,255,.3)';
    }

    // ── Show field error ──────────────────────────────────────────────
    function fieldErr(id, msg) {
    const el = document.getElementById(id);
    el.textContent = msg;
    msg ? el.classList.add('visible') : el.classList.remove('visible');
    }

    function clearErrors() {
        ['email-error','password-error','repassword-error'].forEach(id => fieldErr(id, ''));
    }

    // ── Form validation ───────────────────────────────────────────────
    document.getElementById('registerForm').addEventListener('submit', function (e) {
        e.preventDefault();
        clearErrors();

        const email      = document.getElementById('email').value.trim();
        const password   = document.getElementById('password').value;
        const repassword = document.getElementById('repassword').value;
        let valid = true;

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

        if (!repassword) {
            fieldErr('repassword-error', 'Please confirm your password.');
            valid = false;
        } else if (password !== repassword) {
            fieldErr('repassword-error', 'Passwords do not match.');
            valid = false;
        }

        if (!valid) return;

        // Show loading state then submit
        const btn = document.getElementById('submit-btn');
        btn.querySelector('.btn-text').style.display    = 'none';
        btn.querySelector('.btn-spinner').style.display = 'inline-block';
        btn.disabled = true;
        this.submit();
    });
</script>
</body>
</html>