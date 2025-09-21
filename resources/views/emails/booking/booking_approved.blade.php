<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Booking Approved</title>
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
            background-color: #10B981;
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
            border-left: 4px solid #10B981;
        }
        .meeting-link {
            background-color: #EFF6FF;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border: 1px solid #3B82F6;
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
            background-color: #10B981;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
        }
        .join-button {
            background-color: #3B82F6;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>✅ Booking Approved!</h1>
        <p>Your session is confirmed</p>
    </div>
    
    <div class="content">
        <h2>Hello {{ $student_name }}!</h2>
        
        <p>Excellent news! Your booking has been approved by {{ $teacher_name }}. Your session is now confirmed and ready to go!</p>
        
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
        
        @if($meeting_link)
        <div class="meeting-link">
            <h3>🔗 Meeting Link</h3>
            <p>Click the button below to join your session:</p>
            <div style="text-align: center;">
                <a href="{{ $meeting_link }}" class="button join-button">Join Session</a>
            </div>
            <p style="font-size: 12px; color: #666; margin-top: 10px;">
                Meeting Link: <a href="{{ $meeting_link }}">{{ $meeting_link }}</a>
            </p>
        </div>
        @endif
        
        <p><strong>Important reminders:</strong></p>
        <ul>
            <li>Join the session 5 minutes before the scheduled time</li>
            <li>Ensure you have a stable internet connection</li>
            <li>Have your learning materials ready</li>
            <li>Test your camera and microphone beforehand</li>
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
