<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Session Starting Soon</title>
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
            background-color: #F59E0B;
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
        .urgent-notice {
            background-color: #FEF3C7;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #F59E0B;
        }
        .meeting-link {
            background-color: #EFF6FF;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border: 1px solid #3B82F6;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #666;
            font-size: 14px;
        }
        .button {
            display: inline-block;
            background-color: #3B82F6;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>⏰ Session Starting Soon!</h1>
        <p>Your session begins in 15 minutes</p>
    </div>
    
    <div class="content">
        <h2>Hello {{ $student_name }}!</h2>
        
        <div class="urgent-notice">
            <h3>🚨 Reminder: Session Starting Soon!</h3>
            <p>Your {{ $subject_name }} session with {{ $teacher_name }} is starting in just 15 minutes!</p>
        </div>
        
        <div class="meeting-link">
            <h3>🔗 Join Your Session Now</h3>
            <p>Click the button below to join your session:</p>
            <div style="text-align: center;">
                <a href="{{ $meeting_link }}" class="button">Join Session Now</a>
            </div>
            <p style="font-size: 12px; color: #666; margin-top: 10px;">
                Meeting Link: <a href="{{ $meeting_link }}">{{ $meeting_link }}</a>
            </p>
        </div>
        
        <p><strong>Quick checklist before joining:</strong></p>
        <ul>
            <li>✅ Check your internet connection</li>
            <li>✅ Test your camera and microphone</li>
            <li>✅ Have your learning materials ready</li>
            <li>✅ Find a quiet, well-lit space</li>
            <li>✅ Close unnecessary applications</li>
        </ul>
        
        <p><strong>Session Details:</strong></p>
        <ul>
            <li><strong>Subject:</strong> {{ $subject_name }}</li>
            <li><strong>Teacher:</strong> {{ $teacher_name }}</li>
            <li><strong>Date:</strong> {{ \Carbon\Carbon::parse($booking_date)->format('l, F j, Y') }}</li>
            <li><strong>Time:</strong> {{ \Carbon\Carbon::parse($start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($end_time)->format('g:i A') }}</li>
        </ul>
        
        <p style="color: #F59E0B; font-weight: bold;">
            Don't miss your session! Join now to ensure you're ready when it starts.
        </p>
    </div>
    
    <div class="footer">
        <p>Thank you for choosing IQRAPATH for your learning journey!</p>
        <p>If you have any technical issues, please contact our support team immediately.</p>
    </div>
</body>
</html>
