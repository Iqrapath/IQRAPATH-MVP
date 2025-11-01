# Email Troubleshooting Guide - IQRAPATH

## Current Issue: Emails Not Being Sent

### Problem Analysis

1. **Mail Configuration**: Currently using Mailtrap (testing service)
2. **Queue System**: Using database queue with failed notification jobs
3. **Failed Jobs**: Multiple `SendQueuedNotifications` jobs failing

### Root Causes

#### 1. Mailtrap Configuration
- Mailtrap is a **testing service** that captures emails but doesn't send them to real recipients
- Good for development, but emails won't reach actual users
- Current config: `MAIL_MAILER=smtp` with Mailtrap credentials

#### 2. Queue Worker Not Running
- Emails are queued but not being processed
- Queue worker needs to be running continuously
- Failed jobs indicate processing errors

#### 3. Gmail Configuration (Commented Out)
- Gmail SMTP is configured but commented out in `.env`
- Gmail requires app-specific password, not regular password
- Current password appears to be app password: `jgwv cfju nltw xmnk`

## Solutions

### Solution 1: Use Gmail for Real Emails (Recommended for Production)

#### Step 1: Update `.env` file
```env
# Comment out Mailtrap
# MAIL_MAILER=smtp
# MAIL_HOST=sandbox.smtp.mailtrap.io
# MAIL_PORT=2525
# MAIL_USERNAME=56bd5cbe001283
# MAIL_PASSWORD=367f4f16c9be15

# Enable Gmail SMTP
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=nanaotoo77@gmail.com
MAIL_PASSWORD="jgwv cfju nltw xmnk"
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=nanaotoo77@gmail.com
MAIL_FROM_NAME="IQRAQUEST"
```

#### Step 2: Clear config cache
```bash
php artisan config:clear
php artisan cache:clear
```

#### Step 3: Start queue worker
```bash
# For development (run in separate terminal)
php artisan queue:work

# For production (use supervisor or systemd)
php artisan queue:work --daemon
```

#### Step 4: Retry failed jobs
```bash
php artisan queue:retry all
```

### Solution 2: Use Mailtrap for Testing (Current Setup)

If you want to keep testing with Mailtrap:

#### Step 1: Check Mailtrap inbox
- Login to https://mailtrap.io
- Check your inbox for captured emails
- Emails won't go to real recipients

#### Step 2: Start queue worker
```bash
php artisan queue:work
```

#### Step 3: Test email sending
```bash
php artisan tinker
```
```php
Mail::raw('Test email', function($message) {
    $message->to('test@example.com')->subject('Test');
});
```

### Solution 3: Use Alternative Email Services

#### Option A: SendGrid
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your_sendgrid_api_key
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your@email.com
MAIL_FROM_NAME="IQRAQUEST"
```

#### Option B: Mailgun
```env
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=your-domain.mailgun.org
MAILGUN_SECRET=your_mailgun_api_key
MAIL_FROM_ADDRESS=your@email.com
MAIL_FROM_NAME="IQRAQUEST"
```

#### Option C: Amazon SES
```env
MAIL_MAILER=ses
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_DEFAULT_REGION=us-east-1
MAIL_FROM_ADDRESS=your@email.com
MAIL_FROM_NAME="IQRAQUEST"
```

## Queue Worker Setup

### Development (Windows)

#### Option 1: Manual (Simple)
```bash
# Run in separate terminal
php artisan queue:work
```

#### Option 2: Task Scheduler (Automated)
1. Open Task Scheduler
2. Create new task
3. Trigger: At startup
4. Action: Start program
   - Program: `C:\xampp\php\php.exe`
   - Arguments: `artisan queue:work`
   - Start in: `C:\xampp\htdocs\IQRAPATH-final`

### Production (Linux)

#### Using Supervisor (Recommended)
```bash
# Install supervisor
sudo apt-get install supervisor

# Create config file
sudo nano /etc/supervisor/conf.d/iqrapath-worker.conf
```

```ini
[program:iqrapath-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/iqrapath/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/iqrapath/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
# Start supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start iqrapath-worker:*
```

## Debugging Email Issues

### Check Mail Configuration
```bash
php artisan tinker
```
```php
config('mail.default');
config('mail.mailers.smtp');
config('mail.from');
```

### Test Email Sending
```bash
php artisan tinker
```
```php
use Illuminate\Support\Facades\Mail;

Mail::raw('Test email content', function($message) {
    $message->to('your@email.com')
            ->subject('Test Email from IQRAPATH');
});
```

### Check Queue Status
```bash
# List failed jobs
php artisan queue:failed

# Check jobs table
php artisan tinker
```
```php
DB::table('jobs')->count();
DB::table('failed_jobs')->count();
```

### View Failed Job Details
```bash
php artisan tinker
```
```php
$failed = DB::table('failed_jobs')->latest('failed_at')->first();
echo $failed->exception;
```

### Clear Failed Jobs
```bash
# Retry all failed jobs
php artisan queue:retry all

# Flush all failed jobs
php artisan queue:flush
```

## Common Email Errors

### Error: "Connection refused"
**Cause**: SMTP server not reachable
**Solution**: Check MAIL_HOST and MAIL_PORT

### Error: "Authentication failed"
**Cause**: Invalid credentials
**Solution**: 
- For Gmail: Use app-specific password
- Check MAIL_USERNAME and MAIL_PASSWORD

### Error: "SSL certificate problem"
**Cause**: SSL verification issues
**Solution**: Update PHP OpenSSL or disable verification (not recommended)

### Error: "Emails queued but not sent"
**Cause**: Queue worker not running
**Solution**: Start queue worker with `php artisan queue:work`

## Email Testing Checklist

- [ ] Mail configuration is correct in `.env`
- [ ] Config cache is cleared
- [ ] Queue worker is running
- [ ] Failed jobs are retried or flushed
- [ ] Test email sent successfully
- [ ] Email received in inbox (not spam)
- [ ] From address and name are correct
- [ ] Email templates render correctly

## Monitoring Email Delivery

### Log Email Activity
```php
// In AppServiceProvider boot method
Mail::alwaysTo('admin@iqrapath.com');
```

### Track Email Opens (Optional)
Consider using services like:
- SendGrid Email Activity
- Mailgun Analytics
- Postmark Message Streams

## Production Recommendations

1. **Use Professional Email Service**
   - SendGrid, Mailgun, or Amazon SES
   - Better deliverability than Gmail
   - Built-in analytics and monitoring

2. **Set Up Queue Worker Monitoring**
   - Use Supervisor (Linux) or Task Scheduler (Windows)
   - Monitor with Laravel Horizon (optional)
   - Set up alerts for failed jobs

3. **Configure Email Verification**
   - SPF records
   - DKIM signing
   - DMARC policy

4. **Test Email Deliverability**
   - Use mail-tester.com
   - Check spam score
   - Verify DNS records

## Quick Fix Commands

```bash
# Complete email system reset
php artisan config:clear
php artisan cache:clear
php artisan queue:flush
php artisan queue:restart

# Start fresh
php artisan queue:work

# Test email
php artisan tinker
Mail::raw('Test', fn($m) => $m->to('test@example.com')->subject('Test'));
```

## Next Steps

1. **Immediate**: Switch to Gmail or keep Mailtrap for testing
2. **Short-term**: Set up queue worker to run continuously
3. **Long-term**: Migrate to professional email service (SendGrid/Mailgun)
4. **Production**: Set up Supervisor for queue worker management
