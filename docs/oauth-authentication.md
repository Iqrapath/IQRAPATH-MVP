# OAuth Authentication System Documentation

## Overview

The IQRAQUEST OAuth authentication system provides secure, seamless social login using Google and Facebook. The system automatically verifies emails, creates user profiles and wallets, and handles complex scenarios like email collisions and provider mismatches.

## Features

✅ **Google & Facebook OAuth** - Quick registration and login  
✅ **Email Auto-Verification** - OAuth users skip email verification  
✅ **Transaction-Safe** - Atomic user/profile/wallet creation  
✅ **Provider Mismatch Detection** - Prevents account confusion  
✅ **Rate Limiting** - 10 requests per minute per IP  
✅ **State Parameter Security** - CSRF protection  
✅ **Avatar Caching** - Downloads and stores OAuth avatars  
✅ **Comprehensive Audit Logging** - Track all OAuth operations  
✅ **Conditional Role Selection** - Context-aware onboarding  

## User Flows

### 1. OAuth from Login Page

```
User clicks "Continue with Google" on login page
→ No role parameter passed (defaults to 'any')
→ User authenticated by Google
→ New user created with role='unassigned'
→ Email automatically verified
→ Redirected to role selection page
→ User chooses: Teacher, Student, or Guardian
→ Profile and wallet created
→ Redirected to appropriate dashboard/onboarding
```

### 2. OAuth from Register Teacher Page

```
User clicks "Continue with Google" on teacher registration
→ role='teacher' parameter passed
→ User authenticated by Google
→ New user created with role='teacher'
→ Email automatically verified
→ Teacher profile, wallet, and earnings created
→ Redirected to teacher onboarding
```

### 3. OAuth from Register Student-Guardian Page

```
User clicks "Continue with Google" on student-guardian registration
→ role='student-guardian' parameter passed
→ User authenticated by Google
→ New user created with role='unassigned'
→ Email automatically verified
→ Redirected to role selection page
→ User chooses: Student or Guardian (teacher option hidden)
→ Profile and wallet created
→ Redirected to appropriate dashboard
```

### 4. Existing User Login

```
Existing OAuth user clicks "Continue with Google"
→ User authenticated by Google
→ Email matches existing account
→ Provider matches stored provider
→ User logged in
→ Redirected to role-specific dashboard
```

### 5. Email Collision - Password Account

```
User with email/password account clicks OAuth
→ User authenticated by Google
→ Email matches existing account
→ No provider currently linked
→ OAuth provider linked to existing account
→ Email verified automatically
→ User logged in
→ Can now use both email/password and OAuth
```

### 6. Email Collision - Different Provider

```
User registered with Google tries Facebook
→ User authenticated by Facebook
→ Email matches existing account
→ Provider mismatch detected (Google vs Facebook)
→ Error displayed: "This email is registered with Google"
→ User redirected to login page
→ Audit log created for security monitoring
```

## API Endpoints

### OAuth Initiation

**GET /auth/google**
- Query Parameters:
  - `role` (optional): 'teacher', 'student-guardian', or 'any'
- Redirects to Google OAuth authorization page
- Generates secure state token

**GET /auth/facebook**
- Query Parameters:
  - `role` (optional): 'teacher', 'student-guardian', or 'any'
- Redirects to Facebook OAuth authorization page
- Generates secure state token

### OAuth Callbacks

**GET /auth/google/callback**
- Query Parameters:
  - `state` (required): State token from initiation
  - `code` (required): Authorization code from Google
- Rate Limited: 10 requests per minute per IP
- Returns: Redirect to appropriate page

**GET /auth/facebook/callback**
- Query Parameters:
  - `state` (required): State token from initiation
  - `code` (required): Authorization code from Facebook
- Rate Limited: 10 requests per minute per IP
- Returns: Redirect to appropriate page

## Configuration

### Environment Variables

```env
# Google OAuth
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=${APP_URL}/auth/google/callback

# Facebook OAuth
FACEBOOK_CLIENT_ID=your_facebook_app_id
FACEBOOK_CLIENT_SECRET=your_facebook_app_secret
FACEBOOK_REDIRECT_URI=${APP_URL}/auth/facebook/callback
```

### Google Cloud Console Setup

1. Go to https://console.cloud.google.com/apis/credentials
2. Create OAuth 2.0 Client ID
3. Add authorized redirect URIs:
   - `http://localhost:8000/auth/google/callback` (development)
   - `https://yourdomain.com/auth/google/callback` (production)
4. Copy Client ID and Client Secret to `.env`

### Facebook Developer Setup

1. Go to https://developers.facebook.com/apps
2. Create new app or select existing
3. Add Facebook Login product
4. Configure OAuth redirect URIs:
   - `http://localhost:8000/auth/facebook/callback` (development)
   - `https://yourdomain.com/auth/facebook/callback` (production)
5. Copy App ID and App Secret to `.env`

## Security Features

### State Parameter Validation

- Cryptographically secure random tokens (32 characters)
- Stored in session with 5-minute expiration
- One-time use (deleted after validation)
- Prevents CSRF attacks

### Rate Limiting

- 10 requests per minute per IP address
- 429 status code when exceeded
- Retry-After header included
- Violations logged to audit log

### Provider Mismatch Detection

- Checks if email exists with different provider
- Prevents account confusion
- Clear error messages
- Security events logged

### Transaction Safety

- All operations wrapped in database transaction
- Rollback on any failure
- No orphaned accounts
- Detailed error logging

## Error Handling

### Common Errors

**"Authentication session expired"**
- Cause: State token expired (>5 minutes)
- Solution: Try signing in again

**"This email is registered with Google"**
- Cause: Attempting Facebook login with Google-registered email
- Solution: Use Google to sign in

**"Email not provided by OAuth provider"**
- Cause: OAuth provider didn't return email
- Solution: Check OAuth provider permissions

**"Too many authentication attempts"**
- Cause: Rate limit exceeded
- Solution: Wait 60 seconds and try again

### Error Response Format

```json
{
    "oauth": "User-friendly error message"
}
```

## Audit Logging

All OAuth operations are logged to the `oauth_audit_logs` table:

### Event Ty`initiated` - OAuth flow started
- `callback_success` - Authentication successful
- `callback_failure` - Authentication failed
- `account_linked` - OAuth provider linked to existing account
- `provider_mismatch` - Attempted login with wrong provider
- `rate_limit_exceeded` - Rate limit hit
- `error` - General error occurred

### Audit Log Fields

```php
- user_id: User ID (if applicable)
- event: Event type
- provider: 'google' or 'facebook'
- provider_id: OAuth provider's user ID
- email: User's email address
- intended_role: Role parameter from OAuth initiation
- metadata: Additional context (JSON)
- ip_address: Request IP address
- user_agent: Browser user agent
- created_at: Timestamp
```

### Querying Audit Logs

```php
// Get all OAuth events for a user
$logs = OAuthAuditLog::where('user_id', $userId)->get();

// Get provider mismatch attempts
$mismatches = OAuthAuditLog::where('event', 'provider_mismatch')->get();

// Get rate limit violations
$violations = OAuthAuditLog::where('event', 'rate_limit_exceeded')
    ->where('created_at', '>=', now()->subDay())
    ->get();
```

## Testing

### Running Tests

```bash
# Run all OAuth tests
php artisan test --filter=OAuth

# Run specific test file
php artisan test tests/Feature/OAuthServiceTest.php

# Run with coverage
php artisan test --coverage --filter=OAuth
```

### Test Coverage

- ✅ Provider data validation
- ✅ Email collision handling
- ✅ User creation and initialization
- ✅ Transaction atomicity
- ✅ Audit logging
- ✅ Redirect logic
- ✅ Rate limiting
- ✅ State validation
- ✅ Complete integration flows

## Troubleshooting

### OAuth Button Not Working

1. Check `.env` configuration
2. Verify OAuth credentials are correct
3. Check redirect URIs match exactly
4. Clear config cache: `php artisan config:clear`
5. Check browser console for JavaScript errors

### "redirect_uri_mismatch" Error

1. Check `GOOGLE_REDIRECT_URI` in `.env`
2. Verify it matches Google Cloud Console exactly
3. Ensure protocol matches (http vs https)
4 for trailing slashes

### User Created But No Profile/Wallet

1. Check `storage/logs/laravel.log` for errors
2. Verify `UnifiedWalletService` methods exist
3. Check database transaction logs
4. Run: `php artisan queue:work` if using queues

### Email Not Verified After OAuthheck `email_verified_at` is in User model `$fillable`
2. Verify `OAuthService` sets `email_verified_at => now()`
3. Check database record directly
4. Clear application cache

### Rate Limit Issues

1. Check `ThrottleOAuthRequests` middleware is registered
middleware is applied to OAuth routes
3. Check Redis/cache driver is working
4. Review audit logs for violations

## Best Practices

### For Developers

1. **Always use transactions** for OAuth oper **Log all OAuth events** to audit log
3. **Validate provider data** before processing
4. **Handle errors gracefully** with user-friendly messages
5. **Test all OAuth flows** thoroughly

### For Administrators

1. **Monitor audit logs** regularly
2. **Set up alerts** for suspicious activity
3. **Review rate limit violations**
4. **Keep OAuth credentials secure**
5. **Test OAuth after deployments**

### For Users

1. **Use OAuth for faster registration**
2. **Link multiple providers** for flexibility
3. **Set a password** as backup authentication
4. **Use the same provider** for subsequent logins
5. **Contact support** if issues persist

## Maintenance

### Regular Tasks

- Review audit logs weekly
- Monitor OAuth success rates
- Update OAuth credentials annually
- Test OAuth flows after updates
- Clean up old audit logs (>90 days)

### Monitoring Queries

```sql
-- OAuth success rate (last 24 hours)
SELECT 
    provider,
    COUNT(CASE WHEN event = 'callback_success' THEN 1 END) as success,
    COUNT(CASE WHEN event = 'error' THEN 1 END) as errors,
    ROUND(COUNT(CASE WHEN event = 'callback_success' THEN 1 END) * 100.0 / COUNT(*), 2) as success_rate
FROM oauth_audit_logs
WHERE created_at >= NOW() - INTERVAL 24 HOUR
GROUP BY provider;

-- Provider mismatch attempts
SELECT email, provider, COUNT(*) as attempts
FROM oauth_audit_logs
WHERE event = 'provider_mismatch'
AND created_at >= NOW() - INTERVAL 7 DAY
GROUP BY email, provider
HAVING attempts > 3;
```

## Support

For issues or questions:
- Check this documentation first
- Review audit logs for errors
- Check Laravel logs: `storage/logs/laravel.log`
- Contact development team with audit log IDs

---

**Last Updated:** November 18, 2025  
**Version:** 1.0.0
