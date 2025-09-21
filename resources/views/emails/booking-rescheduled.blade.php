<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Rescheduled - IQRAQUEST</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .email-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #338078 0%, #236158 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .content {
            padding: 30px;
        }
        .booking-card {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .booking-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        .info-item {
            display: flex;
            flex-direction: column;
        }
        .info-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .info-value {
            font-size: 14px;
            color: #333;
            font-weight: 500;
        }
        .reschedule-details {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }
        .reschedule-details h3 {
            margin: 0 0 10px 0;
            color: #856404;
            font-size: 16px;
        }
        .time-change {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 10px 0;
        }
        .old-time, .new-time {
            text-align: center;
            flex: 1;
        }
        .old-time {
            color: #dc3545;
        }
        .new-time {
            color: #28a745;
        }
        .arrow {
            font-size: 20px;
            color: #6c757d;
            margin: 0 15px;
        }
        .reason {
            background: #f8f9fa;
            border-left: 4px solid #338078;
            padding: 15px;
            margin: 20px 0;
        }
        .reason h4 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 14px;
        }
        .reason p {
            margin: 0;
            color: #666;
            font-style: italic;
        }
        .cta-button {
            display: inline-block;
            background: #338078;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #6c757d;
            font-size: 12px;
        }
        .footer a {
            color: #338078;
            text-decoration: none;
        }
        @media (max-width: 600px) {
            .booking-info {
                grid-template-columns: 1fr;
            }
            .time-change {
                flex-direction: column;
            }
            .arrow {
                transform: rotate(90deg);
                margin: 10px 0;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>📅 Booking Rescheduled</h1>
            <p style="margin: 10px 0 0 0; opacity: 0.9;">
                @if($isForTeacher)
                    Your teaching session has been rescheduled
                @else
                    Your learning session has been rescheduled
                @endif
            </p>
        </div>

        <div class="content">
            <p>Hello {{ $notifiable->name }},</p>
            
            <p>
                @if($isForTeacher)
                    Your teaching session has been rescheduled. Here are the updated details:
                @else
                    Your learning session has been rescheduled. Here are the updated details:
                @endif
            </p>

            <div class="booking-card">
                <div class="booking-info">
                    <div class="info-item">
                        <span class="info-label">Student</span>
                        <span class="info-value">{{ $booking->student->name }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Teacher</span>
                        <span class="info-value">{{ $booking->teacher->name }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Subject</span>
                        <span class="info-value">{{ $booking->subject->template->name }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Duration</span>
                        <span class="info-value">{{ $booking->duration_minutes }} minutes</span>
                    </div>
                </div>

                <div class="reschedule-details">
                    <h3>🔄 Schedule Change</h3>
                    <div class="time-change">
                        <div class="old-time">
                            <strong>Previous Time</strong><br>
                            {{ \Carbon\Carbon::parse($oldDate)->format('M d, Y') }}<br>
                            {{ \Carbon\Carbon::parse($oldTime)->format('g:i A') }}
                        </div>
                        <div class="arrow">→</div>
                        <div class="new-time">
                            <strong>New Time</strong><br>
                            {{ \Carbon\Carbon::parse($booking->booking_date)->format('M d, Y') }}<br>
                            {{ \Carbon\Carbon::parse($booking->start_time)->format('g:i A') }}
                        </div>
                    </div>
                </div>

                @if($reason)
                    <div class="reason">
                        <h4>Reason for Reschedule:</h4>
                        <p>{{ $reason }}</p>
                    </div>
                @endif
            </div>

            <p>
                @if($isForTeacher)
                    Please make sure you're available for the new time. If you have any concerns, please contact our support team.
                @else
                    Please make sure you're available for the new time. If you have any concerns, please contact our support team.
                @endif
            </p>

            <div style="text-align: center;">
                <a href="{{ route('admin.bookings.show', $booking->id) }}" class="cta-button">
                    View Booking Details
                </a>
            </div>

            <p style="margin-top: 30px; color: #666; font-size: 14px;">
                If you have any questions or need to make further changes, please don't hesitate to contact our support team.
            </p>
        </div>

        <div class="footer">
            <p>
                This email was sent by <strong>IQRAQUEST</strong><br>
                <a href="mailto:support@iqraquest.com">support@iqraquest.com</a> | 
                <a href="{{ route('home') }}">Visit our website</a>
            </p>
            <p style="margin-top: 15px; font-size: 11px; color: #999;">
                © {{ date('Y') }} IQRAQUEST. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
