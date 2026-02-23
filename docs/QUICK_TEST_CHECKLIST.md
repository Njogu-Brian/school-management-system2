# Quick Test Checklist Before Production

## ⚡ Quick Tests (5 minutes)

### 1. Database Migrations ✅
```bash
php artisan migrate
```
**Check:** Both migrations completed successfully

### 2. Routes Working ✅
```bash
php artisan route:list --name=payments.history
php artisan route:list --name=bank-statements.history
```
**Check:** Both routes appear in list

### 3. Authorization Test (2 minutes)
1. Login as **Teacher** (non-finance role)
2. Navigate to any payment: `/finance/payments/{id}`
3. Try to click "Reverse Payment"
4. **Expected:** Should be blocked (403 or redirect with error)

### 4. Audit Log Test (2 minutes)
1. Login as **Finance Officer/Admin**
2. Reverse a test payment
3. Check database:
```sql
SELECT * FROM audit_logs 
WHERE event = 'payment_reversed' 
ORDER BY created_at DESC LIMIT 1;
```
4. **Expected:** Log entry exists with your user ID

### 5. History View Test (1 minute)
1. Navigate to: `/finance/payments/{payment_id}/history`
2. **Expected:** Page loads, shows audit trail

## ✅ All Tests Passed?

If all 5 tests pass, you're ready for production!

## 📋 Full Testing

For comprehensive testing, see `TESTING_GUIDE.md`

## 🚨 Common Issues

**Issue:** "Policy not found"
- **Fix:** Run `php artisan config:clear`

**Issue:** "Route not found"
- **Fix:** Run `php artisan route:clear`

**Issue:** "Column not found"
- **Fix:** Run `php artisan migrate`

**Issue:** "Authorization always fails"
- **Fix:** Check user has correct role in database
