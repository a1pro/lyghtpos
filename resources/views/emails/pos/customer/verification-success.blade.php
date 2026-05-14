<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Email Verified Successfully - {{ $appName }}</title>
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

        .header h2 {
            color: #4CAF50;
        }

        .content {
            padding: 20px 0;
        }

        .success-icon {
            font-size: 64px;
            text-align: center;
            margin: 20px 0;
        }

        .details {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #4CAF50;
        }

        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            text-align: center;
        }

        .footer {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #f0f0f0;
            font-size: 12px;
            color: #999;
        }

    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Email Verified Successfully! 🎉</h2>
        </div>

        <div class="content">
            <p>Dear {{ $user->first_name }} {{ $user->last_name }},</p>

            <p>Congratulations! Your email address has been successfully verified.</p>

            <div class="details">
                <p><strong>Verification Details:</strong></p>
                <p>📧 Email: {{ $user->email }}</p>
            </div>

            <p>You can now enjoy all the features and services offered by {{ $appName }}.</p>

            <center>
                <a href="{{ $appUrl }}" class="button">Go to Dashboard</a>
            </center>

            <p style="margin-top: 30px;">Need help? Contact our support team anytime.</p>

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