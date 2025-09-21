<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verification Call Results - IQRAQUEST</title>
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
        .info-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            box-shadow: 0 8px 25px rgba(245, 158, 11, 0.3);
        }
        .info-icon::before {
            content: "ℹ";
            font-size: 40px;
            color: white;
            font-weight: bold;
        }
        .info-card {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 1px solid #fbbf24;
            border-radius: 12px;
            padding: 24px;
            margin: 24px 0;
        }
        .info-title {
            font-size: 18px;
            font-weight: 600;
            color: #92400e;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
        }
        .info-title::before {
            content: "📋";
            margin-right: 8px;
            font-size: 20px;
        }
        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #fde68a;
        }
        .info-item:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 500;
            color: #92400e;
        }
        .info-value {
            font-weight: 600;
            color: #92400e;
        }
        .feedback-card {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border: 1px solid #fca5a5;
            border-radius: 12px;
            padding: 24px;
            margin: 24px 0;
        }
        .feedback-title {
            font-size: 18px;
            font-weight: 600;
            color: #991b1b;
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
            color: #7f1d1d;
            line-height: 1.6;
            background: white;
            padding: 16px;
            border-radius: 8px;
            border-left: 4px solid #ef4444;
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
        .encouragement-card {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border: 1px solid #86efac;
            border-radius: 12px;
            padding: 24px;
            margin: 24px 0;
        }
        .encouragement-title {
            font-size: 18px;
            font-weight: 600;
            color: #166534;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
        }
        .encouragement-title::before {
            content: "💪";
            margin-right: 8px;
            font-size: 20px;
        }
        .encouragement-item {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            padding: 8px 0;
        }
        .encouragement-item:last-child {
            margin-bottom: 0;
        }
        .encouragement-icon {
            margin-right: 12px;
            font-size: 16px;
        }
        .encouragement-text {
            color: #166534;
            font-weight: 500;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #14B8A6 0%, #0D9488 100%);
            color: white;
            padding: 16px 32px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            text-align: center;
            margin: 24px 0;
            box-shadow: 0 4px 14px rgba(20, 184, 166, 0.3);
            transition: all 0.3s ease;
        }
        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(20, 184, 166, 0.4);
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
                <div class="info-icon"></div>
                <div class="greeting">Hello {{ $notifiable->name }}!</div>
                <div class="main-message">
                    Verification Call Results - <strong>Next Steps Available</strong><br>
                    Thank you for completing your verification call with our team.
                </div>
            </div>
            
            <div class="info-card">
                <div class="info-title">Verification Results</div>
                <div class="info-item">
                    <span class="info-label">Status:</span>
                    <span class="info-value" style="color: #D97706; font-weight: bold;">Needs Improvement</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Completed:</span>
                    <span class="info-value">{{ $completedTime }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Review Status:</span>
                    <span class="info-value">Additional requirements identified</span>
                </div>
            </div>

            @if($notes)
            <div class="feedback-card">
                <div class="feedback-title">Specific Feedback</div>
                <div class="feedback-content">
                    {{ $notes }}
                </div>
            </div>
            @endif

            <div class="next-steps-section">
                <div class="next-steps-title">Your Next Steps</div>
                <div style="color: #64748b; margin-bottom: 20px; text-align: center;">
                    Don't worry! This is a common part of the verification process.
                </div>
                
                <div class="next-step-item">
                    <div class="next-step-number">1</div>
                    <div class="next-step-content">
                        <div class="next-step-title">Option 1: Schedule Another Call</div>
                        <div class="next-step-description">Address the feedback points above, prepare any additional documents if needed, book a new slot when you're ready</div>
                    </div>
                </div>
                
                <div class="next-step-item">
                    <div class="next-step-number">2</div>
                    <div class="next-step-content">
                        <div class="next-step-title">Option 2: Contact Support</div>
                        <div class="next-step-description">Get clarification on specific requirements, ask questions about the feedback, receive guidance for your next attempt</div>
                    </div>
                </div>
            </div>

            <div class="encouragement-card">
                <div class="encouragement-title">Remember</div>
                <div class="encouragement-item">
                    <span class="encouragement-icon">👥</span>
                    <span class="encouragement-text">Many successful teachers needed multiple attempts</span>
                </div>
                <div class="encouragement-item">
                    <span class="encouragement-icon">🎯</span>
                    <span class="encouragement-text">Each call helps us ensure quality education</span>
                </div>
                <div class="encouragement-item">
                    <span class="encouragement-icon">🤝</span>
                    <span class="encouragement-text">We're here to help you succeed</span>
                </div>
                <div class="encouragement-item">
                    <span class="encouragement-icon">⭐</span>
                    <span class="encouragement-text">Your teaching goals are still achievable!</span>
                </div>
            </div>

            <div class="text-center">
                <a href="{{ config('app.url') }}/teacher/verification" class="cta-button">
                    Schedule Next Call
                </a>
            </div>

            <div class="text-center mb-4">
                <strong>Ready to try again?</strong> Take time to address the feedback, then schedule your next verification call.
            </div>
        </div>

        <div class="footer">
            <div class="footer-text">
                Keep going - you've got this! Our support team is here to guide you through the process.
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