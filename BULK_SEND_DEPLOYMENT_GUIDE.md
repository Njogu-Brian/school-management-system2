# Bulk Send Payment Notifications - Deployment Guide

## Overview
This update implements a robust background job system for bulk sending payment notifications with real-time progress tracking. It solves timeout issues and provides a better user experience.

## What's New

### 1. Background Job Processing
- Bulk send operations now run as background jobs
- No more timeout errors
- Can handle hundreds of payments without issues

### 2. Real-Time Progress Tracking
- Live progress bar showing completion percentage
- Real-time counters for sent, skipped, and failed notifications
- Display of currently processing payment
- Automatic polling every second for updates

### 3. Better Error Handling
- Individual payment failures don't stop the entire process
- Detailed error logging
- Error messages displayed in the UI

## Deployment Steps

### Step 1: Run the Missing Migration

```bash
# SSH into production server
cd /home2/royalce1/laravel-app/school-management-system

# Run the migration
php artisan migrate

# This will add the 'bulk_sent_channels' column to the payments table
```

### Step 2: Clear Cache

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Step 3: Setup Queue Worker (IMPORTANT!)

The background jobs require a queue worker to be running. Choose ONE of these options:

#### Option A: Using Supervisor (Recommended for Production)

1. Create supervisor configuration file:

```bash
sudo nano /etc/supervisor/conf.d/school-erp-worker.conf
```

2. Add this configuration:

```ini
[program:school-erp-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home2/royalce1/laravel-app/school-management-system/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=royalce1
numprocs=2
redirect_stderr=true
stdout_logfile=/home2/royalce1/laravel-app/school-management-system/storage/logs/worker.log
stopwaitsecs=3600
```

3. Start supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start school-erp-worker:*
```

4. Check status:

```bash
sudo supervisorctl status school-erp-worker:*
```

#### Option B: Using Cron Job (Simple but Less Reliable)

Add this to crontab:

```bash
crontab -e
```

Add:

```
* * * * * cd /home2/royalce1/laravel-app/school-management-system && php artisan schedule:run >> /dev/null 2>&1
* * * * * cd /home2/royalce1/laravel-app/school-management-system && php artisan queue:work --stop-when-empty >> /dev/null 2>&1
```

#### Option C: Manual Testing (Development Only)

```bash
# Run this in a separate terminal/screen session
php artisan queue:work --verbose
```

### Step 4: Test the System

1. Go to Finance → Payments
2. Apply any filters
3. Click "Bulk Send All" or "Send Selected"
4. Select WhatsApp (or any channel)
5. Review the preview page
6. Click "Send Selected Payments"
7. You'll be redirected to the progress tracking page
8. Watch the real-time progress!

## How It Works

### Flow Diagram

```
User clicks "Bulk Send All"
    ↓
Preview Page (select payments to send)
    ↓
User confirms selection
    ↓
Controller dispatches background job
    ↓
User redirected to Progress Tracking Page
    ↓
Progress page polls for updates every second
    ↓
Background job processes payments
    ↓
Updates progress in cache
    ↓
Frontend displays real-time progress
    ↓
Job completes, user sees summary
```

### Technical Details

#### Files Modified/Added:

1. **New Job**: `app/Jobs/BulkSendPaymentNotifications.php`
   - Handles background processing
   - Updates progress in cache
   - Processes payments in batches
   - Prevents rate limiting with delays

2. **New View**: `resources/views/finance/payments/bulk-send-progress.blade.php`
   - Real-time progress tracking
   - Live updates via polling
   - Progress bar and counters
   - Error display

3. **Controller Updates**: `app/Http/Controllers/Finance/PaymentController.php`
   - `bulkSend()` - Dispatches background job
   - `bulkSendTracking()` - Shows progress page
   - `bulkSendProgressCheck()` - API endpoint for progress

4. **Routes**: `routes/web.php`
   - Added progress tracking routes

5. **Migration**: `database/migrations/2026_01_07_185029_add_bulk_sent_channels_to_payments_table.php`
   - Adds `bulk_sent_channels` column

## Troubleshooting

### Issue: "Column 'bulk_sent_channels' not found"

**Solution**: Run the migration
```bash
php artisan migrate
```

### Issue: Progress page shows "initializing" forever

**Solution**: Queue worker is not running. Check:
```bash
# Check if supervisor is running
sudo supervisorctl status

# Check Laravel logs
tail -f storage/logs/laravel.log

# Manually run queue worker to test
php artisan queue:work --verbose
```

### Issue: Job fails immediately

**Solution**: Check logs
```bash
tail -f storage/logs/laravel.log
```

Look for errors related to:
- Missing dependencies
- Database connection issues
- Service configuration (SMS, WhatsApp, Email)

### Issue: Payments showing as "failed" in logs

**Solution**: 
- Check if WhatsApp service is configured correctly
- Verify parent contact information exists
- Check communication service logs

## Performance Notes

- **Batch Size**: Processes 10 payments at a time
- **Delays**: 0.2 seconds between sends, 0.1 seconds between batches
- **Timeout**: Job can run for up to 1 hour
- **Memory**: Minimal memory usage due to chunking
- **Concurrent Jobs**: Can run 2 workers simultaneously

## Cache Storage

Progress data is stored in cache with:
- **Key**: `bulk_send_progress_{trackingId}`
- **TTL**: 2 hours
- **Data Stored**: Total, processed, sent, skipped, failed, current payment, errors

## Queue Configuration

The job uses Laravel's default queue driver (usually `database` or `redis`).

To check queue configuration:
```bash
cat .env | grep QUEUE
```

Expected:
```
QUEUE_CONNECTION=database
```

Or for better performance:
```
QUEUE_CONNECTION=redis
```

## Monitoring

### Check Queue Status

```bash
# See failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush
```

### Check Worker Logs

```bash
# Supervisor logs
tail -f storage/logs/worker.log

# Laravel logs
tail -f storage/logs/laravel.log
```

## Rollback Plan

If issues occur, you can rollback to synchronous processing:

1. Revert controller changes to use old `bulkSendSynchronous_OLD` method
2. Or disable bulk send temporarily

## Support

If you encounter issues:

1. Check Laravel logs: `storage/logs/laravel.log`
2. Check worker logs: `storage/logs/worker.log`
3. Verify queue worker is running
4. Test with a small batch first (5-10 payments)
5. Check database for `bulk_sent_channels` column

## Success Indicators

✅ Migration runs without errors
✅ Queue worker shows as "RUNNING" in supervisor
✅ Progress page updates every second
✅ Communications are being sent
✅ No timeout errors
✅ Payments marked with sent channels

## Next Steps After Deployment

1. Monitor the first bulk send operation closely
2. Check communication logs for delivery status
3. Verify no duplicate sends occur
4. Adjust batch size if needed for performance
5. Set up monitoring alerts for failed jobs

---

**Deployment Date**: January 8, 2026
**Version**: 2.0 - Background Job Implementation
**Critical**: Queue worker MUST be running for this feature to work!

