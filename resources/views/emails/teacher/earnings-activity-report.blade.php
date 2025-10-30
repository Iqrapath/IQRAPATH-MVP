<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Earnings Activity Report</title>
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
            background-color: #338078;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 20px;
            border: 1px solid #ddd;
        }
        .summary-box {
            background-color: white;
            padding: 15px;
            margin: 15px 0;
            border-radius: 8px;
            border-left: 4px solid #338078;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .summary-item:last-child {
            border-bottom: none;
        }
        .summary-label {
            font-weight: 600;
            color: #666;
        }
        .summary-value {
            font-weight: bold;
            color: #338078;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background-color: white;
        }
        th {
            background-color: #338078;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-completed {
            background-color: #d1fae5;
            color: #065f46;
        }
        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        .status-failed {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #666;
            font-size: 14px;
        }
        .amount-positive {
            color: #10b981;
            font-weight: 600;
        }
        .amount-negative {
            color: #ef4444;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Earnings Activity Report</h1>
        <p>{{ now()->format('F d, Y') }}</p>
    </div>

    <div class="content">
        <p>Hello {{ $teacher->name }},</p>
        <p>Here's your earnings activity report for the last 30 days from IQRAQUEST.</p>

        <div class="summary-box">
            <h3 style="margin-top: 0; color: #338078;">Earnings Summary</h3>
            <div class="summary-item">
                <span class="summary-label">Total Earned:</span>
                <span class="summary-value">₦{{ number_format($summary['total_earned'], 2) }}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Current Balance:</span>
                <span class="summary-value">₦{{ number_format($summary['current_balance'], 2) }}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Pending Payouts:</span>
                <span class="summary-value">₦{{ number_format($summary['pending_payouts'], 2) }}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Total Withdrawn:</span>
                <span class="summary-value">₦{{ number_format($summary['total_withdrawn'], 2) }}</span>
            </div>
        </div>

        <h3 style="color: #338078;">Recent Transactions</h3>
        
        @if(count($transactions) > 0)
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction['date'] }}</td>
                            <td>{{ $transaction['description'] }}</td>
                            <td class="{{ in_array($transaction['type'], ['credit', 'session_payment', 'bonus']) ? 'amount-positive' : 'amount-negative' }}">
                                {{ in_array($transaction['type'], ['credit', 'session_payment', 'bonus']) ? '+' : '-' }}₦{{ number_format($transaction['amount'], 2) }}
                            </td>
                            <td>
                                <span class="status-badge status-{{ $transaction['status'] }}">
                                    {{ ucfirst($transaction['status']) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p style="text-align: center; color: #666; padding: 20px;">No transactions found in the last 30 days.</p>
        @endif

        <p style="margin-top: 30px;">
            <strong>Need help?</strong> Contact our support team at <a href="mailto:support@iqraquest.com" style="color: #338078;">support@iqraquest.com</a>
        </p>
    </div>

    <div class="footer">
        <p>This is an automated email from IQRAQUEST. Please do not reply to this email.</p>
        <p>&copy; {{ date('Y') }} IQRAQUEST. All rights reserved.</p>
    </div>
</body>
</html>
