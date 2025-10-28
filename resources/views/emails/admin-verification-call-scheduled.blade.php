<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verification Call Scheduled - Admin Notification</title>
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
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
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
        .admin-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
        .admin-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.3);
        }
        .admin-icon::before {
            content: "👨‍💼";
            font-size: 40px;
        }
        .teacher-card {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 2px solid #fbbf24;
            border-radius: 12px;
            padding: 20px;
            margin: 24px 0;
            text-align: center;
        }
        .teacher-name {
            font-size: 22px;
            font-weight: 700;
            color: #92400e;
            margin-bottom: 8px;
        }
        .teacher-label {
            font-size: 14px;
            color: #b45309;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
        .countdown-card {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border: 2px solid #3b82f6;
            border-radius: 16px;
            padding: 32px 24px;
            margin: 32px 0;
            text-align: center;
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.2);
        }
        .countdown-title {
            font-size: 18px;
            font-weight: 600;
            color: #1e40af;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .countdown-title::before {
            content: "⏰";
            margin-right: 10px;
            font-size: 24px;
        }
        .countdown-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin: 24px 0;
        }
        .countdown-item {
            background: white;
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .countdown-value {
            font-size: 36px;
            font-weight: 700;
            color: #1e40af;
            display: block;
            margin-bottom: 8px;
            font-family: 'Courier New', monospace;
        }
        .countdown-label {
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .time-zone-note {
            font-size: 14px;
            color: #475569;
            margin-top: 16px;
            font-style: italic;
        }
        .access-time-card {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border: 2px solid #22c55e;
            border-radius: 12px;
            padding: 20px;
            margin: 24px 0;
            text-align: center;
        }
        .access-time-text {
            color: #166534;
            font-weight: 600;
            font-size: 16px;
        }
        .access-time-value {
            color: #15803d;
            font-size: 20px;
            font-weight: 700;
            margin-top: 8px;
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
        .admin-reminders {
            background: #f8fafc;
            border-radius: 12px;
            padding: 24px;
            margin: 24px 0;
        }
        .reminders-title {
            font-size: 20px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 20px;
            text-align: center;
        }
        .reminder-item {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            padding: 12px 16px;
            background: white;
            border-radius: 8px;
            border-left: 4px solid #6366f1;
        }
        .reminder-item:last-child {
            margin-bottom: 0;
        }
        .reminder-icon {
            margin-right: 12px;
            font-size: 18px;
        }
        .reminder-text {
            color: #1e293b;
            font-weight: 500;
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
            color: #6366f1;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="{{ config('app.url') }}" class="logo">IQRAQUEST</a>
            <div class="admin-badge">Admin Notification</div>
        </div>

        <div class="content-card">
            <div class="text-center">
                <div class="admin-icon"></div>
                <div class="greeting">Verification Call Scheduled</div>
                <div class="main-message">
                    A verification call has been scheduled with a teacher candidate.
                </div>
            </div>
            
            <div class="teacher-card">
                <div class="teacher-label">Teacher Candidate</div>
                <div class="teacher-name">{{ $teacherName }}</div>
            </div>

            <div class="info-card">
                <div class="info-title">Call Details</div>
                <div class="info-item">
                    <span class="info-label">Date & Time:</span>
                    <span class="info-value">{{ $scheduledDate->format('l, F d, Y') }} at {{ $scheduledDate->format('g:i A') }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Platform:</span>
                    <span class="info-value">{{ $platformLabel }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Teacher ID:</span>
                    <span class="info-value">#{{ $verificationRequest->teacherProfile->user->id }}</span>
                </div>
                @if($meetingLink)
                <div class="info-item">
                    <span class="info-label">Meeting Link:</span>
                    <span class="info-value">
                        <a href="{{ $meetingLink }}" style="color: #6366f1; text-decoration: none;">Available</a>
                    </span>
                </div>
                @endif
            </div>

            @php
                $now = \Carbon\Carbon::now();
                $diff = $now->diff($scheduledDate);
                $totalDays = $diff->days;
                $days = $diff->d;
                $hours = $diff->h;
                $minutes = $diff->i;
                $isPast = $scheduledDate->isPast();
                
                // Calculate early access time (30 minutes before)
                $earlyAccessTime = $scheduledDate->copy()->subMinutes(30);
                $canAccessNow = $now->gte($earlyAccessTime);
            @endphp

            @if(!$isPast)
            <div class="countdown-card">
                <div class="countdown-title">Time Until Verification Call</div>
                
                <div class="countdown-grid">
                    <div class="countdown-item">
                        <span class="countdown-value">{{ $totalDays }}</span>
                        <span class="countdown-label">{{ $totalDays === 1 ? 'Day' : 'Days' }}</span>
                    </div>
                    <div class="countdown-item">
                        <span class="countdown-value">{{ $hours }}</span>
                        <span class="countdown-label">{{ $hours === 1 ? 'Hour' : 'Hours' }}</span>
                    </div>
                    <div class="countdown-item">
                        <span class="countdown-value">{{ $minutes }}</span>
                        <span class="countdown-label">{{ $minutes === 1 ? 'Minute' : 'Minutes' }}</span>
                    </div>
                    <div class="countdown-item">
                        <span class="countdown-value">{{ $diff->s }}</span>
                        <span class="countdown-label">{{ $diff->s === 1 ? 'Second' : 'Seconds' }}</span>
                    </div>
                </div>
                
                <div class="time-zone-note">
                    📍 All times are in your local timezone
                </div>
            </div>

            <div class="access-time-card">
                <div class="access-time-text">
                    🔓 You can join the meeting starting from:
                </div>
                <div class="access-time-value">
                    {{ $earlyAccessTime->format('g:i A') }} (30 minutes before)
                </div>
            </div>
            @endif

            @if($meetingLink)
            <div class="meeting-link-card">
                <div class="meeting-title">Join Meeting</div>
                <a href="{{ $meetingLink }}" class="meeting-link">
                    Access Meeting Room
                </a>
                <div style="margin-top: 12px; font-size: 12px; color: #64748b;">
                    💡 You can monitor this call to ensure quality standards
                </div>
            </div>
            @endif

            <div class="admin-reminders">
                <div class="reminders-title">Admin Checklist</div>
                
                <div class="reminder-item">
                    <span class="reminder-icon">✓</span>
                    <span class="reminder-text">Review teacher's documents before the call</span>
                </div>
                
                <div class="reminder-item">
                    <span class="reminder-icon">✓</span>
                    <span class="reminder-text">Prepare verification questions</span>
                </div>
                
                <div class="reminder-item">
                    <span class="reminder-icon">✓</span>
                    <span class="reminder-text">Test your audio/video setup beforehand</span>
                </div>
                
                <div class="reminder-item">
                    <span class="reminder-icon">✓</span>
                    <span class="reminder-text">Have the verification form ready</span>
                </div>
                
                <div class="reminder-item">
                    <span class="reminder-icon">✓</span>
                    <span class="reminder-text">Join 5 minutes early</span>
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
                <a href="{{ config('app.url') }}/admin/verification/{{ $verificationRequest->id }}" 
                   style="color: #6366f1; text-decoration: none; font-weight: 600;">
                    View Teacher's Verification Request →
                </a>
            </div>
        </div>

        <div class="footer">
            <div class="footer-text">
                This is an automated admin notification.<br>
                Please ensure you're available at the scheduled time.
            </div>
            
            <div class="support-link">
                <a href="mailto:admin@iqraquest.com">admin@iqraquest.com</a>
            </div>

            <div class="copyright">
                © {{ date('Y') }} IQRAQUEST. All rights reserved.<br>
                This is an automated message. Please do not reply to this email.
            </div>
        </div>
    </div>
</body>
</html>

