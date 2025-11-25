# Deployment Guide

**CRITICAL:** This guide contains procedures for deploying to staging and production environments. Follow all safety procedures to prevent data loss.

## Pre-Deployment Checklist

- [ ] All tests passing locally
- [ ] Code review completed
- [ ] Database backup created and verified
- [ ] Migration files reviewed for safety
- [ ] Environment variables updated
- [ ] Feature flags configured (if applicable)
- [ ] Rollback plan documented

## Environment Setup

### Staging Environment

1. **Create staging database:**
   ```bash
   mysql -u root -p
   CREATE DATABASE school_management_staging CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   EXIT;
   ```

2. **Configure staging `.env`:**
   ```env
   APP_ENV=staging
   APP_DEBUG=false
   DB_DATABASE=school_management_staging
   # ... other staging-specific values
   ```

### Production Environment

**NEVER** modify production environment directly. All changes must go through staging first.

## Deployment Process

### Step 1: Create Database Backup

**MANDATORY:** Always backup before deployment.

```bash
# Backup production database
./scripts/backup-db.sh production

# Verify backup
ls -lh backups/
# Check backup file size (should be > 1KB)
# Verify checksum file exists
```

**Backup Verification:**
- File size > 1KB
- Checksum file present
- Metadata file present
- Backup timestamped correctly

### Step 2: Deploy Code

```bash
# Pull latest code
git pull origin main

# Install/update dependencies
composer install --no-dev --optimize-autoloader
npm ci --production

# Build assets
npm run build
```

### Step 3: Run Migrations

**CRITICAL:** Only run migrations after backup verification.

```bash
# Check pending migrations
php artisan migrate:status

# Run migrations (non-destructive only)
php artisan migrate

# If any migration is destructive, STOP and get approval first
```

**Migration Safety Rules:**
1. Review all migration files before running
2. Destructive migrations require manual approval
3. Use feature flags for risky changes
4. Test migrations on staging first

### Step 4: Clear Caches

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### Step 5: Run Post-Deployment Checks

```bash
# Health check script (to be created)
./scripts/health-check.sh

# Or manually check:
# - Application loads
# - Database connection works
# - Key endpoints respond
# - User login works
```

### Step 6: Monitor

Monitor application logs and error tracking (Sentry, etc.) for 30 minutes after deployment.

## Rollback Procedure

If deployment fails or issues are detected:

### 1. Stop Deployment

```bash
# If migration is in progress, it may need manual intervention
# Check migration status
php artisan migrate:status
```

### 2. Restore Database (if needed)

```bash
# Identify backup file
ls -lh backups/

# Restore from backup
# MySQL:
mysql -u root -p school_management < backups/backup_file.sql

# PostgreSQL:
pg_restore -d school_management backups/backup_file.dump
```

### 3. Revert Code

```bash
# Revert to previous commit
git revert HEAD
# Or checkout previous tag
git checkout <previous-tag>
```

### 4. Clear Caches

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## Environment Variables

### Required Variables

Add these to your environment (`.env` or secret manager):

```env
# Application
APP_NAME="School Management System"
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=school_management
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=school@example.com
MAIL_FROM_NAME="${APP_NAME}"

# SMS Configuration (HostPinnacle)
SMS_PROVIDER=HOSTPINNACLE
SMS_API_URL=https://smsportal.hostpinnacle.co.ke/SMSApi/send
SMS_API_KEY=your_api_key
SMS_USER_ID=your_user_id
SMS_PASSWORD=your_password
SMS_SENDER_ID=YOUR_SCHOOL

# Payment Gateway (Stripe - Example)
PAYMENT_STRIPE_KEY=pk_live_xxx
PAYMENT_STRIPE_SECRET=sk_live_xxx
PAYMENT_STRIPE_WEBHOOK_SECRET=whsec_xxx

# Payment Gateway (M-Pesa - Example)
MPESA_CONSUMER_KEY=your_consumer_key
MPESA_CONSUMER_SECRET=your_consumer_secret
MPESA_SHORTCODE=your_shortcode
MPESA_PASSKEY=your_passkey
MPESA_ENVIRONMENT=sandbox  # or production

# Queue
QUEUE_CONNECTION=database  # or redis, sqs, etc.

# Cache
CACHE_DRIVER=redis  # or file, database, etc.

# Session
SESSION_DRIVER=database  # or redis, file, etc.

# Error Tracking (Sentry)
SENTRY_LARAVEL_DSN=https://xxx@sentry.io/xxx
SENTRY_TRACES_SAMPLE_RATE=0.1
```

### Setting Secrets in CI/CD

**GitHub Actions:**
1. Go to Repository → Settings → Secrets and variables → Actions
2. Add each secret as a repository secret
3. Reference in workflow: `${{ secrets.SECRET_NAME }}`

**Other Platforms:**
- Use platform-specific secret management
- Never commit secrets to repository
- Rotate secrets regularly

## Database Migrations

### Safe Migration Practices

1. **Always add, never drop (initially):**
   ```php
   // ✅ Safe
   $table->string('new_column')->nullable();
   
   // ❌ Dangerous (do in separate migration after testing)
   $table->dropColumn('old_column');
   ```

2. **Use nullable for new required fields:**
   ```php
   $table->string('email')->nullable();
   // Backfill data
   // Then make required in next migration
   ```

3. **Provide default values:**
   ```php
   $table->enum('status', ['active', 'inactive'])->default('active');
   ```

### Destructive Migration Process

If a migration must drop/rename columns:

1. **Step 1:** Add new column, keep old one
2. **Deploy Step 1** to staging
3. **Backfill data** from old to new column
4. **Test thoroughly**
5. **Step 2:** Switch code to use new column
6. **Deploy Step 2** to staging
7. **Monitor for issues**
8. **Step 3:** Drop old column (separate migration, requires approval)
9. **Deploy Step 3** after explicit approval

## Feature Flags

For risky features, use feature flags:

```php
// In config/feature-flags.php
return [
    'new_payment_gateway' => env('FEATURE_NEW_PAYMENT_GATEWAY', false),
    'biometric_attendance' => env('FEATURE_BIOMETRIC_ATTENDANCE', false),
];

// In code
if (config('feature-flags.new_payment_gateway')) {
    // New code
} else {
    // Old code
}
```

## Monitoring & Health Checks

### Application Health

Check these endpoints after deployment:

- `/health` - Basic health check (to be implemented)
- `/api/status` - API status
- Login page loads
- Dashboard loads for authenticated users

### Database Health

```bash
# Check database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Check migration status
php artisan migrate:status
```

### Log Monitoring

```bash
# Watch error logs
tail -f storage/logs/laravel.log

# Check for critical errors
grep -i "error\|exception\|fatal" storage/logs/laravel.log
```

## Troubleshooting

### Migration Fails

1. Check error message
2. Verify database backup exists
3. Check migration file syntax
4. Verify database user has permissions
5. If safe, rollback: `php artisan migrate:rollback`

### Application Won't Start

1. Check `.env` file exists and is valid
2. Verify `APP_KEY` is set
3. Check file permissions: `storage/` and `bootstrap/cache/` writable
4. Clear caches: `php artisan config:clear`

### Database Connection Issues

1. Verify database credentials in `.env`
2. Check database server is running
3. Verify network connectivity
4. Check firewall rules

## Security Checklist

- [ ] `APP_DEBUG=false` in production
- [ ] Strong `APP_KEY` generated
- [ ] Database credentials secure
- [ ] API keys in secret manager, not code
- [ ] HTTPS enabled
- [ ] File permissions correct (storage writable, not executable)
- [ ] Error tracking configured (Sentry, etc.)
- [ ] Regular security updates applied

## Post-Deployment

1. **Monitor logs** for 30 minutes
2. **Test critical flows:**
   - User login
   - Student creation
   - Payment processing
   - Report generation
3. **Check error tracking** for new errors
4. **Verify backups** are still running
5. **Update deployment log** in `AUTOMATION_REPORT.md`

## Emergency Contacts

- **Database Admin:** [Contact]
- **DevOps:** [Contact]
- **Lead Developer:** [Contact]

## Additional Resources

- `DEVELOPMENT.md` - Local development setup
- `DB_README.md` - Database schema documentation
- `AUTOMATION_REPORT.md` - Current project status and issues

