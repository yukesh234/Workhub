<?php

class Email {
    
    public static function sendVerificationEmail(string $email, string $otp): bool {
        $subject = "Verify Your WorkHub Account";
        
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #4CAF50; color: white; padding: 20px; text-align: center; }
                .content { background-color: #f9f9f9; padding: 30px; border-radius: 5px; margin-top: 20px; }
                .otp { font-size: 32px; font-weight: bold; color: #4CAF50; text-align: center; letter-spacing: 5px; padding: 20px; background-color: white; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Welcome to WorkHub!</h1>
                </div>
                <div class='content'>
                    <p>Thank you for registering with WorkHub.</p>
                    <p>To complete your registration, please use the following One-Time Password (OTP):</p>
                    <div class='otp'>{$otp}</div>
                    <p><strong>This OTP will expire in 10 minutes.</strong></p>
                    <p>If you didn't request this verification, please ignore this email.</p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " WorkHub. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        return sendMail($email, $subject, $message);
    }
}