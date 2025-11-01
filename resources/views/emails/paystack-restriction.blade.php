<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payout Requires Manual Processing</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f4f4f4;
        }
        
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .header {
            background: linear-gradient(135deg, #14B8A6 0%, #0D9488 100%);
            color: #ffffff;
            padding: 30px 40px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .header p {
            font-size: 14px;
            opacity: 0.95;
        }
        
        .alert-banner {
            background-color: #FEF3C7;
            border-left: 4px solid #F59E0B;
            padding: 16px 40px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert-icon {
            font-size: 24px;
        }
        
        .alert-text {
            flex: 1;
        }
        
        .alert-text strong {
            display: block;
            color: #92400E;
            font-size: 16px;
            margin-bottom: 4px;
        }
        
        .alert-text span {
            color: #78350F;
            font-size: 14px;
        }
        
        .content {
            padding: 40px;
        }
        
        .greeting {
            font-size: 16px;
            color: #1F2937;
            margin-bottom: 20px;
        }
        
        .section {
            margin-bottom: 32px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #1F2937;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .section-icon {
            font-size: 20px;
        }
        
        .info-grid {
            background-color: #F9FAFB;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 16px;
        }
        
        .info-row {
            display: flex;
            padding: 8px 0;
            border-bottom: 1px solid #E5E7EB;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #6B7280;
            min-width: 140px;
            font-size: 14px;
        }
        
        .info-value {
            color: #1F2937;
            font-size: 14px;
            flex: 1;
        }
        
        .bank-details {
            background-color: #EFF6FF;
            border: 1px solid #DBEAFE;
            border-radius: 6px;
            padding: 20px;
        }
        
        .error-box {
            background-color: #FEE2E2;
            border: 1px solid #FECACA;
            border-radius: 6px;
            padding: 16px;
            margin-bottom: 16px;
        }
        
        .error-box p {
            color: #991B1B;
            font-size: 14px;
            margin-bottom: 12px;
        }
        
        .error-box ul {
            list-style: none;
            padding-left: 0;
        }
        
        .error-box li {
            color: #B91C1C;
            font-size: 13px;
            padding: 4px 0;
            padding-left: 20px;
            position: relative;
        }
        
        .error-box li:before {
            content: "•";
            position: absolute;
            left: 8px;
        }
        
        .action-steps {
            background-color: #F0FDF4;
            border: 1px solid #BBF7D0;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 16px;
        }
        
        .action-steps h4 {
            color: #166534;
            font-size: 15px;
            margin-bottom: 12px;
            font-weight: 600;
        }
        
        .action-steps ol {
            padding-left: 20px;
            margin: 0;
        }
        
        .action-steps li {
            color: #15803D;
            font-size: 14px;
            padding: 6px 0;
        }
        
        .button-container {
            text-align: center;
            margin: 32px 0;
        }
        
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #14B8A6 0%, #0D9488 100%);
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 15px;
            box-shadow: 0 2px 4px rgba(20, 184, 166, 0.3);
            transition: all 0.3s ease;
        }
        
        .button:hover {
            box-shadow: 0 4px 8px rgba(20, 184, 166, 0.4);
            transform: translateY(-1px);
        }
        
        .secondary-button {
            display: inline-block;
            background-color: #ffffff;
            color: #14B8A6 !important;
            text-decoration: none;
            padding: 12px 28px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            border: 2px solid #14B8A6;
            margin-left: 12px;
            transition: all 0.3s ease;
        }
        
        .secondary-button:hover {
            background-color: #F0FDFA;
        }
        
        .urgent-notice {
            background-color: #FEF3C7;
            border-left: 4px solid #F59E0B;
            padding: 16px;
            border-radius: 4px;
            margin: 24px 0;
        }
        
        .urgent-notice strong {
            color: #92400E;
            display: block;
            margin-bottom: 8px;
            font-size: 15px;
        }
        
        .urgent-notice p {
            color: #78350F;
            font-size: 14px;
            margin: 0;
        }
        
        .footer {
            background-color: #F9FAFB;
            padding: 30px 40px;
            text-align: center;
            border-top: 1px solid #E5E7EB;
        }
        
        .footer p {
            color: #6B7280;
            font-size: 13px;
            margin: 8px 0;
        }
        
        .footer a {
            color: #14B8A6;
            text-decoration: none;
        }
        
        .divider {
            height: 1px;
            background-color: #E5E7EB;
            margin: 24px 0;
        }
        
        @media only screen and (max-width: 600px) {
            .email-container {
                margin: 0;
                border-radius: 0;
            }
            
            .header, .content, .footer {
                padding: 20px;
            }
            
            .alert-banner {
                padding: 12px 20px;
            }
            
            .info-row {
                flex-direction: column;
            }
            
            .info-label {
                min-width: auto;
                margin-bottom: 4px;
            }
            
            .button-container {
                flex-direction: column;
            }
            
            .secondary-button {
                margin-left: 0;
                margin-top: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <h1>⚠️ Payout Requires Manual Processing</h1>
            <p>IQRAQUEST Teacher Payment System</p>
        </div>
        
        <!-- Alert Banner -->
        <div class="alert-banner">
            <div class="alert-icon">⚠️</div>
            <div class="alert-text">
                <strong>Action Required</strong>
                <span>A teacher payout could not be processed automatically</span>
            </div>
        </div>
        
        <!-- Content -->
        <div class="content">
            <p class="greeting">Hello {{ $admin->name }},</p>
            
            <p style="margin-bottom: 24px; color: #4B5563; font-size: 15px;">
                A teacher payout request could not be processed automatically through PayStack and requires your immediate attention.
            </p>
            
            <!-- Payout Details Section -->
            <div class="section">
                <h3 class="section-title">
                    <span class="section-icon">📋</span>
                    Payout Request Details
                </h3>
                
                <div class="info-grid">
                    <div class="info-row">
                        <span class="info-label">Request ID:</span>
                        <span class="info-value">#{{ $payoutRequest->request_uuid }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Teacher:</span>
                        <span class="info-value">{{ $payoutRequest->teacher->name }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Amount:</span>
                        <span class="info-value" style="font-weight: 600; color: #14B8A6; font-size: 16px;">
                            ₦{{ number_format($payoutRequest->amount, 2) }} {{ $payoutRequest->currency }}
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Payment Method:</span>
                        <span class="info-value">{{ ucwords(str_replace('_', ' ', $payoutRequest->payment_method)) }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Request Date:</span>
                        <span class="info-value">{{ $payoutRequest->request_date->format('F j, Y') }}</span>
                    </div>
                </div>
            </div>
            
            <!-- Bank Details Section -->
            <div class="section">
                <h3 class="section-title">
                    <span class="section-icon">🏦</span>
                    Bank Account Details
                </h3>
                
                <div class="bank-details">
                    <div class="info-row">
                        <span class="info-label">Bank Name:</span>
                        <span class="info-value">{{ $paymentDetails['bank_name'] ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Account Number:</span>
                        <span class="info-value" style="font-weight: 600;">{{ $paymentDetails['account_number'] ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Account Name:</span>
                        <span class="info-value">{{ $paymentDetails['account_name'] ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>
            
            <div class="divider"></div>
            
            <!-- Error Details Section -->
            <div class="section">
                <h3 class="section-title">
                    <span class="section-icon">❌</span>
                    Error Details
                </h3>
                
                <div class="error-box">
                    <p><strong>{{ $errorMessage }}</strong></p>
                    <p style="margin-bottom: 8px;">This error typically occurs due to:</p>
                    <ul>
                        <li>Transfers feature not enabled in PayStack dashboard</li>
                        <li>Business verification not completed</li>
                        <li>Settlement account not configured</li>
                        <li>Account restrictions or limitations</li>
                    </ul>
                </div>
            </div>
            
            <!-- Action Steps Section -->
            <div class="section">
                <h3 class="section-title">
                    <span class="section-icon">✅</span>
                    Required Actions
                </h3>
                
                <div class="action-steps">
                    <h4>Option 1: Enable PayStack Transfers (Recommended)</h4>
                    <ol>
                        <li>Log into your PayStack dashboard at <a href="https://dashboard.paystack.com" style="color: #14B8A6;">dashboard.paystack.com</a></li>
                        <li>Navigate to Settings → Preferences</li>
                        <li>Enable "Allow transfers" feature</li>
                        <li>Complete business verification if prompted</li>
                        <li>Retry the payout from the admin dashboard</li>
                    </ol>
                </div>
                
                <div class="action-steps" style="background-color: #FEF3C7; border-color: #FDE68A;">
                    <h4 style="color: #92400E;">Option 2: Process Manual Bank Transfer</h4>
                    <ol style="color: #78350F;">
                        <li>Log into your bank account</li>
                        <li>Transfer ₦{{ number_format($payoutRequest->amount, 2) }} to the account details above</li>
                        <li>Mark the payout as completed in the admin dashboard</li>
                        <li>Upload the transfer receipt for records</li>
                    </ol>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="button-container">
                <a href="{{ url('/admin/financial/payouts?id=' . $payoutRequest->id) }}" class="button">
                    View & Process Payout
                </a>
                <a href="https://dashboard.paystack.com/settings" class="secondary-button">
                    PayStack Settings
                </a>
            </div>
            
            <!-- Urgent Notice -->
            <div class="urgent-notice">
                <strong>⏰ Time Sensitive</strong>
                <p>Please process this payout within 24 hours to maintain teacher satisfaction and platform reliability.</p>
            </div>
            
            <div class="divider"></div>
            
            <p style="color: #6B7280; font-size: 14px; margin-top: 24px;">
                If you need assistance with PayStack setup, please refer to our documentation at 
                <strong>docs/PAYSTACK_SETUP_GUIDE.md</strong> or contact PayStack support at 
                <a href="mailto:support@paystack.com" style="color: #14B8A6;">support@paystack.com</a>
            </p>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p><strong>IQRAQUEST Learning Platform</strong></p>
            <p>Islamic Education & Teacher Management System</p>
            <p style="margin-top: 16px;">
                <a href="{{ url('/admin/dashboard') }}">Admin Dashboard</a> | 
                <a href="{{ url('/admin/financial/payouts') }}">View All Payouts</a> | 
                <a href="mailto:support@iqraquest.com">Support</a>
            </p>
            <p style="margin-top: 16px; font-size: 12px;">
                © {{ date('Y') }} IQRAQUEST. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
