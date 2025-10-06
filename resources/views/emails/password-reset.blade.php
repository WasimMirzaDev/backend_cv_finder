<!DOCTYPE html>
<html>
<head>
    <title>Password Reset Request</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #777;
        }
    </style>
</head>
<body>
    <h2>Password Reset Request</h2>
    <p>Hello,</p>
    <p>We received a request to reset your password. Click the button below to set a new password:</p>
    
    <p>
        <a href="{{ $resetUrl }}" class="button">Reset Password</a>
    </p>
    
    <p>If you didn't request a password reset, you can safely ignore this email. This link will expire in 24 hours.</p>
    
    <p>If the button above doesn't work, copy and paste this link into your browser:</p>
    <p>{{ $resetUrl }}</p>
    
    <div class="footer">
        <p>Thank you,<br>{{ config('app.name') }}</p>
    </div>
</body>
</html>
