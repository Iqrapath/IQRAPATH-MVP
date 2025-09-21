<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Document Verified - IQRAQUEST</title>
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
            content: "📄";
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
        .benefits-card {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border: 1px solid #93c5fd;
            border-radius: 12px;
            padding: 24px;
            margin: 24px 0;
        }
        .benefits-title {
            font-size: 18px;
            font-weight: 600;
            color: #1e40af;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
        }
        .benefits-title::before {
            content: "🎯";
            margin-right: 8px;
            font-size: 20px;
        }
        .benefit-item {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            padding: 8px 0;
        }
        .benefit-item:last-child {
            margin-bottom: 0;
        }
        .benefit-icon {
            margin-right: 12px;
            font-size: 16px;
        }
        .benefit-text {
            color: #1e40af;
            font-weight: 500;
        }
        .progress-card {
            background: #f8fafc;
            border-radius: 12px;
            padding: 24px;
            margin: 24px 0;
        }
        .progress-title {
            font-size: 20px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 20px;
            text-align: center;
        }
        .progress-item {
            display: flex;
            align-items: center;
            margin-bottom: 16px;
            padding: 12px 16px;
            background: white;
            border-radius: 8px;
            border-left: 4px solid #14B8A6;
        }
        .progress-item.completed {
            border-left-color: #10B981;
            background: linear-gradient(135deg, #f0fdfa 0%, #ecfdf5 100%);
        }
        .progress-item.pending {
            border-left-color: #F59E0B;
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
        }
        .progress-icon {
            margin-right: 12px;
            font-size: 18px;
        }
        .progress-text {
            flex: 1;
            font-weight: 500;
            color: #1e293b;
        }
        .progress-status {
            font-size: 14px;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 4px;
        }
        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }
        .status-pending {
            background: #fef3c7;
            color: #92400e;
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
        .next-step-icon {
            margin-right: 12px;
            font-size: 18px;
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
        .progress-bar {
            background: #e5e7eb;
            border-radius: 8px;
            height: 8px;
            margin: 16px 0;
            overflow: hidden;
        }
        .progress-fill {
            background: linear-gradient(135deg, #14B8A6 0%, #0D9488 100%);
            height: 100%;
            border-radius: 8px;
            width: 75%;
            transition: width 0.3s ease;
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
                <div class="success-icon"></div>
                <div class="greeting">Excellent news, {{ $notifiable->name }}!</div>
                <div class="main-message">
                    Your document has been <strong>successfully verified</strong>! 🎉
                </div>
            </div>
            
            <div class="info-card">
                <div class="info-title">Document Verification Complete</div>
                <div class="info-item">
                    <span class="info-label">Document Name:</span>
                    <span class="info-value">{{ $document->name }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Document Type:</span>
                    <span class="info-value">{{ $documentType }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Verification Status:</span>
                    <span class="info-value" style="color: #10B981; font-weight: bold;">APPROVED</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Verified Date:</span>
                    <span class="info-value">{{ $verifiedDate }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Reviewer:</span>
                    <span class="info-value">IQRAQUEST Verification Team</span>
                </div>
            </div>

            <div class="benefits-card">
                <div class="benefits-title">Why This Matters</div>
                <div class="benefit-item">
                    <span class="benefit-icon">✅</span>
                    <span class="benefit-text">Quality Confirmed - Your document meets our high standards</span>
                </div>
                <div class="benefit-item">
                    <span class="benefit-icon">🤝</span>
                    <span class="benefit-text">Trust Building - Students can trust your credentials</span>
                </div>
                <div class="benefit-item">
                    <span class="benefit-icon">📈</span>
                    <span class="benefit-text">Progress Made - You're moving closer to full verification</span>
                </div>
                <div class="benefit-item">
                    <span class="benefit-icon">👨‍🏫</span>
                    <span class="benefit-text">Professional Standing - Part of our verified teacher community</span>
                </div>
            </div>

            <div class="progress-card">
                <div class="progress-title">Your Verification Journey</div>
                
                <div class="progress-item completed">
                    <span class="progress-icon">✅</span>
                    <span class="progress-text">Document submission</span>
                    <span class="progress-status status-completed">DONE</span>
                </div>
                
                <div class="progress-item completed">
                    <span class="progress-icon">✅</span>
                    <span class="progress-text">Document review</span>
                    <span class="progress-status status-completed">APPROVED</span>
                </div>
                
                <div class="progress-item pending">
                    <span class="progress-icon">⏳</span>
                    <span class="progress-text">Video verification</span>
                    <span class="progress-status status-pending">In progress</span>
                </div>
                
                <div class="progress-item pending">
                    <span class="progress-icon">⏳</span>
                    <span class="progress-text">Final approval</span>
                    <span class="progress-status status-pending">Pending</span>
                </div>

                <div class="progress-bar">
                    <div class="progress-fill"></div>
                </div>
                <div class="text-center" style="color: #14B8A6; font-weight: 600;">
                    ~75% Complete
                </div>
            </div>

            <div class="next-steps-section">
                <div class="next-steps-title">What's Next?</div>
                <div style="color: #64748b; margin-bottom: 20px; text-align: center;">
                    Almost there! Here's what happens next:
                </div>
                
                <div class="next-step-item">
                    <span class="next-step-icon">📹</span>
                    <div class="next-step-content">
                        <div class="next-step-title">Video Verification (if not completed)</div>
                        <div class="next-step-description">Schedule your verification call - 15-20 minute friendly interview to verify your identity and qualifications</div>
                    </div>
                </div>
                
                <div class="next-step-item">
                    <span class="next-step-icon">📄</span>
                    <div class="next-step-content">
                        <div class="next-step-title">Remaining Documents (if any)</div>
                        <div class="next-step-description">Upload any additional required documents - each gets reviewed within 24-48 hours</div>
                    </div>
                </div>
                
                <div class="next-step-item">
                    <span class="next-step-icon">🎯</span>
                    <div class="next-step-content">
                        <div class="next-step-title">Final Review</div>
                        <div class="next-step-description">All components reviewed together, final approval notification sent, welcome to the teaching community!</div>
                    </div>
                </div>
            </div>

            <div class="text-center">
                <a href="{{ config('app.url') }}/teacher/verification" class="cta-button">
                    Continue Verification Process
                </a>
            </div>

            <div class="text-center mb-4">
                <strong>Keep up the great work!</strong> You're making excellent progress toward becoming a verified IQRAQUEST teacher.
            </div>
        </div>

        <div class="footer">
            <div class="footer-text">
                If you have any questions about the next steps, our support team is here to help!
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