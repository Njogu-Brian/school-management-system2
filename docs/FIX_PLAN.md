# Database Fix Plan
**Based on Production Database Analysis**

## Overview

We found **479 issues** in the production database. This document outlines the fix plan to resolve them systematically.

---

## Phase 1: Critical Fixes (Do First)

### Fix 1.1: Reset Reversed Payment Links
**Priority:** CRITICAL  
**Estimated Time:** 30 minutes  
**Issues:** ~50 transactions

**Problem:**
- Transactions have `payment_id` pointing to reversed payments
- `payment_created` still `true`
- Blocks new payment creation

**SQL Fix:**
```sql
-- Bank Statement Transactions
UPDATE bank_statement_transactions t
JOIN payments p ON p.id = t.payment_id
SET 
    t.payment_id = NULL,
    t.payment_created = false,
    t.status = CASE 
        WHEN t.match_status IN ('matched', 'manual') THEN 'draft'
        ELSE t.status
    END
WHERE p.reversed = true
AND t.payment_created = true;

-- C2B Transactions  
UPDATE mpesa_c2b_transactions t
JOIN payments p ON p.id = t.payment_id
SET 
    t.payment_id = NULL,
    t.status = CASE 
        WHEN t.allocation_status IN ('auto_matched', 'manually_allocated') THEN 'pending'
        ELSE 'pending'
    END
WHERE p.reversed = true
AND t.status = 'processed';
```

**Verification:**
```sql
-- Should return 0
SELECT COUNT(*) FROM bank_statement_transactions t
JOIN payments p ON p.id = t.payment_id
WHERE p.reversed = true AND t.payment_created = true;
```

---

### Fix 1.2: Reset Confirmed Without Valid Payments
**Priority:** CRITICAL  
**Estimated Time:** 30 minutes  
**Issues:** 64 transactions

**Problem:**
- Transactions marked `confirmed` or `payment_created = true`
- But payment is NULL, reversed, or deleted

**SQL Fix:**
```sql
-- Bank Statement Transactions
UPDATE bank_statement_transactions t
LEFT JOIN payments p ON p.id = t.payment_id 
    AND p.reversed = false 
    AND p.deleted_at IS NULL
SET 
    t.payment_created = false,
    t.payment_id = NULL,
    t.status = CASE 
        WHEN t.match_status IN ('matched', 'manual') THEN 'draft'
        WHEN t.match_status = 'unmatched' THEN 'draft'
        ELSE 'draft'
    END
WHERE (t.status = 'confirmed' OR t.payment_created = true)
AND (p.id IS NULL OR p.reversed = true OR p.deleted_at IS NOT NULL);

-- C2B Transactions
UPDATE mpesa_c2b_transactions t
LEFT JOIN payments p ON p.id = t.payment_id 
    AND p.reversed = false 
    AND p.deleted_at IS NULL
SET 
    t.payment_id = NULL,
    t.status = CASE 
        WHEN t.allocation_status IN ('auto_matched', 'manually_allocated') THEN 'pending'
        ELSE 'pending'
    END
WHERE t.status = 'processed'
AND (p.id IS NULL OR p.reversed = true OR p.deleted_at IS NOT NULL);
```

**Verification:**
```sql
-- Should return 0
SELECT COUNT(*) FROM bank_statement_transactions t
WHERE (t.status = 'confirmed' OR t.payment_created = true)
AND NOT EXISTS (
    SELECT 1 FROM payments p 
    WHERE p.id = t.payment_id 
    AND p.reversed = false 
    AND p.deleted_at IS NULL
);
```

---

## Phase 2: Swimming Transactions (High Priority)

### Fix 2.1: Reverse Swimming Invoice Allocations
**Priority:** HIGH  
**Estimated Time:** 2-3 hours  
**Issues:** 147 transactions

**Problem:**
- Swimming transactions have payments allocated to invoice items
- Should be in swimming wallet instead

**Complex Fix (Requires PHP Script):**

This needs a Laravel command because we need to:
1. Reverse invoice allocations
2. Credit swimming wallets
3. Update swimming ledgers
4. Recalculate invoices

**Command:** `php artisan transactions:fix-swimming-fees`

**Logic:**
```php
// For each swimming transaction with invoice allocations:
1. Find all payment allocations to invoice items
2. Reverse each allocation:
   - Delete payment_allocation record
   - Recalculate invoice (amount_paid -= allocation)
   - Update invoice status if needed
3. Credit swimming wallet:
   - Get or create swimming wallet for student
   - Update wallet balance
   - Create swimming ledger entry
4. Update payment:
   - Set allocated_amount = 0 (or remove invoice allocations)
   - Mark as swimming payment
5. Update transaction:
   - Ensure is_swimming_transaction = true
   - Update swimming_allocated_amount
```

**Verification:**
```sql
-- Should return 0
SELECT COUNT(*) FROM bank_statement_transactions t
JOIN payments p ON p.id = t.payment_id
JOIN payment_allocations pa ON pa.payment_id = p.id
JOIN invoice_items ii ON ii.id = pa.invoice_item_id
WHERE t.is_swimming_transaction = true
AND p.reversed = false;
```

---

## Phase 3: Link Unlinked Payments (Medium Priority)

### Fix 3.1: Link Payments by Reference Number
**Priority:** MEDIUM  
**Estimated Time:** 1-2 hours  
**Issues:** 116 transactions

**Problem:**
- Payments exist with matching reference numbers
- But transactions not linked to them

**Important:** Many of these are likely from sibling sharing where:
- One transaction reference (e.g., `UA5PM2ZIDG`)
- Multiple payments with suffixes (e.g., `UA5PM2ZIDG-4-1767731170`)
- Only one payment should be linked to the transaction

**SQL Fix (Conservative - Only link if single match):**
```sql
-- Bank Statement Transactions - Only link if exactly one matching payment
UPDATE bank_statement_transactions t
JOIN (
    SELECT 
        reference_number,
        COUNT(*) as payment_count,
        MIN(id) as payment_id
    FROM (
        SELECT t.reference_number, p.id
        FROM bank_statement_transactions t
        JOIN payments p ON (
            p.transaction_code = t.reference_number
            OR p.transaction_code LIKE CONCAT(t.reference_number, '-%')
        )
        WHERE t.payment_id IS NULL
        AND t.reference_number IS NOT NULL
        AND p.reversed = false
        AND p.deleted_at IS NULL
        GROUP BY t.reference_number, p.id
    ) as matches
    GROUP BY reference_number
    HAVING payment_count = 1
) as single_matches ON single_matches.reference_number = t.reference_number
SET 
    t.payment_id = single_matches.payment_id,
    t.payment_created = true
WHERE t.payment_id IS NULL;
```

**For Sibling Sharing Cases:**
- These need manual review
- Link the "primary" payment (usually the first one created)
- Or create a shared transaction allocation record

---

## Phase 4: Data Validation (Low Priority)

### Fix 4.1: Review Mismatched Amounts
**Priority:** LOW  
**Estimated Time:** 1 hour  
**Issues:** ~52 transactions

**Analysis:**
- Most mismatches are from sibling sharing (expected)
- Transaction amount = sum of all sibling payments
- Individual payment amounts are smaller

**Action:**
- Review each case manually
- If sibling sharing: No fix needed
- If error: Adjust amounts or create additional payments

**Query to Review:**
```sql
SELECT 
    t.id,
    t.reference_number,
    t.amount as transaction_amount,
    p.id as payment_id,
    p.amount as payment_amount,
    ABS(t.amount - p.amount) as difference,
    t.is_shared,
    (SELECT COUNT(*) FROM payments p2 
     WHERE p2.transaction_code LIKE CONCAT(t.reference_number, '%')
     AND p2.reversed = false) as sibling_payment_count
FROM bank_statement_transactions t
JOIN payments p ON p.id = t.payment_id
WHERE t.payment_created = true
AND p.reversed = false
AND ABS(t.amount - p.amount) > 0.01
ORDER BY difference DESC;
```

---

## Execution Order

1. ✅ **Backup Database** (CRITICAL - Do this first!)
2. ✅ **Fix 1.1:** Reset Reversed Payment Links
3. ✅ **Fix 1.2:** Reset Confirmed Without Payments
4. ✅ **Verify Phase 1:** Re-run analysis, should see ~114 issues resolved
5. ✅ **Fix 2.1:** Fix Swimming Transactions (requires PHP script)
6. ✅ **Verify Phase 2:** Re-run analysis, should see ~261 issues resolved
7. ✅ **Fix 3.1:** Link Unlinked Payments (conservative approach)
8. ✅ **Review:** Manually review remaining unlinked payments (sibling sharing)
9. ✅ **Fix 4.1:** Review mismatched amounts
10. ✅ **Final Verification:** Re-run full analysis, should see <50 issues remaining

---

## Safety Measures

1. **Always backup before running fixes**
2. **Test on staging/local first**
3. **Run fixes in transactions (rollback on error)**
4. **Verify after each phase**
5. **Keep audit logs of all changes**

---

## Expected Results

After all fixes:
- **Reversed Payment Links:** 0 issues
- **Confirmed Without Payments:** 0 issues  
- **Swimming for Fees:** 0 issues
- **Unlinked Payments:** ~20-30 (sibling sharing cases - may be expected)
- **Mismatched Amounts:** ~10-20 (sibling sharing - expected)
- **Other Issues:** ~5-10 (need manual review)

**Total Remaining:** ~35-60 issues (mostly expected from sibling sharing)

---

## Next Steps

1. Review this plan
2. Create backup
3. Start with Phase 1 (Critical Fixes)
4. Verify results
5. Proceed to Phase 2
6. Continue through all phases
7. Update code to prevent future issues
