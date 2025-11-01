# Payout Flow Explained

## Why Status Shows "Approved" Even When Payment Fails

### The Current Flow:

```
Teacher Requests Withdrawal
    ↓
Admin Clicks "Approve"
    ↓
┌─────────────────────────────────────────┐
│ STEP 1: Database Transaction (ATOMIC)  │
│ ✅ Deduct from pending_payouts          │
│ ✅ Add to total_withdrawn                │
│ ✅ Create transaction record             │
│ ✅ Set status to "approved"              │
│ ✅ Save processed_by and processed_date  │
└─────────────────────────────────────────┘
    ↓
┌─────────────────────────────────────────┐
│ STEP 2: Payment Gateway Call (ASYNC)   │
│ ⚠️  Try to send money via PayStack      │
│ ⚠️  This MIGHT fail                      │
└─────────────────────────────────────────┘
    ↓
┌─────────────────────────────────────────┐
│ STEP 3: Handle Result                  │
│ If Success:                             │
│   ✅ Status → "processing"               │
│   ✅ Save external_reference             │
│   🔔 Notify admin: Success              │
│                                         │
│ If Failed:                              │
│   ⚠️  Status stays "approved"            │
│   📝 Add error to notes                  │
│   🔔 Notify admin: Failed               │
└─────────────────────────────────────────┘
    ↓
┌─────────────────────────────────────────┐
│ STEP 4: Webhook (LATER)                │
│ When PayStack completes transfer:       │
│   ✅ Status → "completed"                │
│   ✅ Money arrived in teacher's account  │
└─────────────────────────────────────────┘
```

### Why This Design?

**Accounting Principle: "Approved" = Admin Decision**

1. **"Approved" means admin approved it** - This is an accounting decision
2. **Money is committed** - It's no longer "pending", it's been approved for payout
3. **Payment gateway is just execution** - The actual transfer is a separate step

### Status Meanings:

| Status | What It Means | Money Status | Action Needed |
|--------|---------------|--------------|---------------|
| **pending** | Waiting for admin review | Locked in pending_payouts | Admin must approve/reject |
| **approved** | Admin approved, payment gateway failed | Moved to total_withdrawn | Admin must process manually |
| **processing** | Payment gateway is processing | Moved to total_withdrawn | Wait for webhook |
| **completed** | Money arrived in teacher's account | Moved to total_withdrawn | None - done! |
| **rejected** | Admin rejected | Returned to balance | None - closed |

### The Problem You Identified:

**Issue:** When payment gateway fails, status stays "approved" which looks like it succeeded.

**Why:** Because the admin DID approve it - the failure is in the execution, not the approval.

**Solution:** Check the payout notes to see if automatic transfer failed.

---

## Why Payment Gateway Logs Are Empty

### When Logs Are Created:

```php
// ✅ Log is created when API call succeeds
if ($response->successful()) {
    PaymentGatewayLog::create([...]);
}

// ✅ Log is created when API call fails
else {
    PaymentGatewayLog::create([...]);
}

// ❌ Log was NOT created when exception occurs BEFORE API call
catch (\Exception $e) {
    // Previously: No log created
    // Now: Log created with error details
}
```

### Common Failure Points:

1. **Account Validation** (BEFORE API call)
   - "Cannot resolve account"
   - "Invalid bank details"
   - Account number doesn't exist

2. **API Call** (DURING API call)
   - Network error
   - PayStack API down
   - Invalid credentials

3. **Processing** (AFTER API call)
   - Insufficient funds
   - Transfer limit exceeded
   - Account restrictions

### Now Fixed:

Payment gateway logs are now created for ALL attempts, including:
- ✅ Successful API calls
- ✅ Failed API calls
- ✅ Exceptions before API call
- ✅ Validation errors

---

## Payment Intents vs Payment Gateway Logs

### payment_gateway_logs
**Purpose:** Log ALL interactions with payment gateways (PayStack, Stripe, PayPal)

**Used for:**
- Payouts (sending money OUT)
- Incoming payments (receiving money IN)
- Refunds
- Transfers

**Populated when:**
- Any API call to PayStack/Stripe/PayPal
- Now includes failed attempts

### payment_intents
**Purpose:** Stripe-specific payment intents for INCOMING payments

**Used for:**
- Student subscription payments
- Student wallet top-ups
- One-time purchases

**NOT used for:**
- Payouts (we use Transfers, not Intents)
- PayStack payments (PayStack doesn't use intents)

**Why it's empty:**
- You're testing payouts, not incoming payments
- Intents are only for Stripe incoming payments

### payment_reconciliations
**Purpose:** Manual reconciliation records

**Used for:**
- When admin manually reconciles payments
- Fixing discrepancies
- Matching bank statements

**Why it's empty:**
- No manual reconciliations have been performed
- This is a manual admin action, not automatic

---

## Webhooks Explained

### What Webhooks Do:

```
PayStack processes transfer
    ↓
PayStack sends webhook to your server
    ↓
Your webhook endpoint receives it
    ↓
Updates payout status to "completed"
```

### Webhook Flow:

```php
// PayStack sends POST request to:
POST /webhooks/paystack/transfer

// With data:
{
    "event": "transfer.success",
    "data": {
        "transfer_code": "TRF_xxx",
        "status": "success",
        "amount": 100000,
        "recipient": {...}
    }
}

// Your code updates:
$payoutRequest->update([
    'status' => 'completed',
    'completed_at' => now()
]);
```

### Why Webhooks Haven't Fired:

1. **Test Mode** - PayStack test mode may not send webhooks
2. **Invalid Account** - Transfer never actually started
3. **Webhook Not Configured** - Need to set up webhook URL in PayStack dashboard
4. **Local Development** - PayStack can't reach localhost (use ngrok)

### To Enable Webhooks:

1. **Configure in PayStack Dashboard:**
   ```
   URL: https://yourdomain.com/webhooks/paystack/transfer
   Events: transfer.success, transfer.failed, transfer.reversed
   ```

2. **For Local Testing:**
   ```bash
   # Use ngrok to expose localhost
   ngrok http 8000
   
   # Use ngrok URL in PayStack webhook config
   https://abc123.ngrok.io/webhooks/paystack/transfer
   ```

3. **Verify Webhook:**
   - PayStack dashboard → Settings → Webhooks
   - Click "Test" to send test webhook
   - Check your logs to see if received

---

## Recommended Status Flow

### Option 1: Current (Accounting-First)

```
pending → approved → processing → completed
                  ↓
                (manual processing if gateway fails)
```

**Pros:**
- Clear accounting trail
- Admin approval is recorded
- Can process manually if needed

**Cons:**
- "Approved" looks like success even when it failed

### Option 2: Gateway-First (Alternative)

```
pending → processing → completed
       ↓
     failed (restore funds)
```

**Pros:**
- Status clearly shows gateway result
- Less confusion

**Cons:**
- Loses admin approval timestamp
- Harder to track manual processing

### Recommendation:

**Keep current flow BUT:**
1. ✅ Show warning badge for "approved" with failed notes
2. ✅ Add "Retry Payment" button for failed approved payouts
3. ✅ Show payment gateway logs in payout details
4. ✅ Add filter for "Needs Manual Processing"

---

## Summary

### Why "Approved" Even When Failed:
- Admin approval is separate from payment execution
- Status reflects admin decision, not gateway result
- Check notes field for actual payment status

### Why Logs Were Empty:
- Logs only created when API call was made
- Validation errors happened before API call
- Now fixed: All attempts are logged

### Why Intents Are Empty:
- Intents are for incoming Stripe payments only
- Payouts use Transfers, not Intents
- This is expected and correct

### Why Reconciliations Are Empty:
- Manual admin action only
- Not automatic
- Will populate when admin reconciles manually

### Next Steps:
1. Configure webhooks in PayStack dashboard
2. Use test account numbers for testing
3. Check payment_gateway_logs for all attempts
4. Monitor notifications for success/failure


---

## PayStack Integration Status (Updated: November 1, 2025)

### ✅ Fully Implemented:
- Transfer API integration with retry logic (30s timeout, 3 retries)
- Account restriction detection
- Automatic fallback to manual processing
- Admin notifications for restrictions
- Comprehensive error handling and logging
- Bank code resolution with caching
- Transfer status verification
- Webhook handling for transfer events

### ⚠️ Requires Setup:
To enable automatic PayStack transfers:
1. Log into PayStack dashboard at https://dashboard.paystack.com
2. Go to Settings → Preferences
3. Enable "Allow transfers" feature
4. Complete business verification (for production)
5. Add settlement bank account

See `docs/PAYSTACK_SETUP_GUIDE.md` for detailed setup instructions.

### Current Behavior:
When PayStack transfers are disabled:
- Payouts are automatically marked as `requires_manual_processing`
- All admins receive email and database notifications
- System logs detailed error information for debugging
- Admin can process manual bank transfer from dashboard
- Teacher wallet remains accurate (pending payout tracked)

### Test Scripts:
- `php test-paystack-connection.php` - Test API connectivity
- `php test-paystack.php` - Test transfer initiation
- `php fetch-paystack-banks.php` - Fetch supported banks

### Monitoring:
```bash
# Check payouts requiring manual processing
php artisan tinker --execute="echo App\Models\PayoutRequest::where('status', 'requires_manual_processing')->count();"

# View PayStack logs
tail -f storage/logs/laravel.log | grep PayStack

# Check admin notifications
php artisan tinker --execute="App\Models\User::where('role', 'admin')->first()->unreadNotifications;"
```

---
