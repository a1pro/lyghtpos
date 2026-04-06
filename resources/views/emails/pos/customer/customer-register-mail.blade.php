<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{ $appName }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        
        .email-container {
            max-width: 600px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.5s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 48px 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .email-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
            transform: rotate(45deg);
        }
        
        .logo {
            font-size: 48px;
            font-weight: 800;
            margin-bottom: 16px;
            position: relative;
            z-index: 1;
            letter-spacing: -0.5px;
        }
        
        .logo-icon {
            font-size: 64px;
            margin-bottom: 16px;
            position: relative;
            z-index: 1;
        }
        
        h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 12px;
            position: relative;
            z-index: 1;
            letter-spacing: -0.5px;
        }
        
        .welcome-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 500;
            margin-top: 16px;
            position: relative;
            z-index: 1;
        }
        
        .email-body {
            padding: 48px 40px;
            background: #ffffff;
        }
        
        .greeting {
            font-size: 24px;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 20px;
        }
        
        .greeting-name {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        
        .message {
            color: #4a5568;
            font-size: 16px;
            line-height: 1.8;
            margin-bottom: 24px;
        }
        
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin: 32px 0;
        }
        
        .feature-card {
            background: #f7fafc;
            padding: 20px;
            border-radius: 16px;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .feature-icon {
            font-size: 32px;
            margin-bottom: 12px;
        }
        
        .feature-title {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
            font-size: 16px;
        }
        
        .feature-description {
            font-size: 14px;
            color: #718096;
            line-height: 1.5;
        }
        
        .button-container {
            text-align: center;
            margin: 40px 0 32px;
        }
        
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 14px 36px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
        }
        
        .info-box {
            background: #f0fff4;
            border-left: 4px solid #48bb78;
            padding: 20px;
            border-radius: 12px;
            margin: 32px 0;
        }
        
        .info-title {
            font-weight: 600;
            color: #22543d;
            margin-bottom: 12px;
            font-size: 16px;
        }
        
        .info-text {
            color: #276749;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .social-links {
            text-align: center;
            margin: 32px 0;
            padding-top: 24px;
            border-top: 1px solid #e2e8f0;
        }
        
        .social-icon {
            display: inline-block;
            margin: 0 10px;
            color: #a0aec0;
            font-size: 20px;
            transition: color 0.3s ease;
            text-decoration: none;
        }
        
        .social-icon:hover {
            color: #667eea;
        }
        
        .email-footer {
            background: #f7fafc;
            padding: 32px 40px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }
        
        .footer-text {
            color: #718096;
            font-size: 12px;
            line-height: 1.6;
        }
        
        .footer-links {
            margin-top: 16px;
        }
        
        .footer-links a {
            color: #667eea;
            text-decoration: none;
            font-size: 12px;
            margin: 0 12px;
        }
        
        .footer-links a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 600px) {
            .email-container {
                margin: 20px;
                border-radius: 20px;
            }
            
            .email-header {
                padding: 32px 24px;
            }
            
            .email-body {
                padding: 32px 24px;
            }
            
            h1 {
                font-size: 28px;
            }
            
            .greeting {
                font-size: 22px;
            }
            
            .feature-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header Section -->
        <div class="email-header">
            <div class="logo-icon">🎉</div>
            <div class="logo">{{ $appName }}</div>
            <h1>Welcome Aboard!</h1>
            <div class="welcome-badge">✨ Your journey begins here ✨</div>
        </div>
        
        <!-- Body Section -->
        <div class="email-body">
            <div class="greeting">
                Hello <span class="greeting-name">{{ $user->name }}</span>! 👋
            </div>
            
            <div class="message">
                Thank you for choosing <strong>{{ $appName }}</strong>. We're absolutely thrilled to have you on board! 
                Your account has been successfully created and you're now part of our growing community.
            </div>
            
            <div class="message">
                We're committed to providing you with an exceptional experience. Whether you're here to explore, 
                learn, or grow, we've got you covered with powerful features and dedicated support.
            </div>
            
            <!-- Features Section -->
            <div class="feature-grid">
                <div class="feature-card">
                    <div class="feature-icon">🚀</div>
                    <div class="feature-title">Get Started Quickly</div>
                    <div class="feature-description">Easy onboarding process to help you hit the ground running</div>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">💪</div>
                    <div class="feature-title">Powerful Features</div>
                    <div class="feature-description">Access to all tools and resources you need to succeed</div>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">🤝</div>
                    <div class="feature-title">24/7 Support</div>
                    <div class="feature-description">Our dedicated team is always here to help you</div>
                </div>
            </div>
            
            <!-- Info Box -->
            <div class="info-box">
                <div class="info-title">📌 What's Next?</div>
                <div class="info-text">
                    <strong>1.</strong> Log in to your account using your registered email address.<br>
                    <strong>2.</strong> Complete your profile to personalize your experience.<br>
                    <strong>3.</strong> Explore our features and start making the most of {{ $appName }}.<br>
                    <strong>4.</strong> Check out our knowledge base for tips and tutorials.
                </div>
            </div>
            
            <!-- CTA Button -->
            <div class="button-container">
                <a href="{{ $appUrl }}/login" class="cta-button">
                    🚀 Go to Your Dashboard
                </a>
            </div>
            
            <!-- Social Links -->
            <div class="social-links">
                <a href="#" class="social-icon">📘</a>
                <a href="#" class="social-icon">🐦</a>
                <a href="#" class="social-icon">📷</a>
                <a href="#" class="social-icon">💼</a>
            </div>
        </div>
        
        <!-- Footer Section -->
        <div class="email-footer">
            <div class="footer-text">
                © {{ $currentYear }} {{ $appName }}. All rights reserved.<br>
                Empowering your journey to success.
            </div>
            <div class="footer-links">
                <a href="{{ $appUrl }}/privacy">Privacy Policy</a>
                |
                <a href="{{ $appUrl }}/terms">Terms of Service</a>
                |
                <a href="{{ $appUrl }}/support">Support</a>
            </div>
            <div class="footer-text" style="margin-top: 16px;">
                <small>This email was sent to {{ $user->email }} because you registered with {{ $appName }}.</small>
            </div>
        </div>
    </div>
</body>
</html>