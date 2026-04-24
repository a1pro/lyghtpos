<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Password Reset OTP - {{ $appName }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 10px;
        }

        .header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        .content {
            padding: 20px 0;
        }

        .otp-code {
            font-size: 32px;
            font-weight: bold;
            text-align: center;
            padding: 20px;
            background: #f5f5f5;
            border-radius: 8px;
            letter-spacing: 5px;
            margin: 20px 0;
        }

        .validity {
            color: #666;
            font-size: 14px;
            text-align: center;
        }

        .footer {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #f0f0f0;
            font-size: 12px;
            color: #999;
        }

        .warning {
            background-color: #fff3e0;
            border-left: 4px solid #ff9800;
            padding: 12px;
            margin: 20px 0;
            font-size: 14px;
        }

        .reset-button {
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: center;
            font-weight: bold;
        }

        .button-container {
            text-align: center;
            margin: 30px 0;
        }

    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Password Reset Request</h2>
        </div>

        <div class="content">
            <p>Dear {{ $user->first_name }} {{ $user->last_name }},</p>

            <p>We received a request to reset the password for your account associated with <strong>{{ $user->email }}</strong>.</p>

            <p>Please use the following One-Time Password (OTP) to reset your password:</p>

            <div class="otp-code">
                {{ $otp }}
            </div>

            <p>Best regards,<br>
                <strong>The {{ $appName }} Team</strong></p>
        </div>

        <div class="footer">
            <p>&copy; {{ $currentYear }} {{ $appName }}. All rights reserved.</p>
            <p>{{ $appUrl }}</p>
            <p><small>If you didn't request this password reset, please ignore this email.</small></p>
        </div>
    </div>
</body>
</html>
