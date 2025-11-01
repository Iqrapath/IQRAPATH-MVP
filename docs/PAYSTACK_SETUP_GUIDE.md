# PayStack Setup Guide for IQRAQUEST

## Overview
This guide explains how to set up PayStack for processing teacher payouts and transfers in the IQRAQUEST platform.

## Prerequisites
- Active PayStack account (https://paystack.com)
- Business verification completed
- Nigerian bank account for settlements

## Step-by-Step Setup

### 1. Create PayStack Account
1. Visit https://paystack.com and sign up
2. Complete email verification
3. Log into your dashboard

### 2. Get API Keys
1. Navigate to **Settings → API Keys & Webhooks**
2. Copy your **Secret Key** (starts with `sk_`)
3. Copy your **Public Key** (starts with `pk_`)
4. Add to your `.env` file:
   ```env
   PAYSTACK_SECRET_KEY=sk_test_xxxxxxxxxxxxx
   PAYSTACK_PUBLIC_KEY=pk_test_xxxxxxxxxxxxx
   PAYSTACK_BASE_URL=https://api.paystack.co
   PAYSTACK_MERCHANT_EMAIL=your-business@email.com
   ```

### 3. Enable Transfers Feature

#### For Test Mode:
1. Go to **Settings → Preferences**
2. Scroll to **Transfers** section
3. Enable **"Allow transfers"**
4. Note: Some test accounts may have transfers restricted
5. Contact PayStack support if you need test transfers enabled

#### For Production Mode:
1. Complete **Business Verification**:
   - Business name and registration
   - Business address
   - Director/Owner information
   - Upload required documents (CAC, ID, utility bill)

2. Add **Settlement Account**:
   - Go to **Settings → Settlement**
   - Add your Nigerian bank account
   - Verify the account with test deposit

3. Enable **Transfers**:
   - Go to **Settings → Preferences**
   - Enable **"Allow transfers"**
   - Set transfer limits if needed

4. **Activate Live Mode**:
   - Complete all verification steps
   - Switch to live mode in dashboard
   - Update `.env` with live API keys

### 4. Configure Webhooks
1. Go to **Settings → API Keys & Webhooks**
2. Click **"Add Webhook URL"**
3. Add your webhook URL:
   ```
   https://yourdomain.com/api/webhooks/paystack
   ```
4. Select events to listen for:
   - ✅ `transfer.success`
   - ✅ `transfer.failed`
   - ✅ `transfer.reversed`
5. Save and copy the webhook secret
6. Add to `.env`:
   ```env
   PAYSTACK_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxx
   ```

### 5. Test the Integration

#### Using Test Mode:
```bash
# Run the test script
php test-paystack.php

# Check logs
tail -f storage/logs/laravel.log
```

#### Test Account Numbers:
PayStack provides these test account numbers:
- **Success**: `0123456789` (any bank)
- **Failed**: `0000000000` (any bank)

#### Expected Test Results:
- ✅ Recipient creation succeeds
- ✅ Transfer initiation succeeds
- ⚠️ Transfer may require OTP finalization in test mode
- ⚠️ Some test accounts may have transfers disabled

### 6. Common Issues & Solutions

#### Issue: "You cannot initiate third party payouts at this time"
**Cause**: Transfers feature not enabled on your account

**Solution**:
1. Complete business verification
2. Enable transfers in Settings → Preferences
3. Contact PayStack support for test mode access
4. Ensure settlement account is added

#### Issue: "Cannot resolve account"
**Cause**: Invalid bank code or account number

**Solution**:
1. Use correct PayStack bank codes (fetch from API)
2. Verify account number format (10 digits for Nigerian banks)
3. Use test account `0123456789` in test mode

#### Issue: "Insufficient funds"
**Cause**: PayStack balance too low

**Solution**:
1. Check your PayStack balance in dashboard
2. Top up your account if needed
3. In test mode, this shouldn't occur

#### Issue: "Invalid API key"
**Cause**: Wrong or expired API key

**Solution**:
1. Verify API key in `.env` matches dashboard
2. Ensure using correct mode (test vs live)
3. Check for extra spaces or quotes in `.env`

### 7. Production Checklist

Before going live with payouts:

- [ ] Business verification completed
- [ ] Settlement bank account added and verified
- [ ] Transfers feature enabled
- [ ] Live API keys configured in `.env`
- [ ] Webhook URL configured and tested
- [ ] Transfer limits set appropriately
- [ ] Test transfers completed successfully
- [ ] Error handling tested
- [ ] Admin notifications working
- [ ] Teacher notifications working
- [ ] Logs monitoring set up

### 8. Security Best Practices

1. **API Keys**:
   - Never commit API keys to version control
   - Use environment variables only
   - Rotate keys periodically
   - Use different keys for test/live

2. **Webhooks**:
   - Always verify webhook signatures
   - Use HTTPS only
   - Log all webhook events
   - Handle duplicate events

3. **Transfers**:
   - Validate all bank details before transfer
   - Set reasonable transfer limits
   - Monitor for suspicious activity
   - Keep audit logs of all transfers

### 9. Monitoring & Maintenance

#### Daily Checks:
- Review failed transfers in dashboard
- Check error logs for issues
- Monitor PayStack balance

#### Weekly Checks:
- Review transfer success rate
- Check for pending transfers
- Verify webhook delivery

#### Monthly Checks:
- Review transfer fees and costs
- Analyze payout patterns
- Update bank codes if needed

### 10. Support & Resources

- **PayStack Documentation**: https://paystack.com/docs
- **PayStack Support**: support@paystack.com
- **API Reference**: https://paystack.com/docs/api
- **Status Page**: https://status.paystack.com

### 11. Cost Structure

PayStack charges for transfers:
- **Local Transfers**: ₦50 per transfer
- **Free Transfers**: First 50 transfers per month (may vary)
- **Bulk Transfers**: Discounted rates available

Check current pricing: https://paystack.com/pricing

### 12. Testing Checklist

Use this checklist to verify your setup:

```bash
# 1. Test bank list fetch
php artisan tinker
>>> $service = app(App\Services\PayStackTransferService::class);
>>> $banks = $service->getSupportedBanks();
>>> count($banks['banks']); // Should return 200+

# 2. Test account verification
>>> $result = $service->verifyAccountNumber('0123456789', '044');
>>> $result['success']; // Should be true in test mode

# 3. Test transfer initiation
php test-paystack.php

# 4. Check logs
tail -f storage/logs/laravel.log | grep PayStack
```

### 13. Troubleshooting Commands

```bash
# Clear cache
php artisan cache:clear
php artisan config:clear

# Check PayStack configuration
php artisan tinker
>>> config('services.paystack')

# Test API connectivity
curl -H "Authorization: Bearer YOUR_SECRET_KEY" \
  https://api.paystack.co/bank

# View recent logs
tail -100 storage/logs/laravel.log | grep -i paystack
```

## Need Help?

If you encounter issues not covered in this guide:
1. Check PayStack dashboard for error details
2. Review `storage/logs/laravel.log` for detailed errors
3. Contact PayStack support with transaction references
4. Check PayStack status page for service issues

---

**Last Updated**: October 31, 2025
**Version**: 1.0
