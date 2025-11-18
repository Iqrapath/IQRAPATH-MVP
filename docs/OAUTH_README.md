# OAuth Authentication - Quick Start

## 🚀 Quick Setup

### 1. Configure Environment

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

### 2. Run Migration

```bash
php artisan migrate
```

### 3. Clear Cache

```bash
php artisan config:clear
php artisan cache:clear
```

### 4. Test OAuth

Visit: `http://localhost:8000/login` and click "Continue with Google"

## 📖 Documentation

- **Complete Guide**: [oauth-authentication.md](./oauth-authentication.md)
- **Troubleshooting**: [oauth-troubleshooting.md](./oauth-troubleshooting.md)
- **Implementation Summary**: [oauth-implementation-summary.md](./oauth-implementation-summary.md)

## 🧪 Running Tests

```bash
# All OAuth tests
php artisan test --filter=OAuth

# Specific test file
php artisan test tests/Feature/OAuthServiceTest.php
php artisan test tests/Feature/OAuthIntegrationTest.php
```

## 🔍 Quick Diagnostics

```bash
# Check configuration
php artisan tinker
>>> app(App\Services\OAuthConfigValidator::class)->validateAll()

# Check recent OAuth events
>>> App\Models\OAuthAuditLog::latest()->take(5)->get(['event', 'provider', 'email'])

# Check user OAuth status
>>> App\Models\User::where('email', 'user@example.com')->first(['provider', 'email_verified_at'])
```

## ⚠️ Common Issues

| Issue | Solution |
|-------|----------|
| redirect_uri_mismatch | Update redirect URI in Google/Facebook console |
| Email verification required | Add `email_verified_at` to User `$fillable` |
| Rate limit exceeded | Wait 60 seconds or increase limit |
| State expired | Try again (5-minute timeout) |
| No profile created | Check logs: `tail -f storage/logs/laravel.log` |

## 📊 Features

✅ Google & Facebook OAuth  
✅ Email auto-verification  
✅ Transaction-safe operations  
✅ Rate limiting (10/min)  
✅ Provider mismatch detection  
✅ Avatar caching  
✅ Comprehensive audit logging  
✅ Conditional role selection  

## 🔐 Security

- State parameter CSRF protection
- Rate limiting per IP
- Transaction atomicity
- Audit logging
- Provider validation

## 📞 Need Help?

1. Check [troubleshooting guide](./oauth-troubleshooting.md)
2. Review audit logs
3. Check Laravel logs
4. Contact development team

---

**Status:** ✅ Production Ready  
**Version:** 1.0.0  
**Last Updated:** November 18, 2025
