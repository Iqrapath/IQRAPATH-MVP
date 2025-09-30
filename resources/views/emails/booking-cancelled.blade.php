<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Cancelled - IQRAQUEST</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #2B6B65;
            text-decoration: none;
        }
        .content-card {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 40px;
            margin-bottom: 30px;
        }
        .greeting {
            font-size: 18px;
            color: #2d3748;
            margin-bottom: 20px;
        }
        .main-message {
            font-size: 16px;
            color: #4a5568;
            margin-bottom: 25px;
        }
        .booking-details {
            background-color: #f7fafc;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .booking-details h3 {
            color: #2d3748;
            margin: 0 0 15px 0;
            font-size: 16px;
            font-weight: 600;
        }
        .detail-row {
            display: flex;
            margin-bottom: 8px;
            align-items: center;
        }
        .detail-label {
            font-weight: 600;
            color: #4a5568;
            min-width: 80px;
            margin-right: 10px;
        }
        .detail-value {
            color: #2d3748;
        }
        .highlight {
            color: #2B6B65;
            font-weight: 600;
        }
        .reason-section {
            background-color: #fef5e7;
            border-left: 4px solid #f6ad55;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .reason-title {
            font-weight: 600;
            color: #744210;
            margin-bottom: 8px;
        }
        .reason-text {
            color: #744210;
            font-style: italic;
        }
        .footer-text {
            text-align: center;
            color: #718096;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .support-link {
            text-align: center;
            color: #718096;
            font-size: 14px;
        }
        .support-link a {
            color: #2B6B65;
            text-decoration: underline;
        }
        .copyright {
            text-align: center;
            color: #a0aec0;
            font-size: 12px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="{{ config('app.url') }}" class="logo">IQRAQUEST</a>
        </div>

        <div class="content-card">
            <div class="greeting">Hello {{ $user->name }},</div>
            
            <div class="main-message">
                We are writing to inform you that a booking has been cancelled.
            </div>

            <div class="booking-details">
                <h3>Booking Details:</h3>
                <div class="detail-row">
                    <span class="detail-label">Date:</span>
                    <span class="detail-value">{{ \Carbon\Carbon::parse($booking->booking_date)->format('F j, Y') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Time:</span>
                    <span class="detail-value">{{ \Carbon\Carbon::parse($booking->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($booking->end_time)->format('g:i A') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Subject:</span>
                    <span class="detail-value highlight">{{ $booking->subject->template->name ?? 'Unknown Subject' }}</span>
                </div>
                @if($isStudent)
                <div class="detail-row">
                    <span class="detail-label">Teacher:</span>
                    <span class="detail-value highlight">{{ $booking->teacher->name }}</span>
                </div>
                @else
                <div class="detail-row">
                    <span class="detail-label">Student:</span>
                    <span class="detail-value highlight">{{ $booking->student->name }}</span>
                </div>
                @endif
            </div>

            @if($reason)
            <div class="reason-section">
                <div class="reason-title">Reason for Cancellation:</div>
                <div class="reason-text">{{ $reason }}</div>
            </div>
            @endif

            <div class="main-message">
                We apologize for any inconvenience this may cause.
            </div>
        </div>

        <div class="footer-text">
            If you have any questions, please contact our support team.
        </div>
        
        <div class="support-link">
            <a href="mailto:support@iqraquest.com">support@iqraquest.com</a>
        </div>

        <div class="copyright">
            © {{ date('Y') }} IQRAQUEST. All rights reserved.
        </div>
    </div>
</body>
</html>
