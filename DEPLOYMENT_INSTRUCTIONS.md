# Production Deployment Instructions

## Overview
This deployment adds balance brought forward (BBF) tracking to the fee balance report and fixes critical C2B transaction ID conflicts.

## Pre-Deployment Checklist
- [ ] Backup your database
- [ ] Ensure you have SSH access to production server
- [ ] Verify you have database access credentials
- [ ] Schedule maintenance window if needed (recommended)

---

## Step-by-Step Deployment Instructions

### Step 1: Backup Database
```bash
# On production server, create a database backup
mysqldump -u [username] -p [database_name] > backup_$(date +%Y%m%d_%H%M%S).sql

# Or if using Laravel backup command
php artisan backup:run
```

### Step 2: Pull Latest Code
```bash
# Navigate to your project directory
cd /path/to/school-management-system2

# Pull the latest changes
git pull origin main

# Verify the commit is present
git log -1 --oneline
# Should show: "Add balance brought forward tracking to fee balance report and fix C2B transaction ID conflicts"
```

### Step 3: Install Dependencies (if needed)
```bash
# Update composer dependencies
composer install --no-dev --optimize-autoloader

# Clear and cache config
php artisan config:clear
php artisan config:cache
```

### Step 4: Run Database Migrations
```bash
# Run any new migrations (if any were added)
php artisan migrate --force
```

### Step 5: Fix C2B Transaction ID Conflicts (CRITICAL)

**IMPORTANT:** This step resolves 77 ID conflicts between C2B and Bank Statement transactions.

#### 5.1: First, check for conflicts (dry run)
```bash
php artisan transactions:fix-c2b-id-conflicts --dry-run
```

This will show you all the conflicts without making any changes. Review the output to understand what will be fixed.

#### 5.2: Fix the conflicts
```bash
# This will reassign C2B transaction IDs to resolve conflicts
php artisan transactions:fix-c2b-id-conflicts --fix
```

**What this does:**
- Finds all C2B transactions that have the same ID as Bank Statement transactions
- Reassigns C2B transaction IDs to new unique values (starting from max existing ID + 1000)
- Updates any self-referential foreign keys (duplicate_of)
- Logs all changes for audit purposes

**Expected output:**
- Shows count of conflicts found
- Displays table of conflicts
- Asks for confirmation
- Reassigns IDs and reports success

**Note:** This operation is safe because:
- Most C2B transactions don't have payments yet
- The command handles foreign key relationships properly
- All changes are logged
- The operation runs in a transaction (rolls back on error)

### Step 6: Clear All Caches
```bash
# Clear application cache
php artisan cache:clear

# Clear route cache
php artisan route:clear
php artisan route:cache

# Clear view cache
php artisan view:clear
php artisan view:cache

# Clear config cache (already done in step 3, but ensure it's cached)
php artisan config:cache
```

### Step 7: Optimize for Production
```bash
# Optimize autoloader
composer dump-autoload --optimize --classmap-authoritative

# Optimize Laravel
php artisan optimize
```

### Step 8: Verify Deployment

#### 8.1: Check Application Status
```bash
# Check if application is running
php artisan about

# Check for any errors
tail -f storage/logs/laravel.log
```

#### 8.2: Test Key Features

1. **Fee Balance Report with BBF:**
   - Navigate to: Finance → Fee Balances
   - Verify you can see "Balance Brought Forward" column
   - Test the "With BBF" tab filter
   - Verify BBF status badges are showing correctly

2. **Balance Brought Forward Page:**
   - Navigate to: Finance → Balance Brought Forward
   - Verify values shown are static (don't change with payments)
   - Check the info note is displayed

3. **C2B Transaction Assignment:**
   - Navigate to: Finance → Bank Statements
   - Try to assign a C2B transaction
   - Verify no ID conflict errors occur
   - Verify transaction assignment works correctly

#### 8.3: Check for Errors
```bash
# Monitor logs for any errors
tail -f storage/logs/laravel.log | grep -i "error\|exception\|conflict"
```

### Step 9: Post-Deployment Verification

1. **Database Integrity:**
   ```bash
   # Verify no duplicate IDs exist
   php artisan transactions:fix-c2b-id-conflicts --dry-run
   # Should show: "✓ No ID conflicts found. All transactions have unique IDs."
   ```

2. **Check Transaction Counts:**
   ```bash
   # Verify transaction counts are correct
   php artisan tinker
   # Then run:
   echo "C2B: " . \App\Models\MpesaC2BTransaction::count();
   echo "Bank: " . \App\Models\BankStatementTransaction::count();
   ```

3. **Test Payment Creation:**
   - Create a test payment from a transaction
   - Verify it links correctly
   - Check that reassignment validation works

---

## Rollback Instructions (if needed)

If you encounter issues and need to rollback:

### Option 1: Rollback Code Only
```bash
# Revert to previous commit
git log --oneline -10  # Find the commit before this one
git reset --hard [previous-commit-hash]
git push origin main --force  # Only if necessary

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Option 2: Restore Database Backup
```bash
# Restore from backup created in Step 1
mysql -u [username] -p [database_name] < backup_[timestamp].sql
```

**Note:** The ID conflict fix cannot be easily rolled back once applied, as it changes primary keys. However, the changes are logged, so you can manually revert if absolutely necessary.

---

## Troubleshooting

### Issue: "Transaction ID conflict detected" errors still occur
**Solution:**
- Ensure you ran `php artisan transactions:fix-c2b-id-conflicts --fix`
- Check that the command completed successfully
- Verify no new conflicts were introduced
- Clear all caches and restart queue workers if applicable

### Issue: BBF information not showing in fee balance report
**Solution:**
- Clear view cache: `php artisan view:clear`
- Verify the views were updated: check `resources/views/finance/fee_balances/`
- Check browser cache (hard refresh: Ctrl+Shift+R)

### Issue: Balance brought forward values changing with payments
**Solution:**
- Verify `BalanceBroughtForwardController` is using `original_amount` or `amount` (not outstanding balance)
- Check that `original_amount` is being set when BBF is imported/manually set
- Review logs for any errors in BBF calculation

### Issue: Cannot assign C2B transactions
**Solution:**
- Ensure `?type=c2b` parameter is in the URL
- Check that `resolveTransaction` method is working correctly
- Verify transaction exists: `php artisan tinker` then `\App\Models\MpesaC2BTransaction::find(25)`

---

## Files Changed in This Deployment

### Controllers
- `app/Http/Controllers/Finance/FeeBalanceController.php` - Added BBF tracking
- `app/Http/Controllers/Finance/BalanceBroughtForwardController.php` - Fixed to show static values
- `app/Http/Controllers/Finance/BankStatementController.php` - Fixed ID conflict resolution

### Views
- `resources/views/finance/fee_balances/index.blade.php` - Added BBF columns and filters
- `resources/views/finance/fee_balances/partials/student_row.blade.php` - Added BBF display
- `resources/views/finance/balance_brought_forward/index.blade.php` - Added info note

### Commands
- `app/Console/Commands/FixC2BTransactionIdConflicts.php` - NEW: Command to fix ID conflicts

---

## Support

If you encounter any issues during deployment:
1. Check `storage/logs/laravel.log` for detailed error messages
2. Review the troubleshooting section above
3. Verify all steps were completed in order
4. Check database backup was created successfully

---

## Summary Checklist

- [ ] Database backup created
- [ ] Code pulled from repository
- [ ] Dependencies installed
- [ ] Migrations run (if any)
- [ ] C2B ID conflicts fixed (`transactions:fix-c2b-id-conflicts --fix`)
- [ ] All caches cleared and rebuilt
- [ ] Application optimized
- [ ] Fee balance report tested
- [ ] Balance brought forward page tested
- [ ] C2B transaction assignment tested
- [ ] No errors in logs
- [ ] Deployment verified successful

---

**Deployment Date:** _______________
**Deployed By:** _______________
**Notes:** _______________
