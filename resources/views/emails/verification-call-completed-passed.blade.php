<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verification Call Passed - IQRAQUEST</title>
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
        .success-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
        }
        .success-icon::before {
            content: "🎉";
            font-size: 40px;
            color: white;
            font-weight: bold;
        }
        .info-card {
            background: linear-gradient(135deg, #f0fdfa 0%, #ecfdf5 100%);
            border: 1px solid #a7f3d0;
            border-radius: 12px;
            padding: 24px;
            margin: 24px 0;
        }
        .info-title {
            font-size: 18px;
            font-weight: 600;
            color: #065f46;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
        }
        .info-title::before {
            content: "✅";
            margin-right: 8px;
            font-size: 20px;
        }
        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #d1fae5;
        }
        .info-item:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 500;
            color: #047857;
        }
        .info-value {
            font-weight: 600;
            color: #065f46;
        }
        .feedback-card {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border: 1px solid #93c5fd;
            border-radius: 12px;
            padding: 24px;
            margin: 24px 0;
        }
        .feedback-title {
            font-size: 18px;
            font-weight: 600;
            color: #1e40af;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
        }
        .feedback-title::before {
            content: "💬";
            margin-right: 8px;
            font-size: 20px;
        }
        .feedback-content {
            color: #1e40af;
            line-height: 1.6;
            background: white;
            padding: 16px;
            border-radius: 8px;
            border-left: 4px solid #3b82f6;
        }
        .next-steps-section {
            background: #f8fafc;
            border-radius: 12px;
            padding: 24px;
            margin: 24px 0;
        }
        .next-steps-title {
            font-size: 20px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 20px;
            text-align: center;
        }
        .next-step-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 16px;
            padding: 16px;
            background: white;
            border-radius: 8px;
            border-left: 4px solid #14B8A6;
        }
        .next-step-number {
            background: #14B8A6;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
            margin-right: 12px;
            flex-shrink: 0;
        }
        .next-step-content {
            flex: 1;
        }
        .next-step-title {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 4px;
        }
        .next-step-description {
            color: #64748b;
            font-size: 14px;
        }
        .timeline-card {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 1px solid #fbbf24;
            border-radius: 12px;
            padding: 24px;
            margin: 24px 0;
        }
        .timeline-title {
            font-size: 18px;
            font-weight: 600;
            color: #92400e;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
        }
        .timeline-title::before {
            content: "⏰";
            margin-right: 8px;
            font-size: 20px;
        }
        .timeline-text {
            color: #92400e;
            line-height: 1.6;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="{{ config('app.url') }}" class="logo">IQRAQUEST</a>
        </div>

        <div class="content-card">
            <div class="text-center">
                <div class="success-icon"></div>
                <div class="greeting">Congratulations {{ $notifiable->name }}!</div>
                <div class="main-message">
                    Excellent news! You've <strong>successfully passed</strong> your verification call! 🎉
                </div>
            </div>
            
            <div class="info-card">
                <div class="info-title">Verification Results</div>
                <div class="info-item">
                    <span class="info-label">Status:</span>
                    <span class="info-value" style="color: #10B981; font-weight: bold;">PASSED</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Completed:</span>
                    <span class="info-value">{{ $completedTime }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Duration:</span>
                    <span class="info-value">Video verification phase complete</span>
                </div>
            </div>

            @if($notes)
            <div class="feedback-card">
                <div class="feedback-title">Feedback from our team</div>
                <div class="feedback-content">
                    {{ $notes }}
                </div>
            </div>
            @endif

            <div class="next-steps-section">
                <div class="next-steps-title">What Happens Next?</div>
                
                <div class="next-step-item">
                    <div class="next-step-number">1</div>
                    <div class="next-step-content">
                        <div class="next-step-title">Document review (24-48 hours)</div>
                        <div class="next-step-description">Our team will review your submitted documents</div>
                    </div>
                </div>
                
                <div class="next-step-item">
                    <div class="next-step-number">2</div>
                    <div class="next-step-content">
                        <div class="next-step-title">Final approval notification</div>
                        <div class="next-step-description">You'll receive confirmation when everything is approved</div>
                    </div>
                </div>
                
                <div class="next-step-item">
                    <div class="next-step-number">3</div>
                    <div class="next-step-content">
                        <div class="next-step-title">Access to teacher dashboard</div>
                        <div class="next-step-description">Start creating courses and managing your teaching profile</div>
                    </div>
                </div>
                
                <div class="next-step-item">
                    <div class="next-step-number">4</div>
                    <div class="next-step-content">
                        <div class="next-step-title">Start creating courses and teaching!</div>
                        <div class="next-step-description">Begin your journey as a verified IQRAQUEST teacher</div>
                    </div>
                </div>
            </div>

            <div class="timeline-card">
                <div class="timeline-title">You're Almost There!</div>
                <div class="timeline-text">
                    Our document verification team will now review your submitted documents. This typically takes 24-48 hours during business days.
                </div>
            </div>

            <div class="tips-section">
                <div class="tips-title">Pro tip: Use this time to</div>
                <div class="tip-item">
                    <span class="tip-icon">📚</span>
                    <span class="tip-text">Plan your first course outline</span>
                </div>
                <div class="tip-item">
                    <span class="tip-icon">📝</span>
                    <span class="tip-text">Prepare your teaching materials</span>
                </div>
                <div class="tip-item">
                    <span class="tip-icon">📅</span>
                    <span class="tip-text">Think about your teaching schedule</span>
                </div>
                <div class="tip-item">
                    <span class="tip-icon">🎯</span>
                    <span class="tip-text">Get excited about inspiring students!</span>
                </div>
            </div>

            <div class="text-center mb-4">
                <strong>Welcome to the IQRAQUEST teaching family!</strong>
            </div>
        </div>

        <div class="footer">
            <div class="footer-text">
                Warm regards from the IQRAQUEST Verification Team
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