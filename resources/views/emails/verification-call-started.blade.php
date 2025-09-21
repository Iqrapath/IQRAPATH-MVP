<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verification Call Started - IQRAQUEST</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #374151;
            background-color: #f9fafb;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #14B8A6 0%, #0D9488 100%);
            color: white;
            padding: 32px 24px;
            text-align: center;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            text-decoration: none;
            color: white;
        }
        .content-card {
            padding: 32px 24px;
        }
        .greeting {
            font-size: 24px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 16px;
        }
        .main-message {
            font-size: 18px;
            color: #374151;
            margin-bottom: 24px;
            line-height: 1.7;
        }
        .urgent-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.3);
            animation: pulse 2s infinite;
        }
        .urgent-icon::before {
            content: "📹";
            font-size: 40px;
            color: white;
            font-weight: bold;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        .urgent-card {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border: 2px solid #fca5a5;
            border-radius: 12px;
            padding: 24px;
            margin: 24px 0;
        }
        .urgent-title {
            font-size: 18px;
            font-weight: 600;
            color: #991b1b;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
        }
        .urgent-title::before {
            content: "🚨";
            margin-right: 8px;
            font-size: 20px;
        }
        .urgent-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #fecaca;
        }
        .urgent-item:last-child {
            border-bottom: none;
        }
        .urgent-label {
            font-weight: 500;
            color: #991b1b;
        }
        .urgent-value {
            font-weight: 600;
            color: #991b1b;
        }
        .meeting-link-card {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border: 2px solid #3b82f6;
            border-radius: 12px;
            padding: 24px;
            margin: 24px 0;
            text-align: center;
        }
        .meeting-title {
            font-size: 18px;
            font-weight: 600;
            color: #1e40af;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .meeting-title::before {
            content: "🔗";
            margin-right: 8px;
            font-size: 20px;
        }
        .meeting-link {
            display: inline-block;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            padding: 16px 32px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 18px;
            margin: 16px 0;
            box-shadow: 0 4px 14px rgba(59, 130, 246, 0.3);
            transition: all 0.3s ease;
        }
        .meeting-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
        }
        .checklist-section {
            background: #f8fafc;
            border-radius: 12px;
            padding: 24px;
            margin: 24px 0;
        }
        .checklist-title {
            font-size: 20px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 20px;
            text-align: center;
        }
        .checklist-item {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            padding: 12px 16px;
            background: white;
            border-radius: 8px;
            border-left: 4px solid #14B8A6;
        }
        .checklist-item:last-child {
            margin-bottom: 0;
        }
        .checklist-icon {
            margin-right: 12px;
            font-size: 18px;
        }
        .checklist-text {
            color: #1e293b;
            font-weight: 500;
        }
        .tips-section {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border: 1px solid #86efac;
            border-radius: 12px;
            padding: 24px;
            margin: 24px 0;
        }
        .tips-title {
            font-size: 18px;
            font-weight: 600;
            color: #166534;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
        }
        .tips-title::before {
            content: "💡";
            margin-right: 8px;
            font-size: 20px;
        }
        .tip-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 12px;
            padding: 8px 0;
        }
        .tip-item:last-child {
            margin-bottom: 0;
        }
        .tip-icon {
            margin-right: 12px;
            font-size: 16px;
            margin-top: 2px;
        }
        .tip-text {
            color: #166534;
            font-weight: 500;
        }
        .footer {
            background: #f8fafc;
            padding: 24px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        .footer-text {
            color: #64748b;
            margin-bottom: 16px;
        }
        .support-link {
            margin: 16px 0;
        }
        .support-link a {
            color: #14B8A6;
            text-decoration: none;
            font-weight: 500;
        }
        .copyright {
            font-size: 12px;
            color: #9ca3af;
            margin-top: 16px;
        }
        .text-center {
            text-align: center;
        }
        .mb-4 {
            margin-bottom: 16px;
        }
        .mt-6 {
            margin-top: 24px;
        }
        .text-red-600 {
            color: #dc2626;
        }
        .font-bold {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="{{ config('app.url') }}" class="logo">IQRAQUEST</a>
        </div>

        <div class="content-card">
            <div class="text-center">
                <div class="urgent-icon"></div>
                <div class="greeting">Hello {{ $notifiable->name }}!</div>
                <div class="main-message">
                    Your verification call is now <strong class="text-red-600">LIVE</strong>! 🎥<br>
                    <strong>Time is of the essence - Our team is ready for you now!</strong>
                </div>
            </div>
            
            <div class="urgent-card">
                <div class="urgent-title">Immediate Action Required</div>
                <div class="urgent-item">
                    <span class="urgent-label">Current Time:</span>
                    <span class="urgent-value">{{ $currentTime }}</span>
                </div>
                <div class="urgent-item">
                    <span class="urgent-label">Status:</span>
                    <span class="urgent-value" style="color: #dc2626; font-weight: bold;">Active & Waiting for You</span>
                </div>
                <div class="urgent-item">
                    <span class="urgent-label">Our Verification Team:</span>
                    <span class="urgent-value">Ready and waiting</span>
                </div>
            </div>

            @if($verificationRequest->meeting_link)
            <div class="meeting-link-card">
                <div class="meeting-title">Meeting Room</div>
                <a href="{{ $verificationRequest->meeting_link }}" class="meeting-link">
                    JOIN LIVE CALL NOW
                </a>
            </div>
            @endif

            <div class="checklist-section">
                <div class="checklist-title">Quick Checklist (30 seconds)</div>
                
                <div class="checklist-item">
                    <span class="checklist-icon">📹</span>
                    <span class="checklist-text">Camera working and good lighting?</span>
                </div>
                
                <div class="checklist-item">
                    <span class="checklist-icon">🎤</span>
                    <span class="checklist-text">Microphone clear and no background noise?</span>
                </div>
                
                <div class="checklist-item">
                    <span class="checklist-icon">📄</span>
                    <span class="checklist-text">ID and teaching documents within reach?</span>
                </div>
                
                <div class="checklist-item">
                    <span class="checklist-icon">🌐</span>
                    <span class="checklist-text">Stable internet connection?</span>
                </div>
            </div>

            <div class="tips-section">
                <div class="tips-title">Pro Tips</div>
                <div class="tip-item">
                    <span class="tip-icon">⚡</span>
                    <span class="tip-text"><strong>Join immediately</strong> - Don't keep the team waiting</span>
                </div>
                <div class="tip-item">
                    <span class="tip-icon">🗣️</span>
                    <span class="tip-text"><strong>Speak clearly</strong> and maintain eye contact</span>
                </div>
                <div class="tip-item">
                    <span class="tip-icon">📋</span>
                    <span class="tip-text"><strong>Have documents ready</strong> for quick verification</span>
                </div>
                <div class="tip-item">
                    <span class="tip-icon">😌</span>
                    <span class="tip-text"><strong>Stay calm and confident</strong> - you've got this!</span>
                </div>
            </div>

            <div class="text-center mb-4">
                <strong>Technical issues?</strong> If you can't join, contact support immediately.
            </div>

            <div class="text-center mb-4">
                <strong class="text-red-600">Your teaching journey with IQRAQUEST starts here!</strong>
            </div>
        </div>

        <div class="footer">
            <div class="footer-text">
                Best of luck with your verification call!
            </div>
            
            <div class="support-link">
                <a href="mailto:support@iqraquest.com">support@iqraquest.com</a>
            </div>

            <div class="copyright">
                © {{ date('Y') }} IQRAQUEST. All rights reserved.
            </div>
        </div>
    </div>
</body>
</html>