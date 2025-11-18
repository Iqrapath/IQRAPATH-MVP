# Payment Settings Implementation - Complete Feature Set

## 🎯 Overview
This document outlines the complete implementation of the Payment Settings feature for the IQRAPATH platform, including commission management, auto-payouts, bank verification, and withdrawal controls.

---

## ✅ Features Implemented

### 1. **Commission Rate System**
- **Location**: `app/Services/FinancialService.php`
- **Functionality**:
  - Deducts platform commission from teacher earnings
  - Supports two commission types:
    - **Fixed Percentage**: Same rate for all teachers
    - **Tiered**: Rate based on teacher's total earnings
      - New teachers (< ₦50,000): 15%
      - Intermediate (₦50,000 - ₦200,000): 10%
      - Experienced (> ₦200,000): 5%
  - Stores commission details in `teaching_sessions` table:
    - `gross_amount`: Total session amount
    - `platform_commission`: Commission deducted
    - `teacher_earnings`: Net amount teacher receives
    - `commission_rate`: Rate applied

**Database Changes**:
```sql
ALTER TABLE teaching_sessions ADD COLUMN gross_amount DECIMAL(10,2);
ALTER TABLE teaching_sessions ADD COLUMN platform_commission DECIMAL(10,2);
ALTER TABLE teaching_sessions ADD COLUMN teacher_earnings DECIMAL(10,2);
ALTER TABLE teaching_sessions ADD COLUMN commission_rate DECIMAL(5,2);
```

**Usage**:
```php
// Commission is automatically applied when processing session payments
$transaction = $financialService->processSessionPayment($session);
```

---

### 2. **Auto-Payout System**
- **Location**: `app/Jobs/ProcessAutoPayouts.php`
- **Functionality**:
  - Automatically creates payout requests when teacher balance reaches threshold
  - Runs daily at 10:00 AM
  - Only processes teachers with:
    - Balance >= auto_payout_threshold
    - No pending payout requests
    - Active payment method on file
  - Sends notification to teacher when auto-payout is created

**Scheduled Task**:
```php
// routes/console.php
app(Schedule::class)->command('payouts:process-auto')
    ->dailyAt('10:00')
    ->withoutOverlapping();
```

**Manual Trigger**:
```bash
php artisan payouts:process-auto
```

---

### 3. **Bank Verification Requirement**
- **Location**: `app/Services/WithdrawalService.php`
- **Functionality**:
  - Validates bank account verification before allowing withdrawals
  - Checks `payment_methods` table for verified bank accounts
  - Can be enabled/disabled via settings

**Validation Logic**:
```php
if ($bankVerificationEnabled) {
    $hasVerifiedBank = PaymentMethod::where('user_id', $teacher->id)
        ->where('type', 'bank_transfer')
        ->where('is_verified', true)
        ->exists();
    
    if (!$hasVerifiedBank) {
        $errors[] = "Please verify your bank account before requesting withdrawal";
    }
}
```

---

### 4. **Withdrawal Note Display**
- **Location**: `app/Http/Controllers/Teacher/FinancialController.php`
- **Functionality**:
  - Displays custom withdrawal note to teachers
  - Shows minimum withdrawal amount
  - Indicates bank verification requirement
  - Passed to frontend via Inertia

**Controller Update**:
```php
return Inertia::render('Teacher/Financial/CreatePayoutRequest', [
    'availableBalance' => $availableBalance,
    'minimumWithdrawal' => $minimumWithdrawal,
    'withdrawalNote' => $withdrawalNote,
    'bankVerificationEnabled' => $bankVerificationEnabled,
    'paymentMethods' => [...],
]);
```

---

### 5. **Payment Settings UI**
- **Location**: `resources/js/pages/admin/financial/components/PaymentSettings.tsx`
- **Features**:
  - Inline editing for all settings
  - Real-time validation
  - Toast notifications
  - Apply time options (immediate or scheduled)

**Settings Available**:
1. **Commission Rate** (0-100%)
2. **Commission Type** (Fixed/Tiered)
3. **Auto-Payout Threshold** (₦0+)
4. **Minimum Withdrawal Amount** (₦0+)
5. **Bank Verification** (On/Off)
6. **Withdrawal Note** (Custom text)

---

## 📊 Database Schema

### Financial Settings Table
```sql
CREATE TABLE financial_settings (
    id BIGINT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE,
    setting_value TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Teaching Sessions Table (Updated)
```sql
ALTER TABLE teaching_sessions ADD (
    gross_amount DECIMAL(10,2) COMMENT 'Total session amount before commission',
    platform_commission DECIMAL(10,2) COMMENT 'Platform commission amount',
    teacher_earnings DECIMAL(10,2) COMMENT 'Net amount teacher receives',
    commission_rate DECIMAL(5,2) COMMENT 'Commission rate applied'
);
```

---

## 🔧 Configuration

### Default Settings
```php
'commission_rate' => 10,                    // 10%
'commission_type' => 'fixed_percentage',    // or 'tiered'
'auto_payout_threshold' => 50000,           // ₦50,000
'minimum_withdrawal_amount' => 10000,       // ₦10,000
'bank_verification_enabled' => true,
'withdrawal_note' => 'Withdrawals are processed within 1-3 business days.'
```

### Accessing Settings
```php
use App\Models\FinancialSetting;

// Get setting
$commissionRate = FinancialSetting::get('commission_rate', 10);

// Set setting
FinancialSetting::set('commission_rate', 15);

// Get all settings
$allSettings = FinancialSetting::getAllSettings();
```

---

## 🚀 API Endpoints

### Update Payment Settings
```http
POST /admin/financial/settings/payment
Content-Type: application/json

{
    "commission_rate": 10,
    "commission_type": "fixed_percentage",
    "auto_payout_threshold": 50000,
    "minimum_withdrawal_amount": 10000,
    "bank_verification_enabled": true,
    "withdrawal_note": "Custom note",
    "apply_time": "now",
    "scheduled_date": null
}
```

**Response**:
```json
{
    "success": true,
    "message": "Payment settings updated successfully!",
    "data": {
        "commission_rate": 10,
        "commission_type": "fixed_percentage",
        ...
    }
}
```

---

## 📝 Usage Examples

### 1. Processing Session Payment with Commission
```php
use App\Services\FinancialService;

$financialService = app(FinancialService::class);

// Process completed session
$transaction = $financialService->processSessionPayment($session);

// Commission is automatically deducted
// Session record updated with:
// - gross_amount: ₦10,000
// - platform_commission: ₦1,000 (10%)
// - teacher_earnings: ₦9,000
```

### 2. Validating Withdrawal Request
```php
use App\Services\WithdrawalService;

$withdrawalService = app(WithdrawalService::class);

// Validate withdrawal
$errors = $withdrawalService->validateWithdrawalLimits($teacher, $amount);

if (!empty($errors)) {
    // Handle validation errors
    // - Bank not verified
    // - Below minimum amount
    // - Exceeds daily/monthly limit
}
```

### 3. Triggering Auto-Payouts
```bash
# Manual trigger
php artisan payouts:process-auto

# Scheduled (runs daily at 10:00 AM)
# Automatically processes teachers with balance >= threshold
```

---

## 🔍 Testing

### Test Commission Calculation
```php
// Create a completed session
$session = TeachingSession::factory()->completed()->create([
    'teacher_id' => $teacher->id,
    'start_time' => '10:00:00',
    'end_time' => '11:00:00',
]);

// Process payment
$transaction = $financialService->processSessionPayment($session);

// Verify commission was applied
$session->refresh();
expect($session->gross_amount)->toBe(10000.00);
expect($session->platform_commission)->toBe(1000.00);
expect($session->teacher_earnings)->toBe(9000.00);
```

### Test Auto-Payout
```php
// Set teacher balance above threshold
$teacher->earnings->update(['wallet_balance' => 60000]);

// Run auto-payout job
ProcessAutoPayouts::dispatch();

// Verify payout request created
$payoutRequest = PayoutRequest::where('user_id', $teacher->id)
    ->where('status', 'pending')
    ->first();
    
expect($payoutRequest)->not->toBeNull();
expect($payoutRequest->amount)->toBe(60000.00);
```

---

## 📈 Impact on System

### What Changes When Settings Are Updated:

1. **Commission Rate**:
   - ✅ Affects all NEW session payments
   - ❌ Does NOT affect existing transactions
   - 📊 Stored per-session for audit trail

2. **Auto-Payout Threshold**:
   - ✅ Affects next scheduled run (daily at 10:00 AM)
   - ✅ Can be triggered manually anytime
   - 📊 Only processes eligible teachers

3. **Minimum Withdrawal Amount**:
   - ✅ Validates all NEW withdrawal requests
   - ❌ Does NOT affect pending requests
   - 📊 Displayed to teachers on withdrawal page

4. **Bank Verification**:
   - ✅ Enforced on NEW withdrawal requests
   - ❌ Does NOT affect pending requests
   - 📊 Teachers must verify before withdrawing

5. **Withdrawal Note**:
   - ✅ Displayed immediately to teachers
   - 📊 Shown on withdrawal request page

---

## 🛠️ Maintenance

### Monitoring Auto-Payouts
```bash
# Check logs
tail -f storage/logs/laravel.log | grep "Auto Payout"

# View scheduled tasks
php artisan schedule:list

# Test schedule
php artisan schedule:test
```

### Clearing Settings Cache
```php
use App\Models\FinancialSetting;

// Clear all settings cache
FinancialSetting::clearCache();
```

---

## 🎉 Summary

All payment settings features are now fully integrated and functional:

✅ Commission deductions from teacher earnings  
✅ Auto-payout processing based on threshold  
✅ Bank verification requirements  
✅ Withdrawal notes displayed to users  
✅ Admin UI for managing all settings  
✅ Scheduled tasks configured (Laravel 12 style)  
✅ Comprehensive logging and monitoring  

The system is production-ready and all settings will affect the platform immediately upon update!
