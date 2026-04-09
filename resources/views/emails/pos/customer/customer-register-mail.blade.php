<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Welcome to {{ $appName }}</title>
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
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Welcome to {{ $appName }}!</h2>
        </div>
        
        <div class="content">
            <p>Dear {{ $user->first_name }} {{ $user->last_name }},</p>
            
            <p>Thank you for registering with {{ $appName }}. Please use the following OTP to verify your email address and complete your registration:</p>
            
            <div class="otp-code">
                {{ $otp }}
            </div>
            
            <p class="validity">
                <strong>Note:</strong> This OTP is valid for 15 minutes and will expire on {{ \Carbon\Carbon::parse($validity)->format('F j, Y g:i A') }}.
            </p>
            
            <p>If you didn't create an account with {{ $appName }}, please ignore this email.</p>
            
            <p>Best regards,<br>
            The {{ $appName }} Team</p>
        </div>
        
        <div class="footer">
            <p>&copy; {{ $currentYear }} {{ $appName }}. All rights reserved.</p>
            <p>{{ $appUrl }}</p>
        </div>
    </div>
</body>
</html>