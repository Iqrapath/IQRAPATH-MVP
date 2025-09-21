<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Booking Confirmation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #14B8A6;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f8f9fa;
            padding: 30px;
            border-radius: 0 0 8px 8px;
        }
        .booking-details {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #14B8A6;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .detail-label {
            font-weight: bold;
            color: #666;
        }
        .detail-value {
            color: #333;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #666;
            font-size: 14px;
        }
        .button {
            display: inline-block;
            background-color: #14B8A6;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📚 Booking Confirmation</h1>
        <p>Your session has been scheduled</p>
    </div>
    
    <div class="content">
        <h2>Hello {{ $student_name }}!</h2>
        
        <p>Great news! Your booking request has been confirmed and is pending teacher approval.</p>
        
        <div class="booking-details">
            <h3>Session Details</h3>
            <div class="detail-row">
                <span class="detail-label">Subject:</span>
                <span class="detail-value">{{ $subject_name }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Teacher:</span>
                <span class="detail-value">{{ $teacher_name }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Date:</span>
                <span class="detail-value">{{ \Carbon\Carbon::parse($booking_date)->format('l, F j, Y') }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Time:</span>
                <span class="detail-value">{{ \Carbon\Carbon::parse($start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($end_time)->format('g:i A') }}</span>
            </div>
        </div>
        
        <p><strong>What happens next?</strong></p>
        <ul>
            <li>Your teacher will review and approve your booking request</li>
            <li>You'll receive a confirmation email once approved</li>
            <li>Meeting details and links will be provided</li>
        </ul>
        
        <div style="text-align: center;">
            <a href="{{ route('student.my-bookings') }}" class="button">View My Bookings</a>
        </div>
    </div>
    
    <div class="footer">
        <p>Thank you for choosing IQRAQUEST for your learning journey!</p>
        <p>If you have any questions, please contact our support team.</p>
    </div>
</body>
</html>
