<?php
// views/auth/ForgotPassword.php
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — WorkHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $basePath ?>/css/auth.css">
    <style>
        /* Steps */
        .step         { display: none; }
        .step.active  { display: block; }

        /* OTP row */
        .otp-row {
            display: flex; gap: 8px; justify-content: center;
            margin: 14px 0 6px;
        }
        .otp-box {
            width: 46px; height: 54px;
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 9px;
            font-family: 'Playfair Display', serif;
            font-size: 22px; font-weight: 700;
            text-align: center; color: #e8e8e8;
            outline: none;
            transition: border-color .2s, background .2s, box-shadow .2s;
            appearance: none;
        }
        .otp-box:focus {
            border-color: rgba(125,249,255,.4);
            background: rgba(255,255,255,.06);
            box-shadow: 0 0 0 3px rgba(125,249,255,.07);
        }
        .otp-box.filled {
            border-color: rgba(125,249,255,.3);
            color: #7df9ff;
        }

        /* Password strength bar */
        .pw-bar-wrap {
            height: 3px; background: rgba(255,255,255,.08);
            border-radius: 2px; margin-top: 6px; overflow: hidden;
        }
        .pw-bar-fill {
            height: 100%; border-radius: 2px;
            transition: width .3s, background .3s; width: 0;
        }

        /* Resend */
        .resend-wrap {
            text-align: center; margin-top: 12px;
            font-size: 13px; color: rgba(255,255,255,.38);
        }
        .resend-btn {
            background: none; border: none;
            color: rgba(125,249,255,.7); font-weight: 600;
            cursor: pointer; font-family: 'DM Sans', sans-serif;
            font-size: 13px; transition: color .2s;
        }
        .resend-btn:hover   { color: #7df9ff; }
        .resend-btn:disabled { color: rgba(255,255,255,.25); cursor: not-allowed; }

        /* Success icon */
        .success-icon {
            width: 58px; height: 58px; border-radius: 50%;
            background: rgba(125,249,255,.06);
            border: 1px solid rgba(125,249,255,.18);
            display: flex; align-items: center; justify-content: center;
            margin: 4px auto 18px;
        }
        .success-icon svg { width: 26px; height: 26px; stroke: #7df9ff; }

        /* Generic submit button not type=submit (steps 1–3 use type=button) */
        .btn-action {
            width: 100%; margin-top: 16px; padding: 12px;
            background: linear-gradient(135deg, #7df9ff 0%, #4ecad8 100%);
            border: none; border-radius: 9px;
            color: #050f10; font-size: 14px; font-weight: 700;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            transition: transform .2s, box-shadow .2s, opacity .2s;
            display: flex; align-items: center; justify-content: center;
            gap: 8px; letter-spacing: .2px;
        }
        .btn-action:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 22px rgba(125,249,255,.22);
        }
        .btn-action:disabled { opacity: .6; cursor: not-allowed; }

        /* Go to login reuses btn-action look */
        .btn-goto-login {
            display: flex; align-items: center; justify-content: center;
            text-decoration: none;
        }
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
            Back to login
        </a>

        <!-- Brand -->
        <div class="brand">
            <span class="brand-name">Work<span>Hub</span></span>
        </div>

        <!-- ════════════════════════════════════════════════════════
             Step 1 — Email
        ════════════════════════════════════════════════════════ -->
        <div class="step active" id="step-email">

            <div class="title">
                <h1>Forgot password?</h1>
                <p>Enter your admin email and we'll send a reset code</p>
            </div>

            <div class="server-error" id="email-error" style="display:none">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <span id="email-error-text"></span>
            </div>

            <div class="form-group">
                <label for="input-email">Email address</label>
                <div class="input-wrap">
                    <input type="email" id="input-email" placeholder="admin@yourcompany.com" autocomplete="email">
                    <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                        <polyline points="22,6 12,13 2,6"/>
                    </svg>
                </div>
                <span class="field-error" id="email-field-error"></span>
            </div>

            <button type="button" class="btn-action" id="btn-send-otp" onclick="sendOTP()">
                <span class="btn-text">Send Reset Code</span>
                <span class="btn-spinner" style="display:none"></span>
            </button>

        </div>

        <!-- ════════════════════════════════════════════════════════
             Step 2 — OTP
        ════════════════════════════════════════════════════════ -->
        <div class="step" id="step-otp">

            <div class="title">
                <h1>Check your email</h1>
                <p id="otp-subtitle">Enter the 6-digit code we sent you</p>
            </div>

            <div class="server-error" id="otp-error" style="display:none">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <span id="otp-error-text"></span>
            </div>

            <div class="otp-row">
                <input class="otp-box" maxlength="1" type="text" inputmode="numeric" pattern="[0-9]">
                <input class="otp-box" maxlength="1" type="text" inputmode="numeric" pattern="[0-9]">
                <input class="otp-box" maxlength="1" type="text" inputmode="numeric" pattern="[0-9]">
                <input class="otp-box" maxlength="1" type="text" inputmode="numeric" pattern="[0-9]">
                <input class="otp-box" maxlength="1" type="text" inputmode="numeric" pattern="[0-9]">
                <input class="otp-box" maxlength="1" type="text" inputmode="numeric" pattern="[0-9]">
            </div>
            <span class="field-error" id="otp-field-error"></span>

            <button type="button" class="btn-action" id="btn-verify-otp" onclick="verifyOTP()">
                <span class="btn-text">Verify Code</span>
                <span class="btn-spinner" style="display:none"></span>
            </button>

            <div class="resend-wrap">
                Didn't get it?
                <button class="resend-btn" id="btn-resend" onclick="resendOTP()">Resend code</button>
            </div>

        </div>

        <!-- ════════════════════════════════════════════════════════
             Step 3 — New Password
        ════════════════════════════════════════════════════════ -->
        <div class="step" id="step-password">

            <div class="title">
                <h1>Set new password</h1>
                <p>Choose a strong password for your account</p>
            </div>

            <div class="server-error" id="pw-error" style="display:none">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <span id="pw-error-text"></span>
            </div>

            <div class="form-group">
                <label for="input-new-pw">New Password</label>
                <div class="input-wrap">
                    <input type="password" id="input-new-pw" placeholder="At least 8 characters" oninput="checkStrength(this.value)">
                    <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    <button type="button" class="eye-toggle" onclick="togglePwd('input-new-pw', this)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
                <div class="pw-bar-wrap"><div class="pw-bar-fill" id="pw-bar"></div></div>
                <span class="field-error" id="new-pw-field-error"></span>
            </div>

            <div class="form-group">
                <label for="input-confirm-pw">Confirm Password</label>
                <div class="input-wrap">
                    <input type="password" id="input-confirm-pw" placeholder="Repeat new password">
                    <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    <button type="button" class="eye-toggle" onclick="togglePwd('input-confirm-pw', this)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="button" class="btn-action" id="btn-reset-pw" onclick="resetPassword()">
                <span class="btn-text">Reset Password</span>
                <span class="btn-spinner" style="display:none"></span>
            </button>

        </div>

        <!-- ════════════════════════════════════════════════════════
             Step 4 — Done
        ════════════════════════════════════════════════════════ -->
        <div class="step" id="step-done">

            <div class="success-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
            </div>

            <div class="title" style="text-align:center">
                <h1>Password reset!</h1>
                <p>Your password has been updated. You can now sign in.</p>
            </div>

            <a href="<?= $basePath ?>/login" class="btn-action btn-goto-login" style="margin-top:20px">
                Go to Login
            </a>

        </div>

    </div>
</main>

<script>
    const BASE = '<?= $basePath ?>';
    let adminEmail = '';

    // ── Step 1: Send OTP ──────────────────────────────────────────────
    async function sendOTP() {
        const btn   = document.getElementById('btn-send-otp');
        const email = document.getElementById('input-email').value.trim();
        hideError('email-error');
        clearFieldErr('email-field-error');

        if (!email) { fieldErr('email-field-error', 'Email is required'); return; }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { fieldErr('email-field-error', 'Enter a valid email'); return; }

        setLoading(btn, true);
        try {
            const res  = await fetch(BASE + '/api/admin/forgot-password', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email }),
            });
            const json = await res.json();
            if (!json.success) throw new Error(json.message);

            adminEmail = email;
            document.getElementById('otp-subtitle').textContent =
                `Code sent to ${email} · expires in 10 min`;
            goStep('otp');
            setTimeout(() => document.querySelectorAll('.otp-box')[0].focus(), 150);
        } catch (err) {
            showError('email-error', err.message);
        } finally {
            setLoading(btn, false);
        }
    }

    // ── OTP boxes ─────────────────────────────────────────────────────
    document.querySelectorAll('.otp-box').forEach((box, i, boxes) => {
        box.addEventListener('input', () => {
            box.value = box.value.replace(/\D/g, '').slice(-1);
            box.classList.toggle('filled', !!box.value);
            if (box.value && i < boxes.length - 1) boxes[i + 1].focus();
        });
        box.addEventListener('keydown', e => {
            if (e.key === 'Backspace' && !box.value && i > 0) {
                boxes[i - 1].focus();
                boxes[i - 1].classList.remove('filled');
            }
        });
        box.addEventListener('paste', e => {
            e.preventDefault();
            const digits = (e.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, 6);
            [...digits].forEach((d, j) => {
                if (boxes[j]) { boxes[j].value = d; boxes[j].classList.add('filled'); }
            });
            boxes[Math.min(digits.length, 5)]?.focus();
        });
    });

    // ── Step 2: Verify OTP (just advance — real verify on server) ─────
    function verifyOTP() {
        const otp = [...document.querySelectorAll('.otp-box')].map(b => b.value).join('');
        clearFieldErr('otp-field-error');
        if (otp.length < 6) { fieldErr('otp-field-error', 'Enter the full 6-digit code'); return; }
        goStep('password');
        document.getElementById('input-new-pw').focus();
    }

    async function resendOTP() {
        const btn = document.getElementById('btn-resend');
        btn.disabled = true; btn.textContent = 'Sending…';
        try {
            await fetch(BASE + '/api/admin/forgot-password', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: adminEmail }),
            });
            btn.textContent = 'Sent!';
            document.querySelectorAll('.otp-box').forEach(b => { b.value = ''; b.classList.remove('filled'); });
            document.querySelectorAll('.otp-box')[0].focus();
        } catch { btn.textContent = 'Failed'; }
        setTimeout(() => { btn.disabled = false; btn.textContent = 'Resend code'; }, 30000);
    }

    // ── Step 3: Reset password ────────────────────────────────────────
    async function resetPassword() {
        const btn     = document.getElementById('btn-reset-pw');
        const newPw   = document.getElementById('input-new-pw').value;
        const confirm = document.getElementById('input-confirm-pw').value;
        const otp     = [...document.querySelectorAll('.otp-box')].map(b => b.value).join('');
        hideError('pw-error');
        clearFieldErr('new-pw-field-error');

        if (newPw.length < 8) { fieldErr('new-pw-field-error', 'At least 8 characters'); return; }
        if (newPw !== confirm) { fieldErr('new-pw-field-error', 'Passwords do not match'); return; }

        setLoading(btn, true);
        try {
            const res  = await fetch(BASE + '/api/admin/reset-password', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: adminEmail, otp, new_password: newPw }),
            });
            const json = await res.json();
            if (!json.success) throw new Error(json.message);
            goStep('done');
        } catch (err) {
            showError('pw-error', err.message);
            if (err.message.toLowerCase().includes('otp') || err.message.toLowerCase().includes('expired')) {
                setTimeout(() => goStep('otp'), 1800);
            }
        } finally {
            setLoading(btn, false);
        }
    }

    // ── Strength ──────────────────────────────────────────────────────
    function checkStrength(val) {
        const score = [val.length >= 8, /[A-Z]/.test(val), /[0-9]/.test(val), /[^A-Za-z0-9]/.test(val)]
            .filter(Boolean).length;
        const bar = document.getElementById('pw-bar');
        bar.style.width      = `${score * 25}%`;
        bar.style.background = ['','#f87171','#fb923c','#facc15','#7df9ff'][score];
    }

    // ── Helpers ───────────────────────────────────────────────────────
    function goStep(name) {
        document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
        document.getElementById(`step-${name}`).classList.add('active');
    }

    function showError(id, msg) {
        const el = document.getElementById(id);
        document.getElementById(id + '-text').textContent = msg;
        el.style.display = 'flex';
    }

    function hideError(id) {
        document.getElementById(id).style.display = 'none';
    }

    function fieldErr(id, msg) {
        const el = document.getElementById(id);
        el.textContent = msg;
        el.classList.add('visible');
    }

    function clearFieldErr(id) {
        const el = document.getElementById(id);
        el.textContent = '';
        el.classList.remove('visible');
    }

    function setLoading(btn, state) {
        btn.disabled = state;
        btn.querySelector('.btn-text').style.display    = state ? 'none'         : '';
        btn.querySelector('.btn-spinner').style.display = state ? 'inline-block' : 'none';
    }

    function togglePwd(inputId, btn) {
        const input = document.getElementById(inputId);
        const show  = input.type === 'password';
        input.type  = show ? 'text' : 'password';
        btn.style.color = show ? 'rgba(125,249,255,.7)' : 'rgba(255,255,255,.3)';
    }

    // Enter shortcuts
    document.getElementById('input-email').addEventListener('keydown',      e => { if (e.key === 'Enter') sendOTP(); });
    document.getElementById('input-confirm-pw').addEventListener('keydown', e => { if (e.key === 'Enter') resetPassword(); });
</script>
</body>
</html>