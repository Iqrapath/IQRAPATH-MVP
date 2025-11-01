# PayStack Testing Guide

## Why Transactions Don't Show in PayStack Dashboard

When you approve a payout in **test mode**, the transaction may not appear in your PayStack dashboard for several reasons:

### 1. **Test Mode Limitations**
- PayStack test mode **simulates** transactions but doesn't process real money
- Test transactions may not always appear in the dashboard
- Some features (like transfers) have limited test support

### 2. **Invalid Account Details**
- Random/fake account numbers will fail validation
- PayStack checks if accounts exist before processing
- Error: `"Cannot resolve account"` means the account number doesn't exist

### 3. **Insufficient Balance**
- Your PayStack test account needs sufficient balance
- Transfers will fail if you don't have enough funds
- Test mode usually has a default balance

---

## How to Test PayStack Payouts Properly

### Option 1: Use PayStack Test Account Numbers

PayStack provides specific test account numbers that will pass validation:

**Test Bank Account:**
```
Bank: Access Bank
Account Number: 0690000031
Account Name: Test Account
```

**Test Bank Account (Alternative):**
```
Bank: GTBank  
Account Number: 0123456789
Account Name: Test User
```

### Option 2: Use PayStack's Transfer Recipient API

1. Go to PayStack Dashboard → Transfers → Recipients
2. Create a test recipient with valid details
3. Use that recipient's code in your payout requests

### Option 3: Check Logs Instead of Dashboard

In test mode, check your application logs instead:

```bash
# View recent logs
tail -f storage/logs/laravel.log

# Search for payout logs
grep "PayStack" storage/logs/laravel.log
```

---

## Current System Behavior

### When Admin Approves a Payout:

1. **Database Updates** ✅
   - Wallet balances updated
   - Transaction record created
   - Status changed to "approved"

2. **PayStack API Call** ⏳
   - System calls PayStack Transfer API
   - If successful: Status → "processing"
   - If failed: Error saved in notes

3. **Webhook Confirmation** 🔔
   - PayStack sends webhook when transfer completes
   - Status updates to "completed"
   - Only happens in live mode with real transfers

### What You'll See:

**In Test Mode:**
- ✅ Payout status changes to "approved"
- ✅ Wallet balances update correctly
- ✅ Transaction records created
- ❌ No real money transferred
- ❌ May not see in PayStack dashboard
- ✅ Errors logged in application logs

**In Live Mode:**
- ✅ Everything above PLUS
- ✅ Real money transferred
- ✅ Appears in PayStack dashboard
- ✅ Webhook confirms completion
- ✅ Status updates to "completed"

---

## Testing Workflow

### Step 1: Seed Test Data with Valid Accounts

```bash
php artisan db:seed --class=PayoutRequestSeeder
```

This now creates payout requests with PayStack test account numbers.

### Step 2: Approve a Payout

1. Go to Admin → Financial Management → Teacher Payouts
2. Click "Approve" on a pending request
3. Confirm approval

### Step 3: Check What Happened

**Check Application Logs:**
```bash
tail -50 storage/logs/laravel.log | grep -i "payout\|paystack"
```

**Check Payout Status:**
- Go back to Teacher Payouts page
- Look at the status badge
- Click "View Details" to see notes

**Check PayStack Dashboard:**
- Go to PayStack Dashboard → Transfers
- Look for recent transfers
- Note: May not appear in test mode

### Step 4: Verify Database

```bash
php artisan tinker
```

```php
// Check payout request
$payout = \App\Models\PayoutRequest::find(1);
echo "Status: " . $payout->status . "\n";
echo "Notes: " . $payout->notes . "\n";
echo "External Ref: " . $payout->external_reference . "\n";

// Check wallet
$wallet = $payout->teacher->teacherWallet;
echo "Balance: " . $wallet->balance . "\n";
echo "Pending: " . $wallet->pending_payouts . "\n";
echo "Withdrawn: " . $wallet->total_withdrawn . "\n";
```

---

## Common Errors and Solutions

### Error: "Invalid bank details for transfer"

**Cause:** Account number doesn't exist or is invalid

**Solution:**
- Use PayStack test account: `0690000031`
- Or verify account first using PayStack API
- Or use a real account number (in live mode)

### Error: "Cannot resolve account"

**Cause:** PayStack can't find the account in the bank

**Solution:**
- Check account number is correct
- Ensure bank name matches
- Use test account numbers in test mode

### Error: "Insufficient funds"

**Cause:** Your PayStack balance is too low

**Solution:**
- In test mode: Contact PayStack support for test balance
- In live mode: Fund your PayStack account

### Error: "Transfer recipient not found"

**Cause:** Recipient wasn't created properly

**Solution:**
- System will auto-create recipient
- Check PayStack dashboard → Transfers → Recipients
- Verify recipient details are correct

---

## Moving to Production

### Before Going Live:

1. **Switch to Live Keys**
   ```env
   PAYSTACK_SECRET_KEY=sk_live_xxxxx  # Change from sk_test_
   PAYSTACK_PUBLIC_KEY=pk_live_xxxxx  # Change from pk_test_
   ```

2. **Fund Your PayStack Account**
   - Go to PayStack Dashboard → Balance
   - Add funds to cover payouts
   - Maintain sufficient balance

3. **Configure Webhooks**
   ```
   Webhook URL: https://yourdomain.com/webhooks/paystack/transfer
   Events: transfer.success, transfer.failed, transfer.reversed
   ```

4. **Test with Small Amount**
   - Approve a small payout first (₦100-500)
   - Verify it appears in dashboard
   - Confirm money arrives in recipient account
   - Check webhook is received

5. **Monitor Logs**
   ```bash
   tail -f storage/logs/laravel.log
   ```

### In Live Mode:

- ✅ Transfers will appear in PayStack dashboard
- ✅ Real money will be transferred
- ✅ Webhooks will update status automatically
- ✅ Recipients will receive funds in 1-2 business days
- ✅ You'll see transfer fees deducted

---

## Troubleshooting Checklist

- [ ] Using correct API keys (test vs live)
- [ ] PayStack account has sufficient balance
- [ ] Account numbers are valid
- [ ] Bank names match PayStack's bank list
- [ ] Webhooks are configured correctly
- [ ] Application logs show detailed errors
- [ ] Network can reach PayStack API
- [ ] No firewall blocking requests

---

## Support

**PayStack Support:**
- Email: support@paystack.com
- Docs: https://paystack.com/docs/transfers/single-transfers
- Dashboard: https://dashboard.paystack.com

**Application Logs:**
```bash
storage/logs/laravel.log
```

**Test Account Numbers:**
- Access Bank: 0690000031
- GTBank: 0123456789
