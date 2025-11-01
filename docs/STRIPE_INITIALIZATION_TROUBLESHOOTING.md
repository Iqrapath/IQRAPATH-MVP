# Stripe Initialization Timeout - Troubleshooting Guide

## Error Message
"Payment system initialization timeout. Please refresh the page."

## Common Causes & Solutions

### 1. Stripe.js Script Not Loading

**Check:**
```html
<!-- Should be in resources/views/app.blade.php -->
<script src="https://js.stripe.com/v3/"></script>
```

**Solution:**
- Verify the script tag is present in the layout
- Check browser console for script loading errors
- Check if Content Security Policy (CSP) is blocking Stripe.js
- Try accessing https://js.stripe.com/v3/ directly in browser

### 2. Network Issues

**Symptoms:**
- Slow internet connection
- Firewall blocking Stripe.js
- Ad blocker blocking scripts

**Solution:**
- Disable ad blockers
- Check firewall settings
- Try different network

### 3. Browser Console Errors

**Check browser console for:**
```
[Stripe Init] Starting initialization...
[Stripe Init] Waiting for Stripe.js... (attempt 1/20)
[Stripe Init] Stripe.js loaded successfully
[Stripe Init] Fetching publishable key from: /student/payment/publishable-key
[Stripe Init] Received response: {...}
[Stripe Init] Initializing Stripe with key: pk_test_...
[Stripe Init] Creating Elements...
[Stripe Init] Initialization complete!
```

**If you see:**
- "Waiting for Stripe.js..." repeating 20 times → Stripe.js not loading
- Error after "Fetching publishable key" → Backend issue
- No logs at all → Modal not opening or JavaScript error

### 4. Backend Issues

**Check if endpoint is accessible:**
```bash
# Test the endpoint (must be logged in)
curl http://localhost:8000/student/payment/publishable-key \
  -H "Accept: application/json" \
  -H "Cookie: your_session_cookie"
```

**Expected response:**
```json
{
  "publishable_key": "pk_test_51S3BQxQcJaBCr0hw..."
}
```

**If you get 401 Unauthorized:**
- User is not logged in
- Session expired
- CSRF token issue

**If you get 500 Internal Server Error:**
- Check Laravel logs: `storage/logs/laravel.log`
- Stripe key not configured in `.env`

### 5. Environment Configuration

**Check .env file:**
```env
STRIPE_PUBLISHABLE_KEY=pk_test_51S3BQxQcJaBCr0hw...
STRIPE_SECRET_KEY=sk_test_51S3BQxQcJaBCr0hw...
STRIPE_WEBHOOK_SECRET=whsec_...
```

**Verify keys are valid:**
- Publishable key starts with `pk_test_` (test) or `pk_live_` (production)
- Secret key starts with `sk_test_` (test) or `sk_live_` (production)
- Keys match (both test or both live)

### 6. Cache Issues

**Clear all caches:**
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

**Clear browser cache:**
- Hard refresh: Ctrl+Shift+R (Windows) or Cmd+Shift+R (Mac)
- Clear browser cache completely
- Try incognito/private mode

### 7. JavaScript Errors

**Check for JavaScript errors:**
- Open browser DevTools (F12)
- Go to Console tab
- Look for red error messages
- Check if any errors occur before Stripe initialization

**Common errors:**
- `axios is not defined` → axios not loaded
- `window.Stripe is not a function` → Stripe.js not loaded properly
- `Cannot read property 'get' of undefined` → axios issue

## Debugging Steps

### Step 1: Check Browser Console
1. Open DevTools (F12)
2. Go to Console tab
3. Open the Fund Account modal
4. Look for `[Stripe Init]` logs
5. Note where it fails

### Step 2: Check Network Tab
1. Open DevTools (F12)
2. Go to Network tab
3. Filter by "XHR" or "Fetch"
4. Open the Fund Account modal
5. Look for request to `/student/payment/publishable-key`
6. Check response status and body

### Step 3: Check Stripe.js Loading
1. Open DevTools (F12)
2. Go to Network tab
3. Filter by "JS"
4. Refresh page
5. Look for `v3/` (Stripe.js)
6. Check if it loads successfully (status 200)

### Step 4: Check Laravel Logs
```bash
# View last 50 lines
Get-Content storage/logs/laravel.log -Tail 50

# Watch logs in real-time
Get-Content storage/logs/laravel.log -Wait -Tail 50
```

Look for errors related to:
- Stripe
- Payment
- publishable-key

### Step 5: Test Stripe Key
```bash
php artisan tinker
```
```php
config('services.stripe.publishable_key');
// Should output: pk_test_51S3BQxQcJaBCr0hw...

config('services.stripe.secret_key');
// Should output: sk_test_51S3BQxQcJaBCr0hw...
```

## Quick Fixes

### Fix 1: Increase Timeout
If initialization is just slow, increase the timeout:

```typescript
// In FundAccountModal.tsx
const PAYMENT_CONFIG = {
    // ... other config
    STRIPE_INIT_TIMEOUT: 20000, // Increase from 10000 to 20000 (20 seconds)
} as const;
```

### Fix 2: Force Stripe.js Reload
Add this to the modal:

```typescript
useEffect(() => {
    // Force reload Stripe.js if not loaded
    if (!window.Stripe && isOpen) {
        const script = document.createElement('script');
        script.src = 'https://js.stripe.com/v3/';
        script.async = true;
        document.head.appendChild(script);
    }
}, [isOpen]);
```

### Fix 3: Fallback to Synchronous Loading
Change Stripe.js script to synchronous:

```html
<!-- Remove async attribute -->
<script src="https://js.stripe.com/v3/"></script>
```

### Fix 4: Preload Stripe.js
Add preload hint:

```html
<link rel="preload" href="https://js.stripe.com/v3/" as="script">
<script src="https://js.stripe.com/v3/"></script>
```

## Production Checklist

Before deploying to production:

- [ ] Verify Stripe.js loads on all pages
- [ ] Test with slow 3G network simulation
- [ ] Test with ad blockers enabled
- [ ] Test in incognito mode
- [ ] Test on different browsers (Chrome, Firefox, Safari, Edge)
- [ ] Test on mobile devices
- [ ] Verify publishable key is production key (pk_live_)
- [ ] Monitor error logs for initialization failures
- [ ] Set up alerts for high error rates

## Still Having Issues?

1. **Check browser console** - Look for `[Stripe Init]` logs
2. **Check network tab** - Verify requests are being made
3. **Check Laravel logs** - Look for backend errors
4. **Try different browser** - Rule out browser-specific issues
5. **Try incognito mode** - Rule out extension conflicts
6. **Clear all caches** - Both browser and Laravel
7. **Restart dev server** - Sometimes helps with hot reload issues

## Contact Support

If none of the above works, provide:
- Browser console logs (with `[Stripe Init]` messages)
- Network tab screenshot
- Laravel log errors
- Browser and OS version
- Steps to reproduce
