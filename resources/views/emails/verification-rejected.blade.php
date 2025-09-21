<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verification Application Update - IQRAQUEST</title>
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
        .reason-card {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border: 1px solid #fca5a5;
            border-radius: 12px;
            padding: 24px;
            margin: 24px 0;
        }
        .reason-title {
            font-size: 18px;
            font-weight: 600;
            color: #991b1b;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
        }
        .reason-title::before {
            content: "🔍";
            margin-right: 8px;
            font-size: 20px;
        }
        .reason-content {
            color: #7f1d1d;
            line-height: 1.6;
            background: white;
            padding: 16px;
            border-radius: 8px;
            border-left: 4px solid #ef4444;
        }
        .good-news-card {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            border: 1px solid #86efac;
            border-radius: 12px;
            padding: 24px;
            margin: 24px 0;
        }
        .good-news-title {
            font-size: 18px;
            font-weight: 600;
            color: #065f46;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
        }
        .good-news-title::before {
            content: "✅";
            margin-right: 8px;
            font-size: 20px;
        }
        .steps-section {
            background: #f8fafc;
            border-radius: 12px;
            padding: 24px;
            margin: 24px 0;
        }
        .steps-title {
            font-size: 20px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 20px;
            text-align: center;
        }
        .step-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 16px;
            padding: 16px;
            background: white;
            border-radius: 8px;
            border-left: 4px solid #14B8A6;
        }
        .step-number {
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
        .step-content {
            flex: 1;
        }
        .step-title {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 4px;
        }
        .step-description {
            color: #64748b;
            font-size: 14px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 16px;
            margin: 24px 0;
        }
        .stat-item {
            background: white;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            text-align: center;
        }
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #14B8A6;
            margin-bottom: 4px;
        }
        .stat-label {
            font-size: 14px;
            color: #64748b;
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
                    Verification Application Update - <strong>Action Required</strong><br>
                    Thank you for submitting your teacher verification application to IQRAQUEST.
                </div>
            </div>
            
            <div class="info-card">
                <div class="info-title">Application Review Results</div>
                <div class="info-item">
                    <span class="info-label">Review Date:</span>
                    <span class="info-value">{{ $reviewDate }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Status:</span>
                    <span class="info-value" style="color: #D97706; font-weight: bold;">Requires Additional Information</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Application ID:</span>
                    <span class="info-value">#{{ $verificationRequest->id }}</span>
                </div>
            </div>

            <div class="reason-card">
                <div class="reason-title">What We Found</div>
                <div class="reason-content">
                    {{ $rejectionReason }}
                </div>
            </div>

            <div class="good-news-card">
                <div class="good-news-title">Good News - This is Fixable!</div>
                <div style="color: #065f46; line-height: 1.6;">
                    Don't worry! Most applications need some adjustments. This is completely normal and doesn't affect your ability to become a teacher with us.
                </div>
            </div>

            <div class="steps-section">
                <div class="steps-title">Your Action Plan</div>
                
                <div class="step-item">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <div class="step-title">Review & Fix</div>
                        <div class="step-description">Carefully read the feedback above and gather any missing documents or information</div>
                    </div>
                </div>
                
                <div class="step-item">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <div class="step-title">Get Help (Optional)</div>
                        <div class="step-description">Contact our support team for clarification or schedule a guidance call</div>
                    </div>
                </div>
                
                <div class="step-item">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <div class="step-title">Resubmit When Ready</div>
                        <div class="step-description">Update your application with improvements and submit for review again</div>
                    </div>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number">85%</div>
                    <div class="stat-label">Resubmission Success Rate</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">1-2</div>
                    <div class="stat-label">Days to Approval</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">< 4hrs</div>
                    <div class="stat-label">Support Response Time</div>
                </div>
            </div>

            <div class="text-center">
                <a href="{{ config('app.url') }}/teacher/verification" class="cta-button">
                    Update Application
                </a>
            </div>

            <div class="text-center mb-4">
                <strong>Remember:</strong> This is just a temporary setback. With the right adjustments, you'll be on your way to becoming a verified IQRAQUEST teacher!
            </div>
        </div>

        <div class="footer">
            <div class="footer-text">
                Our goal is to help you become a successful IQRAQUEST teacher. Every piece of feedback is designed to help you meet our quality standards and serve students better.
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