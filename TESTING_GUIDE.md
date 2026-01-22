# Testing Guide for Security & Enhancement Features

## Pre-Production Testing Checklist

### 1. **Database Migrations** ✅
```bash
php artisan migrate
```
**Expected:** Both migrations run successfully:
- `add_reversal_reason_to_payments_table`
- `add_version_to_payments_and_transactions`

**Verify:**
```sql
-- Check columns exist
DESCRIBE payments;
DESCRIBE bank_statement_transactions;
-- Should see: reversal_reason, version columns
```

### 2. **Authorization Tests**

#### Test 1: Payment Reversal Authorization
1. Login as a user WITHOUT finance role (e.g., Teacher)
2. Navigate to a payment detail page
3. Try to reverse a payment
4. **Expected:** Should be blocked with 403 Forbidden or redirect with error

#### Test 2: Transaction Archive Authorization
1. Login as a user WITHOUT finance role
2. Navigate to a bank statement transaction
3. Try to archive the transaction
4. **Expected:** Should be blocked

#### Test 3: Shared Allocation Edit Authorization
1. Login as a user WITHOUT finance role
2. Try to edit shared allocations
3. **Expected:** Should be blocked

**Test with authorized user:**
1. Login as Finance Officer/Accountant/Admin
2. All operations should work normally

### 3. **Audit Logging Tests**

#### Test 1: Payment Reversal Audit Log
1. Login as authorized user
2. Reverse a payment (with or without reason)
3. Check `audit_logs` table:
```sql
SELECT * FROM audit_logs 
WHERE auditable_type = 'App\\Models\\Payment' 
AND event = 'payment_reversed' 
ORDER BY created_at DESC LIMIT 1;
```
4. **Expected:** 
   - Log entry created
   - Contains old_values (amount, allocated_amount)
   - Contains new_values (reversed_by, reversed_at)
   - User ID matches current user
   - IP address recorded

#### Test 2: Transaction Archive Audit Log
1. Archive a transaction
2. Check audit logs:
```sql
SELECT * FROM audit_logs 
WHERE auditable_type = 'App\\Models\\BankStatementTransaction' 
AND event = 'transaction_archived' 
ORDER BY created_at DESC LIMIT 1;
```
3. **Expected:** Log entry with payments_reversed count

#### Test 3: Shared Allocation Edit Audit Log
1. Edit shared allocations for a payment or transaction
2. Check audit logs for `payment_shared_allocation_edited` or `transaction_shared_allocation_edited`
3. **Expected:** Old and new allocation values recorded

### 4. **Optimistic Locking Tests**

#### Test 1: Concurrent Edit Prevention
1. Open payment detail page in Browser Tab 1
2. Note the version number (check page source or network tab)
3. Open same payment in Browser Tab 2
4. In Tab 1, edit shared allocations and submit
5. In Tab 2, try to edit shared allocations and submit
6. **Expected:** Tab 2 should show error: "This payment was modified by another user. Please refresh and try again."

#### Test 2: Version Increment
1. Edit a payment's shared allocations
2. Check database:
```sql
SELECT version FROM payments WHERE id = [payment_id];
```
3. **Expected:** Version should increment by 1

### 5. **Rate Limiting Tests**

#### Test 1: Bulk Operations Rate Limit
1. Try to submit bulk operations rapidly (more than 10 times per minute)
2. **Expected:** After 10 requests, should get 429 Too Many Requests

**Test Commands:**
```bash
# Using curl to test rate limiting
for i in {1..15}; do
  curl -X POST http://your-domain/finance/payments/bulk-allocate-unallocated \
    -H "Cookie: [your-session-cookie]" \
    -w "\nStatus: %{http_code}\n"
  sleep 1
done
```

### 6. **Validation Tests**

#### Test 1: Prevent Editing Reversed Payments
1. Reverse a payment
2. Try to edit its shared allocations
3. **Expected:** Error: "Cannot edit allocations for a reversed payment."

#### Test 2: Prevent Editing if Sibling Reversed
1. Create a shared payment with siblings
2. Reverse one sibling payment
3. Try to edit shared allocations
4. **Expected:** Error about sibling payments being reversed

#### Test 3: Reversal Reason Field
1. Reverse a payment with a reason
2. Check database:
```sql
SELECT reversal_reason FROM payments WHERE id = [payment_id];
```
3. **Expected:** Reason should be stored

### 7. **Transaction History View Tests**

#### Test 1: Payment History Access
1. Navigate to: `/finance/payments/{payment_id}/history`
2. **Expected:** 
   - Page loads successfully
   - Shows payment information
   - Shows audit trail table
   - Can expand to see old/new values

#### Test 2: Transaction History Access
1. Navigate to: `/finance/bank-statements/{transaction_id}/history`
2. **Expected:** Similar to payment history

### 8. **Better Error Messages Tests**

#### Test 1: Payment Reversal Impact Summary
1. Reverse a payment that has allocations
2. **Expected:** Success message should include:
   - Number of invoices recalculated
   - Number of students affected
   - "All allocations have been removed"

#### Test 2: Transaction Archive Impact
1. Archive a transaction with related payments
2. **Expected:** Message should show how many payments were reversed

### 9. **Integration Tests**

#### Test 1: Complete Payment Reversal Flow
1. Create a payment with allocations
2. Reverse the payment
3. **Expected:**
   - Payment marked as reversed
   - Allocations deleted
   - Invoices recalculated
   - Bank transaction moved to "unallocated uncollected" (if applicable)
   - Audit log created
   - Version incremented

#### Test 2: Complete Transaction Archive Flow
1. Create a transaction with payment
2. Archive the transaction
3. **Expected:**
   - Transaction archived
   - Related payment(s) reversed
   - Audit log created
   - Version incremented

### 10. **Edge Cases**

#### Test 1: Reverse Payment with No Allocations
1. Reverse a payment that has no allocations
2. **Expected:** Should work, message should reflect no invoices affected

#### Test 2: Edit Shared Allocations for Single Payment
1. Try to edit allocations for non-shared payment
2. **Expected:** Error message about shared payments only

#### Test 3: Archive Transaction with No Payment
1. Archive a transaction that has no payment created
2. **Expected:** Should archive successfully, no payment reversal needed

## Automated Test Commands

### Run Laravel Tests (if test files exist)
```bash
php artisan test --filter Payment
php artisan test --filter BankStatement
```

### Check for Syntax Errors
```bash
php artisan route:list | grep payments
php artisan route:list | grep bank-statements
```

### Verify Policies are Registered
```bash
php artisan tinker
>>> Gate::abilities()
# Should see: reverse, editSharedAllocations, transfer, etc.
```

## Manual Testing Scenarios

### Scenario 1: Multi-User Concurrent Edit
1. User A opens payment edit page
2. User B opens same payment edit page
3. User A submits changes
4. User B tries to submit changes
5. **Expected:** User B gets optimistic locking error

### Scenario 2: Bulk Operation Under Load
1. Select 50+ transactions
2. Try bulk confirm
3. **Expected:** Should process, but may take time (check for timeouts)

### Scenario 3: Audit Trail Completeness
1. Perform all operations (reverse, archive, edit)
2. Check audit_logs table
3. **Expected:** Every operation should have a corresponding log entry

## Production Deployment Checklist

- [ ] Run migrations on production
- [ ] Verify database columns exist
- [ ] Test authorization with real users
- [ ] Verify audit logs are being created
- [ ] Check rate limiting is working
- [ ] Test optimistic locking with real concurrent users
- [ ] Verify history views are accessible
- [ ] Check error messages are user-friendly
- [ ] Monitor logs for any errors
- [ ] Verify version columns are incrementing correctly

## Rollback Plan

If issues are found:

1. **Rollback Migrations:**
```bash
php artisan migrate:rollback --step=2
```

2. **Remove Routes:**
   - Comment out new history routes
   - Remove rate limiting middleware

3. **Disable Features:**
   - Comment out authorization checks (temporary)
   - Comment out audit logging (temporary)

## Performance Testing

### Test Bulk Operations
```bash
# Time the operation
time curl -X POST [bulk-endpoint] [with-data]
```

### Check Database Performance
```sql
-- Check index usage
EXPLAIN SELECT * FROM audit_logs WHERE auditable_type = 'App\\Models\\Payment';
```

## Security Testing

1. **SQL Injection:** Try SQL in form fields - should be escaped
2. **XSS:** Try script tags in reversal reason - should be escaped
3. **CSRF:** Try submitting without token - should fail
4. **Authorization Bypass:** Try direct URL access without proper role - should fail

## Monitoring After Deployment

1. Monitor `audit_logs` table growth
2. Check for 429 errors in logs (rate limiting)
3. Monitor for authorization errors (403)
4. Check version column increments are working
5. Verify no duplicate audit logs

## Common Issues & Solutions

### Issue: Audit logs not being created
**Solution:** Check `audit_logs` table exists and user has INSERT permission

### Issue: Optimistic locking always fails
**Solution:** Check version column is being sent in form (hidden input)

### Issue: Rate limiting too strict
**Solution:** Adjust throttle values in routes/web.php

### Issue: Authorization always fails
**Solution:** Check policies are registered in AuthServiceProvider
