<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Teacher Verification Approved - IQRAQUEST</title>
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
            content: "✓";
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
            content: "🎉";
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
        .benefits-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin: 24px 0;
        }
        .benefit-item {
            background: white;
            padding: 16px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            text-align: center;
        }
        .benefit-icon {
            font-size: 24px;
            margin-bottom: 8px;
        }
        .benefit-title {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 4px;
        }
        .benefit-description {
            font-size: 14px;
            color: #64748b;
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
                    Your teacher verification has been <strong>APPROVED</strong>! 🎉<br>
                    Welcome to the IQRAQUEST teaching community!
                </div>
            </div>
            
            <div class="info-card">
                <div class="info-title">Verification Complete</div>
                <div class="info-item">
                    <span class="info-label">Status:</span>
                    <span class="info-value" style="color: #10B981; font-weight: bold;">FULLY APPROVED</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Approved Date:</span>
                    <span class="info-value">{{ $approvedDate }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Profile Status:</span>
                    <span class="info-value">Active Teacher</span>
                </div>
            </div>

            <div class="steps-section">
                <div class="steps-title">Your Teaching Journey Starts Now!</div>
                
                <div class="step-item">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <div class="step-title">Complete Your Profile</div>
                        <div class="step-description">Add your bio, teaching specialties, and professional photo</div>
                    </div>
                </div>
                
                <div class="step-item">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <div class="step-title">Setup Payment Info</div>
                        <div class="step-description">Configure how you'll receive your earnings</div>
                    </div>
                </div>
                
                <div class="step-item">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <div class="step-title">Set Your Availability</div>
                        <div class="step-description">Let students know when you're available to teach</div>
                    </div>
                </div>
                
                <div class="step-item">
                    <div class="step-number">4</div>
                    <div class="step-content">
                        <div class="step-title">Create Your First Course</div>
                        <div class="step-description">Share your expertise with structured learning content</div>
                    </div>
                </div>
            </div>

            <div class="benefits-grid">
                <div class="benefit-item">
                    <div class="benefit-icon">💰</div>
                    <div class="benefit-title">Earn $15-50+ per hour</div>
                    <div class="benefit-description">Flexible tutoring rates</div>
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon">👥</div>
                    <div class="benefit-title">10,000+ Students</div>
                    <div class="benefit-description">Access to active learners</div>
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon">📚</div>
                    <div class="benefit-title">Course Sales</div>
                    <div class="benefit-description">Passive income opportunities</div>
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon">⏰</div>
                    <div class="benefit-title">Flexible Schedule</div>
                    <div class="benefit-description">Teach when it works for you</div>
                </div>
            </div>

            <div class="text-center">
                <a href="{{ config('app.url') }}/teacher/dashboard" class="cta-button">
                    Start Your Teaching Journey
                </a>
            </div>

            <div class="text-center mb-4">
                <strong>Fun Fact:</strong> You're joining a community of <strong>500+ verified teachers</strong> who are making a real impact on students' lives worldwide!
            </div>
        </div>

        <div class="footer">
            <div class="footer-text">
                Our teacher success team is here to help you succeed!
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