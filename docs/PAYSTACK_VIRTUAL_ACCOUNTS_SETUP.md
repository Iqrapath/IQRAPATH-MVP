# Paystack Virtual Accounts Setup Guide

## Overview
This system uses Paystack Dedicated Virtual Accounts to provide each user with their own unique bank account number for funding their wallet.

## Features
- ✅ Automatic virtual account creation per user
- ✅ Real-time payment verification via webhooks
- ✅ Automatic wallet crediting
- ✅ Support for Wema Bank accounts
- ✅ Secure webhook signature verification

## Setup Instructions

### 1. Paystack Configuration

Add your Paystack credentials to `.env`:

```env
PAYSTACK_PUBLIC_KEY=pk_test_xxxxxxxxxxxxx
PAYSTACK_SECRET_KEY=sk_test_xxxxxxxxxxxxx
```

### 2. Webhook Configuration

#### A. Set Webhook URL in Paystack Dashboard

1. Log in to your [Paystack Dashboard](https://dashboard.paystack.com)
2. Go to **Settings** → **Webhooks**
3. Add webhook URL: `https://yourdomain.com/api/webhooks/paystack`
4. Save the webhook URL

#### B. Webhook Events

The system listens for these events:
- `charge.success` - When payment is received
- `dedicatedaccount.assign.success` - When virtual account is created
- `dedicatedaccount.assign.failed` - When virtual account creation fails

### 3. Testing

#### Test Virtual Account Creation

```bash
# Make API call to create virtual account
curl -X GET https://yourdomain.com/student/payment/virtual-account \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### Test Webhook Locally (using ngrok)

```bash
# Start ngrok
ngrok http 80

# Update Paystack webhook URL to ngrok URL
https://your-ngrok-url.ngrok.io/api/webhooks/paystack

# Test payment to virtual account
# Paystack will send webhook to your local server
```

### 4. Database Migration

Run the migration to create virtual_accounts table:

```bash
php artisan migrate
```

## How It Works

### 1. User Selects Bank Transfer

When a user selects "Bank Transfer" payment method:

```
Frontend → API: GET /student/payment/virtual-account
Backend → Paystack: Create dedicated account (if not exists)
Paystack → Backend: Return account details
Backend → Frontend: Return account number, bank name, beneficiary
Frontend: Display account details to user
```

### 2. User Makes Transfer

User transfers money to their unique virtual account number using their banking app.

### 3. Paystack Receives Payment

```
User Bank → Paystack: Transfer received
Paystack → Backend: Webhook (charge.success event)
Backend: Verify signature
Backend: Find virtual account
Backend: Credit user wallet
Backend: Create transaction record
```

### 4. Wallet Updated

User's wallet is automatically credited with the transferred amount.

## API Endpoints

### Get Virtual Account
```
GET /student/payment/virtual-account
GET /guardian/payment/virtual-account

Response:
{
  "success": true,
  "data": {
    "account_number": "1234567890",
    "account_name": "IQRAPATH/John Doe",
    "bank_name": "Wema Bank",
    "bank_code": "035"
  }
}
```

### Webhook Endpoint
```
POST /api/webhooks/paystack

Headers:
- x-paystack-signature: <signature>

Body: Paystack webhook payload
```

## Security

### Webhook Signature Verification

All webhooks are verified using HMAC SHA512:

```php
$signature = hash_hmac('sha512', $payload, $secretKey);
```

Only requests with valid signatures are processed.

### CSRF Protection

Webhook endpoint is excluded from CSRF protection in `VerifyCsrfToken` middleware.

## Database Schema

### virtual_accounts Table

```sql
CREATE TABLE virtual_accounts (
    id BIGINT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    account_number VARCHAR(255) UNIQUE,
    account_name VARCHAR(255),
    bank_name VARCHAR(255),
    bank_code VARCHAR(255),
    provider VARCHAR(255) DEFAULT 'paystack',
    provider_account_id VARCHAR(255),
    provider_response JSON,
    is_active BOOLEAN DEFAULT TRUE,
    activated_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (user_id, provider),
    INDEX (account_number)
);
```

## Logging

All webhook events and virtual account operations are logged:

```
[Paystack VA] Creating virtual account for user
[Paystack VA] Virtual account created successfully
[Paystack VA Webhook] Processing credit
[Financial Service] Funds added to wallet
```

Check logs at: `storage/logs/laravel.log`

## Troubleshooting

### Virtual Account Not Created

1. Check Paystack credentials in `.env`
2. Verify user has required fields (email, name, phone)
3. Check logs for error messages
4. Ensure Paystack account supports dedicated accounts

### Webhook Not Received

1. Verify webhook URL is correct in Paystack dashboard
2. Check webhook signature verification
3. Ensure webhook endpoint is accessible (not behind auth)
4. Check firewall/server configuration
5. Test with ngrok for local development

### Payment Not Credited

1. Check webhook logs for errors
2. Verify virtual account exists in database
3. Check transaction was successful in Paystack dashboard
4. Verify FinancialService is working correctly

## Production Checklist

- [ ] Update Paystack keys to live keys
- [ ] Set webhook URL to production domain
- [ ] Test virtual account creation
- [ ] Test payment flow end-to-end
- [ ] Monitor webhook logs
- [ ] Set up error alerting
- [ ] Document support process for users

## Support

For issues with Paystack integration:
- Paystack Documentation: https://paystack.com/docs
- Paystack Support: support@paystack.com
- Dedicated Accounts Guide: https://paystack.com/docs/payments/dedicated-virtual-accounts

## Notes

- Virtual accounts are created on-demand (first time user selects bank transfer)
- Each user can have only one active virtual account
- Accounts are reusable - users can transfer multiple times to same account
- Minimum transfer amount may apply (check with Paystack)
- Transfer processing time: Usually instant, max 24 hours
