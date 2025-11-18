# OAuth Troubleshooting Guide

## Quick Diagnostics

### Step 1: Check Configuration

```bash
# Verify OAuth credentials are set
php artisan tinker
>>> config('services.google.client_id')
>>> config('services.facebook.client_id')
```

### Step 2: Check Audit Logs

```bash
php artisan tinker
>>> App\Models\OAuthAuditLog::latest()->take(10)->get(['event', 'provider', 'email', 'created_at'])
```

### Step 3: Check User Record

```bash
php artisan tinker
>>> App\Models\User::where('email', 'user@example.com')->first(['email', 'provider', 'email_verified_at', 'role'])
```

## Common Issues

### Issue 1: "redirect_uri_mismatch"

**Symptoms:**
- Error page from Google/Facebook
- Message: "redirect_uri_mismatch" or "Error 400"

**Causes:**
- Redirect URI in `.env` doesn't match OAuth provider settings
- Protocol mismatch (http vs https)
- Trailing slash mismatch

**Solutions:**

1. Check your `.env` file:
```env
GOOGLE_REDIRECT_URI=http://127.0.0.1:8000/auth/google/callback
```

2. Go to Google Cloud Console → Credentials
3. Add EXACT redirect URI (including protocol, port, path)
4. Wait 1-2 minutes for changes to propagate
5. Clear config cache:
```bash
php artisan config:clear
```

### Issue 2: User Redirected to Email Verification

**Symptoms:**
- OAuth user redirected to `/verify-email`
- User has `email_verified_at` set but still asked to verify

**Causes:**
- `email_verified_at` not in User model `$fillable`
- Middleware checking verification before OAuth completes

**Solutions:**

1. Check User model:
```php
protected $fillable = [
    'name',
    'email',
    'email_verified_at', // Must be here!
    // ...
];
```

2. Verify database:
```sql
SELECT email, email_verified_at FROM users WHERE email = 'user@example.com';
```

3. If null, manually set:
```bash
php artisan tinker
>>> $user = User::where('email', 'user@example.com')->first();
>>> $user->update(['email_verified_at' => now()]);
```

### Issue 3: "Call to undefined method createTeacherWallet()"

**Symptoms:**
- Error during OAuth registration
- Stack trace shows `UnifiedWalletService::createTeacherWallet()`

**Causes:**
- OnboardingController calling wrong method names
- Methods renamed in UnifiedWalletService

**Solutions:**

1. Update OnboardingController:
```php
// Wrong:
$this->walletService->createTeacherWallet($user);

// Correct:
$this->walletService->getTeacherWallet($user);
```

2. Clear compiled files:
```bash
php artisan clear-compiled
php artisan config:clear
```

### Issue 4: No Profile or Wallet Created

**Symptoms:**
- User created but `teacherProfile` is null
- User created but `teacherWallet` is null

**Causes:**
- Transaction rolled back due to error
- Profile/wallet creation failed silently

**Solutions:**

1. Check Laravel logs:
```bash
tail -f storage/logs/laravel.log
```

2. Check audit logs:
```bash
php artisan tinker
>>> OAuthAuditLog::where('event', 'error')->latest()->first()
```

3. Manually create profile/wallet:
```bash
php artisan tinker
>>> $user = User::find(123);
>>> $controller = new App\Http\Controllers\OnboardingController(app(App\Services\UnifiedWalletService::class));
>>> $controller->createUserProfileAndWallet($user, 'teacher');
```

### Issue 5: "This email is registered with Google"

**Symptoms:**
- User tries Facebook, gets error about Google
- User registered with one provider, trying another

**Causes:**
- Provider mismatch detection working correctly
- User forgot which provider they used

**Solutions:**

1. Check user's provider:
```bash
php artisan tinker
>>> User::where('email', 'user@example.com')->value('provider')
```

2. Tell user to use correct provider
3. Or link new provider (if implementing multi-provider support)

### Issue 6: Rate Limit Exceeded

**Symptoms:**
- "Too many authentication attempts" error
- 429 status code

**Causes:**
- More than 10 OAuth callbacks in 1 minute
- Testing/development causing rapid requests

**Solutions:**

1. Wait 60 seconds
2. Check audit logs:
```bash
php artisan tinker
>>> OAuthAuditLog::where('event', 'rate_limit_exceeded')->count()
```

3. Temporarily disable for testing:
```php
// In routes/auth.php
Route::middleware(['throttle.oauth:100,1'])->group(function () {
    // Increased to 100 for testing
});
```

4. Clear rate limit cache:
```bash
php artisan cache:clear
```

### Issue 7: State Parameter Expired

**Symptoms:**
- "Authentication session expired" error
- User took too long to authenticate

**Causes:**
- More than 5 minutes between initiation and callback
- Session cleared/expired

**Solutions:**

1. Try again immediately
2. Check session driver is working:
```bash
php artisan tinker
>>> session()->put('test', 'value');
>>> session()->get('test');
```

3. Increase state expiration (if needed):
```php
// In OAuthController::generateSecureState()
'expires_at' => now()->addMinutes(10)->timestamp, // Increased to 10
```

### Issue 8: Avatar Not Downloading

**Symptoms:**
- User has OAuth avatar URL but not local file
- Avatar shows broken image

**Causes:**
- AvatarService failing to download
- Storage permissions issue
- Network timeout

**Solutions:**

1. Check storage permissions:
```bash
chmod -R 775 storage/app/public
php artisan storage:link
```

2. Test avatar download:
```bash
php artisan tinker
>>> $service = app(App\Services\AvatarService::class);
>>> $service->downloadOAuthAvatar('https://example.com/avatar.jpg', 'google', '12345');
```

3. Check logs:
```bash
grep "OAuth avatar" storage/logs/laravel.log
```

### Issue 9: Role Selection Shows Wrong Options

**Symptoms:**
- Teacher option shown when it shouldn't be
- Only 2 options when 3 expected

**Causes:**
- `oauth_intended_role` session not set correctly
- Conditional logic not working

**Solutions:**

1. Check session:
```bash
php artisan tinker
>>> session()->get('oauth_intended_role')
```

2. Verify OAuth initiation:
```php
// Login page should pass no role or 'any'
route('auth.google') // defaults to 'any'

// Register teacher should pass 'teacher'
route('auth.google', ['role' => 'teacher'])

// Register student-guardian should pass 'student-guardian'
route('auth.google', ['role' => 'student-guardian'])
```

### Issue 10: OAuth Buttons Not Appearing

**Symptoms:**
- No Google/Facebook buttons on login/register pages
- Buttons disabled/grayed out

**Causes:**
- OAuth credentials not configured
- OAuthConfigValidator disabling buttons

**Solutions:**

1. Check configuration:
```bash
php artisan tinker
>>> app(App\Services\OAuthConfigValidator::class)->validateAll()
```

2. Verify credentials in `.env`:
```env
GOOGLE_CLIENT_ID=your_client_id_here
GOOGLE_CLIENT_SECRET=your_secret_here
```

3. Clear config cache:
```bash
php artisan config:clear
```

## Debugging Tools

### Enable Debug Mode

```env
APP_DEBUG=true
LOG_LEVEL=debug
```

### Watch Logs in Real-Time

```bash
tail -f storage/logs/laravel.log | grep -i oauth
```

### Check Database State

```sql
-- Check recent OAuth users
SELECT id, name, email, provider, role, email_verified_at, created_at
FROM users
WHERE provider IS NOT NULL
ORDER BY created_at DESC
LIMIT 10;

-- Check audit logs
SELECT event, provider, email, created_at
FROM oauth_audit_logs
ORDER BY created_at DESC
LIMIT 20;

-- Check for orphaned users (no profile)
SELECT u.id, u.email, u.role
FROM users u
LEFT JOIN teacher_profiles tp ON u.id = tp.user_id
WHERE u.role = 'teacher' AND tp.id IS NULL;
```

### Test OAuth Flow Manually

```bash
# 1. Start session
php artisan tinker
>>> session()->put('oauth_state_test123', ['intended_role' => 'teacher', 'expires_at' => now()->addMinutes(5)->timestamp]);

# 2. Visit callback URL
# http://localhost:8000/auth/google/callback?state=test123&code=fake_code

# 3. Check result
>>> User::latest()->first()
```

## Prevention

### Before Deployment

- [ ] Test all OAuth flows (login, register teacher, register student-guardian)
- [ ] Verify redirect URIs in production
- [ ] Check rate limiting works
- [ ] Test email verification bypass
- [ ] Verify profile/wallet creation
- [ ] Test provider mismatch detection
- [ ] Check audit logging
- [ ] Test avatar downloading

### Monitoring

Set up alerts for:
- OAuth success rate < 95%
- Rate limit violations > 10/hour
- Provider mismatch attempts > 5/day
- Transaction failures > 0
- Missing profiles/wallets

### Regular Maintenance

- Review audit logs weekly
- Clean up old audit logs (>90 days)
- Update OAuth credentials annually
- Test OAuth after Laravel updates
- Monitor storage usage for avatars

## Getting Help

### Information to Provide

When reporting OAuth issues, include:

1. **Error message** (exact text)
2. **Audit log ID** (from `oauth_audit_logs`)
3. **User email** (if applicable)
4. **Provider** (Google or Facebook)
5. **Timestamp** of the issue
6. **Steps to reproduce**
7. **Laravel log excerpt**

### Useful Commands

```bash
# Get recent errors
php artisan tinker
>>> OAuthAuditLog::where('event', 'error')->latest()->take(5)->get()

# Check user state
>>> User::where('email', 'user@example.com')->first()

# Verify configuration
>>> config('services.google')

# Check middleware
>>> Route::getRoutes()->getByName('auth.google.callback')->middleware()
```

---

**Need more help?** Check the main OAuth documentation or contact the development team with the information above.
