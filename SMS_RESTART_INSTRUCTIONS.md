# SMS Service Restart Instructions

## Important: Server Restart Required

The SMS service code has been updated to use the correct HostPinnacle API endpoints. **The error logs show that the old code is still running**, which means you need to restart your application server.

## Steps to Apply Changes

### 1. Clear All Caches
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### 2. Restart Your Application Server

**If using PHP-FPM:**
```bash
# Restart PHP-FPM service
sudo service php-fpm restart
# or
sudo systemctl restart php-fpm
```

**If using Apache:**
```bash
sudo service apache2 restart
# or
sudo systemctl restart apache2
```

**If using Nginx with PHP-FPM:**
```bash
sudo service nginx restart
sudo service php-fpm restart
```

**If using Laravel Queue Workers:**
```bash
php artisan queue:restart
```

**If using Supervisor (for queue workers):**
```bash
sudo supervisorctl restart all
# or restart specific workers
sudo supervisorctl restart laravel-worker:*
```

### 3. Clear OPcache (if enabled)
If you have OPcache enabled, you may need to clear it:
```bash
# Via PHP script
php -r "opcache_reset();"
```

Or restart PHP-FPM which will clear OPcache.

### 4. Verify New Code is Running

After restarting, check your logs. You should see:
- New error messages like: `"Account status check failed - HTTP error"` (not the old "Account status check failed from all endpoints")
- Endpoint: `GET /SMSApi/account/readstatus` (not the old `/readAccountStatus`)
- Log entry: `"code_version": "v2.0 - Using correct HostPinnacle endpoint"`

## What Changed

### Old Code (Still Running - Based on Error Logs)
- Used POST method with API key
- Tried multiple incorrect endpoints: `/readAccountStatus`, `/accountStatus`, etc.
- Error message: "Account status check failed from all endpoints"

### New Code (After Restart)
- Uses GET method without API key (per HostPinnacle docs)
- Uses correct endpoint: `GET /SMSApi/account/readstatus`
- Fallback: `POST /SMSApi/account/readcredithistory`
- Error message: "Account status check failed - HTTP error"

## Testing After Restart

1. **Send a test SMS** - The system will automatically try to check balance
2. **Check logs** - Look for the new error message format
3. **Verify endpoint** - Should see `/account/readstatus` in logs, not `/readAccountStatus`

## If Errors Persist After Restart

If you still see 404 errors after restarting, it means:
1. The endpoint path might need adjustment (contact HostPinnacle)
2. Additional parameters might be required
3. There might be account/permission issues

The enhanced logging will now show the exact response from HostPinnacle, making it easier to diagnose.

## Quick Check Command

Run this to verify the code is updated:
```bash
grep -n "account/readstatus" app/Services/SMSService.php
```

You should see the endpoint in the `getAccountStatus()` method.
