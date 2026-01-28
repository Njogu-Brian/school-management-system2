# Student Statement Scoping Verification

This document confirms how the student statement at `/finance/student-statements/{student}` ensures that **all** displayed payments, invoices, credit/debit notes, and totals belong to that specific student.

## URL Reviewed

- **Route:** `GET /finance/student-statements/278` (and any `{student}` ID)
- **Controller:** `App\Http\Controllers\Finance\StudentStatementController::show()`
- **View:** `resources/views/finance/student_statements/show.blade.php`

---

## 1. Invoices

**Scope:** ✅ **Correctly filtered by student**

- **Query:** `Invoice::where('student_id', $student->id)` (line ~33).
- Further filters: `year`, term (via `whereHas('term', ...)`), `reversed_at` / `status`.
- **Conclusion:** Every invoice row belongs to the requested student.

---

## 2. Payments

**Scope:** ✅ **Correctly filtered by student** (after fix)

- **Query:** `Payment::where('student_id', $student->id)` (line ~59).
- Further filters: `whereYear('payment_date', $year)` and, when a term is selected, a grouped condition so that we only include payments that either:
  - have an invoice in the selected term, or
  - have `invoice_id` null.
- **Bug fixed:** Previously, when a term was selected, `orWhereNull('invoice_id')` was applied at the top level, so the query could return **any** payment with `invoice_id IS NULL`, including other students’. This was fixed by wrapping the term logic in `where(function ($q) { ... })` so the “term OR null invoice” applies only within the same student/year scope.
- **Conclusion:** All payments in the list belong to the requested student.

---

## 3. Payment allocations (transaction lines)

**Scope:** ✅ **Correctly scoped** (defensive filter added)

- Payments are already scoped by `student_id`.
- When building transaction lines from allocations, we now **additionally** require that each allocation’s `invoice_item->invoice_id` is in the set of this student’s invoice IDs (`$studentInvoiceIds`).
- **Conclusion:** Only allocations that apply to this student’s invoices are shown, even if data were corrupted (e.g. allocation to another student’s invoice).

---

## 4. Credit notes

**Scope:** ✅ **Correctly filtered by student**

- **Query:** `CreditNote::whereHas('invoiceItem', function ($q) use ($student, ...) {
    $q->whereHas('invoice', function ($q2) use ($student) {
        $q2->where('student_id', $student->id);
    });
  })`.
- Credit notes are loaded only when their `invoiceItem->invoice->student_id` equals the requested student.
- **Conclusion:** All credit notes on the statement belong to that student.

---

## 5. Debit notes

**Scope:** ✅ **Correctly filtered by student**

- **Query:** Same pattern as credit notes: `DebitNote::whereHas('invoiceItem', ..., whereHas('invoice', where('student_id', $student->id)))`.
- **Conclusion:** All debit notes on the statement belong to that student.

---

## 6. Totals

| Total | How it’s computed | Student-scoped? |
|-------|-------------------|------------------|
| **Total charges** | Sum of `invoice->items->amount` for invoices already filtered by `student_id` (excluding `source = swimming_attendance`) | ✅ Yes |
| **Total discounts** | Sum of invoice-level and item-level discounts on those same invoices | ✅ Yes |
| **Total payments** | `PaymentAllocation::whereHas('invoiceItem', whereIn('invoice_id', $invoiceIds))->...->sum('amount')` where `$invoiceIds` = this student’s invoices | ✅ Yes |
| **Total credit notes** | Sum of amounts of credit notes loaded via the student-scoped query above | ✅ Yes |
| **Total debit notes** | Sum of amounts of debit notes loaded via the student-scoped query above | ✅ Yes |
| **Balance** | `totalCharges - totalDiscounts - totalPayments + totalDebitNotes - totalCreditNotes` (+ balance brought forward for current year) | ✅ Yes |

All of these use only data that is already restricted to the requested student.

---

## 7. Other statement data

- **Legacy lines:** `LegacyStatementLine::whereHas('term', where('student_id', $student->id))` — ✅ student-scoped.
- **Fee concessions (discounts):** `FeeConcession::where('student_id', $student->id)` — ✅ student-scoped.
- **Reversed posting runs:** Filtered by `invoiceItems->invoice->student_id` and year — ✅ student-scoped.

---

## Summary

- **Invoices:** Filtered by `student_id`.
- **Payments:** Filtered by `student_id`; term filter fixed so it does not leak other students’ unlinked payments.
- **Payment allocation lines:** Only those whose invoice item belongs to this student’s invoices are shown (defensive check added).
- **Credit / debit notes:** Loaded only when their invoice belongs to the student.
- **Totals:** Derived only from the above student-scoped datasets.

With the fixes applied, every payment, invoice, credit/debit note, and total on the student statement for `/finance/student-statements/278` (or any student ID) is guaranteed to belong to that specific student.
