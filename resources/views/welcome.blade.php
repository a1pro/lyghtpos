<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lyght POS | Coming Soon</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            height: 100vh;
            background: linear-gradient(135deg, #0f172a, #020617);
            color: #ffffff;
            display: flex;
            flex-direction: column;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 40px;
        }

        .logo {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .login-btn {
            padding: 10px 22px;
            border-radius: 6px;
            background: #22c55e;
            color: #022c22;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .login-btn:hover {
            background: #16a34a;
            color: #ffffff;
        }

        /* Main Content */
        .content {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 20px;
        }

        .content h1 {
            font-size: 48px;
            margin-bottom: 16px;
        }

        .content p {
            font-size: 18px;
            max-width: 600px;
            opacity: 0.85;
            line-height: 1.6;
        }

        .badge {
            margin-top: 30px;
            padding: 10px 20px;
            border-radius: 50px;
            background: rgba(255, 255, 255, 0.1);
            font-size: 14px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 15px;
            font-size: 14px;
            opacity: 0.6;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .content h1 {
                font-size: 36px;
            }

            .header {
                padding: 15px 20px;
            }
        }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="header">
        <div class="logo">Lyght POS</div>
    
        @auth
            <a href="{{ url('/admin') }}" class="login-btn">Admin</a>
        @else
            <a href="{{ url('login') }}" class="login-btn">Login</a>
        @endauth
    </div>
    

    <!-- Main Content -->
    <div class="content">
        <h1>Coming Soon 🚀</h1>
        <p>
            Lyght POS is almost here. A smarter, faster, and more reliable
            point-of-sale experience designed to power your business.
        </p>
        <div class="badge">Launching Soon</div>
    </div>

    <!-- Footer -->
    <div class="footer">
        © {{ date('Y') ?? '2026' }} Lyght POS. All rights reserved.
    </div>

</body>
</html>
