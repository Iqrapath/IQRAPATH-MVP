# Automated Payout System Documentation

## Overview

The IQRAQUEST platform now supports **automated payouts** to teachers through multiple payment gateways:
- **PayStack** - For Nigerian bank transfers and mobile money
- **PayPal** - For international payouts
- **Stripe** - For international bank transfers and connected accounts

When an admin approves a payout request, the system automatically initiates the transfer through the appropriate payment gateway based on the teacher's selected payment method.

---

## How It Works

### 1. Teacher Requests Withdrawal

1. Teacher goes to **Earnings & Wallet** page
2. Clicks **"Withdraw Funds"**
3. Selects payment method:
   - **Bank Transfer** (Nigerian banks via PayStack)
   - **PayPal** (International)
   - **Stripe** (International)
   - **Mobile Money** (Nigerian mobile wallets)
4. Enters payment details (bank account, PayPal email, etc.)
5. Submits withdrawal request

**What happens:**
- Amount is deducted from teacher's `available balance`
- Amount is added to `pending payouts`
- Payout request is created with status `pending`

---

### 2. Admin Reviews and Approves

1. Admin goes to **Financial Management** → **Teacher Payouts**
2. Reviews pending payout requests
3. Clicks **"Approve"** on a request

**What happens automatically:**
1. **Database Updates:**
   - Payout status changes to `approved`
   - Amount is deducted from `pending_payouts`
   - Amount is added to `total_withdrawn`
   - Transaction record is created

2. **Payment Gateway Integration:**
   - System automatically calls the appropriate payment gateway API
   - Transfer is initiated to teacher's payment method
   - Payout status updates to `processing`

3. **Webhook Monitoring:**
   - System waits for webhook from payment gateway
   - When transfer completes, status updates to `completed`
   - If transfer fails, status updates to `failed` and funds are restored

---

## Payment Gateway Details

### PayStack (Nigerian Bank Transfers)

**Supported:**
- All Nigerian banks
- Mobile money (MTN, Airtel, etc.)
- Currency: NGN only

**Process:**
1. Admin approves payout
2. System creates PayStack transfer recipient
3. System initiates transfer via PayStack API
4. PayStack processes transfer (1-2 business days)
5. Webhook confirms completion
6. Money arrives in teacher's bank account

**Configuration Required:**
```env
PAYSTACK_SECRET_KEY=sk_live_xxxxx
PAYSTACK_PUBLIC_KEY=pk_live_xxxxx
PAYSTACK_MERCHANT_EMAIL=your@email.com
```

**Webhook URL:**
```
https://yourdomain.com/webhooks/paystack/transfer
```

---

### PayPal (International Payouts)

**Supported:**
- PayPal accounts worldwide
- Currencies: USD, EUR, GBP

**Process:**
1. Admin approves payout
2. System creates PayPal payout batch
3. PayPal sends money to teacher's PayPal email
4. Teacher receives email notification
5. Money appears in PayPal account (instant)
6. Webhook confirms completion

**Configuration Required:**
```env
PAYPAL_CLIENT_ID=xxxxx
PAYPAL_CLIENT_SECRET=xxxxx
PAYPAL_MODE=live  # or sandbox for testing
PAYPAL_WEBHOOK_ID=xxxxx
```

**Webhook URL:**
```
https://yourdomain.com/webhooks/paypal/payout
```

**Webhook Events to Subscribe:**
- `PAYMENT.PAYOUTS-ITEM.SUCCEEDED`
- `PAYMENT.PAYOUTS-ITEM.FAILED`
- `PAYMENT.PAYOUTS-ITEM.CANCELED`
- `PAYMENT.PAYOUTS-ITEM.RETURNED`
- `PAYMENT.PAYOUTS-ITEM.UNCLAIMED`

---

### Stripe (International Bank Transfers)

**Supported:**
- Stripe connected accounts
- Direct bank transfers
- Currencies: USD, EUR, GBP, and more

**Process:**
1. Admin approves payout
2. System creates Stripe payout/transfer
3. Stripe processes transfer (1-2 business days)
4. Webhook confirms completion
5. Money arrives in teacher's bank account

**Configuration Required:**
```env
STRIPE_KEY=pk_live_xxxxx
STRIPE_SECRET=sk_live_xxxxx
STRIPE_WEBHOOK_SECRET=whsec_xxxxx
```

**Webhook URL:**
```
https://yourdomain.com/webhooks/stripe/payout
```

**Webhook Events to Subscribe:**
- `payout.paid`
- `payout.failed`
- `payout.canceled`
- `transfer.paid`
- `transfer.failed`

---

## Payout Status Flow

```
pending → approved → processing → completed
                  ↓
                failed (funds restored)
                  ↓
              cancelled (funds restored)
```

### Status Definitions:

- **pending**: Waiting for admin approval
- **approved**: Admin approved, payment gateway transfer initiated
- **processing**: Payment gateway is processing the transfer
- **completed**: Money successfully transferred to teacher
- **failed**: Transfer failed, funds restored to teacher's balance
- **cancelled**: Transfer cancelled, funds restored
- **returned**: Payment returned by recipient, funds restored
- **unclaimed**: PayPal payment not claimed by recipient
- **approved_pending_transfer**: Approved but automatic transfer failed, requires manual processing

---

## Manual Processing Fallback

If automatic payout fails (e.g., API error, invalid payment details), the system:

1. Updates status to `approved_pending_transfer`
2. Adds failure reason to notes
3. Logs error for admin review
4. Admin can:
   - Retry automatic processing
   - Process manually through payment gateway dashboard
   - Mark as paid after manual processing

---

## Webhook Security

All webhooks are secured with signature verification:

### PayStack
- Verifies `x-paystack-signature` header
- Uses HMAC SHA512 with secret key

### Stripe
- Verifies `stripe-signature` header
- Uses HMAC SHA256 with webhook secret

### PayPal
- Verifies multiple headers (auth-algo, cert-url, transmission-id, etc.)
- Calls PayPal API to verify signature

**All webhook routes are excluded from CSRF protection.**

---

## Testing

### Sandbox/Test Mode

1. **PayStack Sandbox:**
   ```env
   PAYSTACK_SECRET_KEY=sk_test_xxxxx
   ```

2. **PayPal Sandbox:**
   ```env
   PAYPAL_MODE=sandbox
   PAYPAL_CLIENT_ID=sandbox_client_id
   ```

3. **Stripe Test Mode:**
   ```env
   STRIPE_SECRET=sk_test_xxxxx
   ```

### Test Webhook Locally

Use tools like:
- **ngrok** - Create public URL for local development
- **Stripe CLI** - Forward Stripe webhooks to localhost
- **PayStack Test Mode** - Use test API keys

---

## Monitoring and Logs

All payout operations are logged:

```php
// Check logs
storage/logs/laravel.log
```

**Log entries include:**
- Payout request ID
- Teacher ID
- Amount and currency
- Payment gateway response
- Success/failure status
- Error messages

---

## Admin Dashboard

Admins can view:
- **Pending Payouts**: Awaiting approval
- **Processing Payouts**: Transfer in progress
- **Completed Payouts**: Successfully paid
- **Failed Payouts**: Requires attention

**Actions available:**
- Approve payout (triggers automatic transfer)
- Reject payout (restores funds to teacher)
- View payout details
- Retry failed payouts
- Mark as paid (for manual processing)

---

## Teacher Dashboard

Teachers can view:
- **Available Balance**: Can be withdrawn
- **Pending Payouts**: Awaiting admin approval
- **Total Withdrawn**: Historical withdrawals
- **Transaction History**: All earnings and withdrawals

---

## Configuration Checklist

### Required Environment Variables:

```env
# PayStack
PAYSTACK_SECRET_KEY=sk_live_xxxxx
PAYSTACK_PUBLIC_KEY=pk_live_xxxxx
PAYSTACK_MERCHANT_EMAIL=your@email.com

# PayPal
PAYPAL_CLIENT_ID=xxxxx
PAYPAL_CLIENT_SECRET=xxxxx
PAYPAL_MODE=live
PAYPAL_WEBHOOK_ID=xxxxx

# Stripe
STRIPE_KEY=pk_live_xxxxx
STRIPE_SECRET=sk_live_xxxxx
STRIPE_WEBHOOK_SECRET=whsec_xxxxx
```

### Webhook URLs to Configure:

1. **PayStack Dashboard** → Settings → Webhooks
   - URL: `https://yourdomain.com/webhooks/paystack/transfer`
   - Events: `transfer.success`, `transfer.failed`, `transfer.reversed`

2. **PayPal Dashboard** → Webhooks
   - URL: `https://yourdomain.com/webhooks/paypal/payout`
   - Events: All payout events

3. **Stripe Dashboard** → Webhooks
   - URL: `https://yourdomain.com/webhooks/stripe/payout`
   - Events: `payout.*`, `transfer.*`

---

## Troubleshooting

### Payout Stuck in "Processing"

**Cause**: Webhook not received from payment gateway

**Solution**:
1. Check webhook configuration in payment gateway dashboard
2. Verify webhook URL is accessible
3. Check webhook logs in payment gateway dashboard
4. Manually verify payout status using admin dashboard

### Payout Failed

**Cause**: Invalid payment details, insufficient funds, or API error

**Solution**:
1. Check failure reason in payout notes
2. Verify teacher's payment details are correct
3. Check payment gateway dashboard for more details
4. Retry payout or process manually

### Webhook Signature Verification Failed

**Cause**: Incorrect webhook secret or configuration

**Solution**:
1. Verify webhook secrets in `.env` file
2. Check webhook configuration in payment gateway dashboard
3. Ensure webhook URL matches exactly
4. Check server logs for detailed error

---

## Support

For issues or questions:
1. Check logs: `storage/logs/laravel.log`
2. Review payment gateway dashboard
3. Contact payment gateway support
4. Review this documentation

---

## Future Enhancements

Planned features:
- Batch payout processing
- Scheduled automatic payouts
- Multi-currency conversion
- Payout analytics and reporting
- Teacher payout preferences
- Automatic retry for failed payouts
