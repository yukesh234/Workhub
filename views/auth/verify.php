<?php
// Start session at the very top
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug: Check what's in session
// var_dump($_SESSION); // Uncomment to debug
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - WorkHub</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%);
            color: #ffffff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: rgba(26, 26, 46, 0.95);
            backdrop-filter: blur(15px);
            border: 2px solid rgba(106, 0, 49, 0.5);
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 450px;
            box-shadow: 
                0 20px 60px rgba(0, 0, 0, 0.5),
                0 0 80px rgba(106, 0, 49, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        h1 {
            font-size: 28px;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #ffffff 0%, #a0a0b8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .subtitle {
            color: #a0a0b8;
            font-size: 14px;
            line-height: 1.6;
        }

        .email-display {
            background: rgba(106, 0, 49, 0.2);
            border: 1px solid rgba(106, 0, 49, 0.4);
            padding: 12px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
            font-weight: 600;
            color: #ffffff;
            font-size: 14px;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #6ee7b7;
        }

        .alert::before {
            content: "⚠️";
            font-size: 18px;
        }

        .alert-success::before {
            content: "✅";
        }

        .form-group {
            margin-bottom: 24px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #e5e7eb;
            font-weight: 500;
            font-size: 14px;
        }

        .otp-input {
            width: 100%;
            padding: 16px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: #ffffff;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 8px;
            text-align: center;
            transition: all 0.3s;
            font-family: 'Courier New', monospace;
        }

        .otp-input:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.08);
            border-color: #6A0031;
            box-shadow: 0 0 0 3px rgba(106, 0, 49, 0.2);
        }

        .otp-input::placeholder {
            color: rgba(255, 255, 255, 0.3);
            letter-spacing: 8px;
        }

        .hint-text {
            font-size: 12px;
            color: #a0a0b8;
            margin-top: 6px;
        }

        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #6A0031 0%, #8a1144 100%);
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(106, 0, 49, 0.4);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 24px 0;
            color: #6b7280;
            font-size: 14px;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .divider span {
            padding: 0 16px;
        }

        .resend-section {
            text-align: center;
        }

        .resend-text {
            color: #a0a0b8;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .resend-link {
            color: #6A0031;
            background: rgba(106, 0, 49, 0.1);
            border: 1px solid rgba(106, 0, 49, 0.3);
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            transition: all 0.3s;
            cursor: pointer;
        }

        .resend-link:hover {
            background: rgba(106, 0, 49, 0.2);
            border-color: rgba(106, 0, 49, 0.5);
            transform: translateY(-1px);
        }

        .resend-link:active {
            transform: translateY(0);
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }

        .back-link a {
            color: #a0a0b8;
            text-decoration: none;
            transition: color 0.3s;
        }

        .back-link a:hover {
            color: #ffffff;
        }

        /* Loading animation */
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .spinner {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
            margin-left: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Verify Your Email</h1>
            <p class="subtitle">We've sent a 6-digit verification code to your email address</p>
        </div>

        <div class="email-display">
            📧 <?= htmlspecialchars($_GET['email'] ?? '') ?>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div id="message"></div>

        <form method="POST" action="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/verify" id="verify-form">
            <input type="hidden" name="email" value="<?= htmlspecialchars($_GET['email'] ?? '') ?>">
            
            <div class="form-group">
                <label for="otp">Enter Verification Code</label>
                <input 
                    type="text" 
                    id="otp" 
                    name="otp" 
                    class="otp-input"
                    required 
                    maxlength="6"
                    pattern="[0-9]{6}"
                    placeholder="000000"
                    autocomplete="off"
                    inputmode="numeric"
                    autofocus
                >
                <p class="hint-text">⏱️ Code expires in 10 minutes</p>
            </div>

            <button type="submit" class="btn" id="verify-btn">
                Verify Email
            </button>
        </form>

        <div class="divider">
            <span>Didn't receive the code?</span>
        </div>

        <div class="resend-section">
            <p class="resend-text">Check your spam folder or request a new code</p>
            <a href="#" class="resend-link" id="resend-otp">
                🔄 Resend Code
            </a>
        </div>

        <div class="back-link">
            <a href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/login">← Back to Login</a>
        </div>
    </div>

    <script>
        const otpInput = document.getElementById('otp');
        const verifyForm = document.getElementById('verify-form');
        const verifyBtn = document.getElementById('verify-btn');
        const resendBtn = document.getElementById('resend-otp');
        const messageDiv = document.getElementById('message');

        // Auto-format OTP input (numbers only)
        otpInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
            
            // Auto-submit when 6 digits entered
            if (this.value.length === 6) {
                verifyForm.submit();
                verifyBtn.disabled = true;
                verifyBtn.innerHTML = 'Verifying<span class="spinner"></span>';
            }
        });

        // Resend OTP functionality
        resendBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const email = '<?= htmlspecialchars($_GET['email'] ?? '') ?>';
            
            // Disable button
            this.style.pointerEvents = 'none';
            this.style.opacity = '0.6';
            this.innerHTML = '⏳ Sending<span class="spinner"></span>';
            
            fetch('<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/resend-otp', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'email=' + encodeURIComponent(email)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageDiv.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
                    // Clear OTP input
                    otpInput.value = '';
                    otpInput.focus();
                } else {
                    messageDiv.innerHTML = '<div class="alert alert-error">' + data.message + '</div>';
                }
                
                // Re-enable button after 30 seconds
                setTimeout(() => {
                    resendBtn.style.pointerEvents = 'auto';
                    resendBtn.style.opacity = '1';
                    resendBtn.innerHTML = '🔄 Resend Code';
                    
                    // Clear message after 5 seconds
                    setTimeout(() => {
                        messageDiv.innerHTML = '';
                    }, 5000);
                }, 30000);
            })
            .catch(error => {
                messageDiv.innerHTML = '<div class="alert alert-error">Failed to resend code. Please try again.</div>';
                resendBtn.style.pointerEvents = 'auto';
                resendBtn.style.opacity = '1';
                resendBtn.innerHTML = '🔄 Resend Code';
            });
        });

        // Paste support (in case user copies OTP)
        otpInput.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedData = e.clipboardData.getData('text').replace(/[^0-9]/g, '').substring(0, 6);
            this.value = pastedData;
            
            if (pastedData.length === 6) {
                verifyForm.submit();
                verifyBtn.disabled = true;
                verifyBtn.innerHTML = 'Verifying<span class="spinner"></span>';
            }
        });

        // Prevent form double submission
        verifyForm.addEventListener('submit', function() {
            verifyBtn.disabled = true;
            verifyBtn.innerHTML = 'Verifying<span class="spinner"></span>';
        });
    </script>
</body>
</html>