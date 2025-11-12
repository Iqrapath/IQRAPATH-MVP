<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .email-header {
            background: linear-gradient(135deg, #14B8A6 0%, #0D9488 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .email-header p {
            margin: 5px 0 0 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .email-body {
            padding: 30px 20px;
        }
        .greeting {
            font-size: 18px;
            color: #1E293B;
            margin-bottom: 20px;
        }
        .message-box {
            background-color: #F8FAFC;
            border-left: 4px solid #14B8A6;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .message-box p {
            margin: 0;
            color: #475569;
            line-height: 1.8;
        }
        .payout-details {
            background-color: #F0FDFA;
            border: 1px solid #14B8A6;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .payout-details h3 {
            margin: 0 0 15px 0;
            color: #0F172A;
            font-size: 16px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #E2E8F0;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            color: #64748B;
            font-size: 14px;
        }
        .detail-value {
            color: #0F172A;
            font-weight: 600;
            font-size: 14px;
        }
        .amount-highlight {
            background: linear-gradient(135deg, #14B8A6 0%, #0D9488 100%);
            color: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
        }
        .amount-highlight .label {
            font-size: 12px;
            opacity: 0.9;
            margin-bottom: 5px;
        }
        .amount-highlight .amount {
            font-size: 32px;
            font-weight: bold;
        }
        .cta-button {
            display: inline-block;
            background-color: #14B8A6;
            color: white;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            margin: 20px 0;
            text-align: center;
        }
        .cta-button:hover {
            background-color: #0D9488;
        }
        .email-footer {
            background-color: #F8FAFC;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #E2E8F0;
        }
        .email-footer p {
            margin: 5px 0;
            font-size: 12px;
            color: #64748B;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin: 10px 0;
        }
        .status-success {
            background-color: #DCFCE7;
            color: #16A34A;
        }
        .status-warning {
            background-color: #FEF3C7;
            color: #D97706;
        }
        .status-error {
            background-color: #FEE2E2;
            color: #DC2626;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <h1>IQRAQUEST</h1>
            <p>Islamic Learning & Quran Memorization Platform</p>
        </div>

        <!-- Body -->
        <div class="email-body">
            <p class="greeting">Hello {{ $recipientName }},</p>

            <!-- Status Badge -->
            <div style="text-align: center;">
                <span class="status-badge status-{{ $notificationType === 'payout_success' ? 'success' : ($notificationType === 'reminder' ? 'warning' : 'error') }}">
                    {{ $title }}
                </span>
            </div>

            <!-- Message Box -->
            <div class="message-box">
                <p>{{ $message }}</p>
            </div>

            <!-- Amount Highlight -->
            <div class="amount-highlight">
                <div class="label">Payout Amount</div>
                <div class="amount">{{ $currency === 'NGN' ? '₦' : $currency }} {{ number_format($amount, 2) }}</div>
            </div>

            <!-- Payout Details -->
            <div class="payout-details">
                <h3>Payout Details</h3>
                <div class="detail-row">
                    <span class="detail-label">Payment Method</span>
                    <span class="detail-value">{{ $paymentMethod }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Request Date</span>
                    <span class="detail-value">{{ \Carbon\Carbon::parse($requestDate)->format('M d, Y') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status</span>
                    <span class="detail-value">{{ ucfirst(str_replace('_', ' ', $notificationType)) }}</span>
                </div>
            </div>

            <!-- Call to Action -->
            <div style="text-align: center;">
                <a href="{{ $actionUrl }}" class="cta-button">View Payout Details</a>
            </div>

            <!-- Additional Info -->
            <p style="color: #64748B; font-size: 14px; margin-top: 30px;">
                If you have any questions about this payout, please contact our support team or check your dashboard for more information.
            </p>
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <p><strong>IQRAQUEST</strong></p>
            <p>Islamic Learning & Quran Memorization Platform</p>
            <p>www.iqraquest.com | support@iqraquest.com</p>
            <p style="margin-top: 15px;">© {{ date('Y') }} IQRAQUEST. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
