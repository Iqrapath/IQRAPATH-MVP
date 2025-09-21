<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verification Call Scheduled - IQRAQUEST</title>
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
            content: "📅";
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
            content: "📋";
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
        .preparation-section {
            background: #f8fafc;
            border-radius: 12px;
            padding: 24px;
            margin: 24px 0;
        }
        .preparation-title {
            font-size: 20px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 20px;
            text-align: center;
        }
        .preparation-item {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            padding: 12px 16px;
            background: white;
            border-radius: 8px;
            border-left: 4px solid #14B8A6;
        }
        .preparation-item:last-child {
            margin-bottom: 0;
        }
        .preparation-icon {
            margin-right: 12px;
            font-size: 18px;
        }
        .preparation-text {
            color: #1e293b;
            font-weight: 500;
        }
        .important-note {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 1px solid #fbbf24;
            border-radius: 12px;
            padding: 20px;
            margin: 24px 0;
            text-align: center;
        }
        .important-text {
            color: #92400e;
            font-weight: 600;
            font-size: 16px;
        }
        .notes-card {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border: 1px solid #86efac;
            border-radius: 12px;
            padding: 24px;
            margin: 24px 0;
        }
        .notes-title {
            font-size: 18px;
            font-weight: 600;
            color: #166534;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
        }
        .notes-title::before {
            content: "📝";
            margin-right: 8px;
            font-size: 20px;
        }
        .notes-content {
            color: #166534;
            line-height: 1.6;
            background: white;
            padding: 16px;
            border-radius: 8px;
            border-left: 4px solid #22c55e;
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
                <div class="greeting">Hello {{ $verificationRequest->teacherProfile->user->name ?? 'Teacher' }}!</div>
                <div class="main-message">
                    Your verification call has been <strong>scheduled successfully</strong>! 🎉
                </div>
            </div>
            
            <div class="info-card">
                <div class="info-title">Call Details</div>
                <div class="info-item">
                    <span class="info-label">Date & Time:</span>
                    <span class="info-value">{{ $scheduledDate->format('M d, Y g:i A') }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Platform:</span>
                    <span class="info-value">{{ $platformLabel }}</span>
                </div>
                @if($meetingLink)
                <div class="info-item">
                    <span class="info-label">Meeting Link:</span>
                    <span class="info-value">
                        <a href="{{ $meetingLink }}" style="color: #14B8A6; text-decoration: none;">Click to join</a>
                    </span>
                </div>
                @endif
                @if($notes)
                <div class="info-item">
                    <span class="info-label">Notes:</span>
                    <span class="info-value">{{ $notes }}</span>
                </div>
                @endif
            </div>

            @if($meetingLink)
            <div class="meeting-link-card">
                <div class="meeting-title">Meeting Room</div>
                <a href="{{ $meetingLink }}" class="meeting-link">
                    Join Meeting
                </a>
            </div>
            @endif

            <div class="preparation-section">
                <div class="preparation-title">What to Prepare</div>
                
                <div class="preparation-item">
                    <span class="preparation-icon">🆔</span>
                    <span class="preparation-text">Valid government-issued ID</span>
                </div>
                
                <div class="preparation-item">
                    <span class="preparation-icon">📜</span>
                    <span class="preparation-text">Teaching certificates or qualifications</span>
                </div>
                
                <div class="preparation-item">
                    <span class="preparation-icon">🌐</span>
                    <span class="preparation-text">Stable internet connection</span>
                </div>
                
                <div class="preparation-item">
                    <span class="preparation-icon">🔇</span>
                    <span class="preparation-text">Quiet environment</span>
                </div>
            </div>

            <div class="important-note">
                <div class="important-text">
                    ⏰ Please join 5 minutes early to test your setup.
                </div>
            </div>

            @if($notes)
            <div class="notes-card">
                <div class="notes-title">Additional Notes</div>
                <div class="notes-content">
                    {{ $notes }}
                </div>
            </div>
            @endif

            <div class="text-center mb-4">
                If you have any questions, please contact our support team.
            </div>
        </div>

        <div class="footer">
            <div class="footer-text">
                Best regards,<br>IQRAQUEST Team
            </div>
            
            <div class="support-link">
                <a href="mailto:support@iqraquest.com">support@iqraquest.com</a>
            </div>

            <div class="copyright">
                © {{ date('Y') }} IQRAQUEST. All rights reserved.<br>
                This is an automated message. Please do not reply to this email.
            </div>
        </div>
    </div>
</body>
</html>