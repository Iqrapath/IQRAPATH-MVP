<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Reassignment - You Have Been Removed</title>
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
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
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
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .booking-info:last-child {
            margin-bottom: 0;
        }
        .info-icon {
            width: 20px;
            height: 20px;
            margin-right: 10px;
            color: #dc3545;
        }
        .info-label {
            font-weight: 600;
            color: #666;
            margin-right: 10px;
            min-width: 100px;
        }
        .info-value {
            color: #333;
            font-weight: 500;
        }
        .highlight {
            background: #fff3cd;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 600;
        }
        .admin-note {
            background: #e7f3ff;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin: 20px 0;
            border-radius: 0 8px 8px 0;
        }
        .admin-note h3 {
            margin: 0 0 10px 0;
            color: #dc3545;
            font-size: 16px;
        }
        .admin-note p {
            margin: 0;
            color: #555;
        }
        .action-button {
            display: inline-block;
            background: #dc3545;
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
            color: #666;
            font-size: 14px;
        }
        .footer a {
            color: #338078;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>Booking Reassignment</h1>
            <p style="margin: 10px 0 0 0; opacity: 0.9;">You have been removed from a teaching session</p>
        </div>
        
        <div class="content">
            <p>Hello {{ $notifiable->name }},</p>
            
            <p>You have been removed from the following teaching session. The session has been reassigned to another teacher:</p>
            
            <div class="booking-card">
                <div class="booking-info">
                    <svg class="info-icon" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="info-label">Student:</span>
                    <span class="info-value">{{ $booking->student->name }}</span>
                </div>
                
                <div class="booking-info">
                    <svg class="info-icon" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2h8a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 1a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                    </svg>
                    <span class="info-label">Subject:</span>
                    <span class="info-value highlight">{{ $booking->subject->template->name }}</span>
                </div>
                
                <div class="booking-info">
                    <svg class="info-icon" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                    </svg>
                    <span class="info-label">Date:</span>
                    <span class="info-value highlight">{{ $bookingDate }}</span>
                </div>
                
                <div class="booking-info">
                    <svg class="info-icon" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                    </svg>
                    <span class="info-label">Time:</span>
                    <span class="info-value highlight">{{ $bookingTime }}</span>
                </div>
            </div>
            
            @if($adminNote)
            <div class="admin-note">
                <h3>Admin Note:</h3>
                <p>{{ $adminNote }}</p>
            </div>
            @endif
            
            <p>This session is no longer on your schedule. If you have any questions about this change, please contact the administration.</p>
            
            <a href="{{ route('teacher.dashboard') }}" class="action-button">View Dashboard</a>
        </div>
        
        <div class="footer">
            <p>This is an automated message from IQRAQUEST.</p>
            <p>© {{ date('Y') }} IQRAQUEST. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
