<?php
// views/user/ChangePassword.php
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$userName = $_SESSION['user_name'] ?? 'there';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Your Password — WorkHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,400;0,500;0,600;0,700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --brand:      #6A0031;
            --brand-mid:  #8a1144;
            --brand-pale: #fdf2f6;
            --accent:     #E8A045;
            --border:     #e8dde3;
            --surface:    #ffffff;
            --text:       #1a1218;
            --muted:      #a08898;
            --radius:     12px;
            --transition: 0.22s cubic-bezier(.4,0,.2,1);
        }

        html, body {
            min-height: 100vh;
            font-family: 'DM Sans', sans-serif;
            background: #0f0008;
            color: var(--text);
            display: flex; align-items: center; justify-content: center;
        }

        /* Subtle background pattern */
        body::before {
            content: '';
            position: fixed; inset: 0;
            background:
                radial-gradient(ellipse 80% 60% at 20% 0%, rgba(106,0,49,.35) 0%, transparent 60%),
                radial-gradient(ellipse 60% 50% at 80% 100%, rgba(184,36,95,.2) 0%, transparent 60%);
            pointer-events: none;
        }

        .page-wrap {
            width: 100%; max-width: 440px;
            padding: 24px 16px; position: relative; z-index: 1;
        }

        /* Lock icon at top */
        .lock-icon-wrap {
            width: 64px; height: 64px;
            background: linear-gradient(135deg, var(--brand), var(--brand-mid));
            border-radius: 18px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 28px;
            box-shadow: 0 8px 32px rgba(106,0,49,.4);
        }
        .lock-icon-wrap svg { width: 28px; height: 28px; stroke: #fff; }

        .card {
            background: rgba(255,255,255,.97);
            border-radius: 20px;
            padding: 36px 36px 32px;
            box-shadow: 0 24px 64px rgba(0,0,0,.4);
        }

        .card-title {
            font-family: 'Playfair Display', serif;
            font-size: 26px; color: var(--brand);
            text-align: center; margin-bottom: 8px;
        }

        .card-sub {
            font-size: 14px; color: #6b5b65;
            text-align: center; margin-bottom: 28px; line-height: 1.6;
        }

        /* Warning banner */
        .first-login-notice {
            background: #fffbeb; border: 1px solid #fde68a;
            border-radius: 8px; padding: 10px 14px;
            font-size: 13px; color: #92400e;
            display: flex; align-items: flex-start; gap: 8px;
            margin-bottom: 24px;
        }
        .first-login-notice svg { width: 15px; height: 15px; stroke: currentColor; flex-shrink: 0; margin-top: 1px; }

        /* Form */
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: var(--text); margin-bottom: 6px; }

        .input-wrap { position: relative; }
        .input-wrap input {
            width: 100%; padding: 12px 44px 12px 14px;
            border: 1.5px solid var(--border); border-radius: var(--radius);
            font-family: 'DM Sans', sans-serif; font-size: 14px;
            color: var(--text); background: #faf7f9;
            outline: none; transition: border-color var(--transition), background var(--transition);
        }
        .input-wrap input:focus { border-color: var(--brand); background: #fff; }

        .eye-toggle {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            color: var(--muted); padding: 4px;
            transition: color var(--transition);
        }
        .eye-toggle:hover { color: var(--brand); }
        .eye-toggle svg { width: 16px; height: 16px; stroke: currentColor; display: block; }

        /* Strength bar */
        .strength-wrap { margin-top: 8px; }
        .strength-track { height: 4px; background: var(--border); border-radius: 2px; overflow: hidden; }
        .strength-fill  { height: 100%; border-radius: 2px; width: 0%; transition: width .3s ease, background .3s ease; }
        .strength-label { font-size: 11px; margin-top: 4px; font-weight: 600; }
        .strength-fill.s1 { background: #ef4444; width: 20%; }
        .strength-fill.s2 { background: #f97316; width: 40%; }
        .strength-fill.s3 { background: #f59e0b; width: 60%; }
        .strength-fill.s4 { background: #22c55e; width: 80%; }
        .strength-fill.s5 { background: #16a34a; width: 100%; }
        .strength-label.s1 { color: #ef4444; }
        .strength-label.s2 { color: #f97316; }
        .strength-label.s3 { color: #f59e0b; }
        .strength-label.s4 { color: #22c55e; }
        .strength-label.s5 { color: #16a34a; }

        /* Requirements list */
        .requirements { margin-top: 10px; display: flex; flex-direction: column; gap: 4px; }
        .req-item { display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--muted); transition: color var(--transition); }
        .req-item.met { color: #16a34a; }
        .req-item svg { width: 12px; height: 12px; stroke: currentColor; flex-shrink: 0; }

        /* Match indicator */
        .match-msg { font-size: 12px; font-weight: 600; margin-top: 6px; min-height: 16px; }
        .match-msg.ok  { color: #16a34a; }
        .match-msg.bad { color: #ef4444; }

        /* Error */
        .form-error { background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 10px 14px; font-size: 13px; color: #b91c1c; margin-bottom: 16px; display: none; }

        /* Submit button */
        .btn-submit {
            width: 100%; padding: 14px;
            background: var(--brand); color: #fff;
            border: none; border-radius: var(--radius);
            font-family: 'DM Sans', sans-serif;
            font-size: 15px; font-weight: 700; cursor: pointer;
            transition: background var(--transition), transform var(--transition), box-shadow var(--transition);
            display: flex; align-items: center; justify-content: center; gap: 10px;
            box-shadow: 0 4px 18px rgba(106,0,49,.3);
            margin-top: 24px;
        }
        .btn-submit:hover:not(:disabled) { background: var(--brand-mid); transform: translateY(-1px); box-shadow: 0 8px 28px rgba(106,0,49,.4); }
        .btn-submit:disabled { opacity: .5; cursor: not-allowed; transform: none; box-shadow: none; }

        .btn-spin { display: none; width: 16px; height: 16px; border: 2px solid rgba(255,255,255,.4); border-top-color: #fff; border-radius: 50%; animation: spin .6s linear infinite; }
        .btn-submit.loading .btn-text { display: none; }
        .btn-submit.loading .btn-spin { display: block; }

        @keyframes spin { to { transform: rotate(360deg); } }

        /* Logo bottom */
        .bottom-logo { text-align: center; margin-top: 24px; font-family: 'Playfair Display', serif; font-size: 16px; color: rgba(255,255,255,.4); }
        .bottom-logo span { color: var(--accent); }
    </style>
</head>
<body>
<div class="page-wrap">

    <div class="lock-icon-wrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
        </svg>
    </div>

    <div class="card">
        <div class="card-title">Set Your Password</div>
        <div class="card-sub">
            Hey <strong><?= htmlspecialchars($userName) ?></strong>, your account was created with a temporary password.
            Set a new one to continue.
        </div>

        <div class="first-login-notice">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
            You must set a new password before you can access WorkHub.
        </div>

        <div class="form-error" id="form-error"></div>

        <div class="form-group">
            <label for="new-password">New Password</label>
            <div class="input-wrap">
                <input type="password" id="new-password" placeholder="Min. 8 characters"
                       autocomplete="new-password" oninput="checkStrength(this.value)">
                <button type="button" class="eye-toggle" onclick="toggleEye('new-password', this)">
                    <svg id="eye-new" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                </button>
            </div>

            <!-- Strength bar -->
            <div class="strength-wrap">
                <div class="strength-track"><div class="strength-fill" id="strength-fill"></div></div>
                <div class="strength-label" id="strength-label"></div>
            </div>

            <!-- Requirements -->
            <div class="requirements">
                <div class="req-item" id="req-len">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    At least 8 characters
                </div>
                <div class="req-item" id="req-upper">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    One uppercase letter
                </div>
                <div class="req-item" id="req-num">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    One number
                </div>
                <div class="req-item" id="req-special">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    One special character
                </div>
            </div>
        </div>

        <div class="form-group">
            <label for="confirm-password">Confirm Password</label>
            <div class="input-wrap">
                <input type="password" id="confirm-password" placeholder="Repeat your password"
                       autocomplete="new-password" oninput="checkMatch()">
                <button type="button" class="eye-toggle" onclick="toggleEye('confirm-password', this)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                </button>
            </div>
            <div class="match-msg" id="match-msg"></div>
        </div>

        <button class="btn-submit" id="submit-btn" disabled onclick="submitChange()">
            <div class="btn-spin"></div>
            <span class="btn-text">Set Password &amp; Continue</span>
        </button>
    </div>

    <div class="bottom-logo">Work<span>Hub</span></div>
</div>

<script>
const BASE = '<?= $basePath ?>';

let strengthScore = 0;
let passwordsMatch = false;

function checkStrength(val) {
    const checks = {
        len:     val.length >= 8,
        upper:   /[A-Z]/.test(val),
        num:     /[0-9]/.test(val),
        special: /[^A-Za-z0-9]/.test(val),
    };

    // Update requirement items
    Object.entries(checks).forEach(([key, met]) => {
        document.getElementById('req-' + key)?.classList.toggle('met', met);
    });

    strengthScore = Object.values(checks).filter(Boolean).length;
    const fill  = document.getElementById('strength-fill');
    const label = document.getElementById('strength-label');
    const levels = ['', 'Weak', 'Fair', 'Good', 'Strong', 'Very Strong'];
    fill.className  = 'strength-fill' + (strengthScore ? ' s' + strengthScore : '');
    label.className = 'strength-label' + (strengthScore ? ' s' + strengthScore : '');
    label.textContent = val.length ? levels[strengthScore] : '';

    checkMatch();
    updateSubmitState();
}

function checkMatch() {
    const pw  = document.getElementById('new-password').value;
    const cfm = document.getElementById('confirm-password').value;
    const msg = document.getElementById('match-msg');

    if (!cfm) { msg.textContent = ''; passwordsMatch = false; updateSubmitState(); return; }

    if (pw === cfm) {
        msg.textContent = '✓ Passwords match';
        msg.className   = 'match-msg ok';
        passwordsMatch  = true;
    } else {
        msg.textContent = '✗ Passwords do not match';
        msg.className   = 'match-msg bad';
        passwordsMatch  = false;
    }
    updateSubmitState();
}

function updateSubmitState() {
    const ready = strengthScore >= 3 && passwordsMatch;
    document.getElementById('submit-btn').disabled = !ready;
}

function toggleEye(inputId, btn) {
    const input = document.getElementById(inputId);
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    btn.querySelector('svg').innerHTML = isHidden
        ? `<line x1="1" y1="1" x2="23" y2="23"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><path d="M6.53 6.53A10.01 10.01 0 0 0 1 12s4 8 11 8a9.73 9.73 0 0 0 5.39-1.61"/><line x1="1" y1="1" x2="23" y2="23"/>`
        : `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>`;
}

async function submitChange() {
    const btn     = document.getElementById('submit-btn');
    const errEl   = document.getElementById('form-error');
    const password = document.getElementById('new-password').value;
    const confirm  = document.getElementById('confirm-password').value;

    errEl.style.display = 'none';
    btn.classList.add('loading');

    try {
        const res  = await fetch(BASE + '/user/change-password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ password, confirm }),
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);

        // Redirect to dashboard after success
        window.location.href = BASE + '/user/dashboard';
    } catch (err) {
        errEl.textContent   = err.message;
        errEl.style.display = 'block';
        btn.classList.remove('loading');
    }
}
</script>
</body>
</html>