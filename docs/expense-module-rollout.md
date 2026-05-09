# Expense Module Rollout Guide

## Deployment Sequence

1. Run database migration for new expense tables:
   - `php artisan migrate`
2. Seed default categories and permissions:
   - `php artisan db:seed --class=ExpenseCategorySeeder`
   - `php artisan db:seed --class=ExpensePermissionSeeder`
3. Verify finance routes load:
   - `/finance/expenses`
   - `/finance/payment-vouchers`
   - `/finance/expense-categories`
   - `/finance/vendors`
   - `/finance/expenses/reports`

## Pilot (Week 1)

- Assign `Finance Officer` role to pilot users.
- Configure base categories and vendor list.
- Enter 20-30 real expenses from different departments.
- Validate approval and voucher cycle on at least 5 items.

## Controlled Adoption (Week 2)

- Train secretaries/requesters on draft + submit steps.
- Train finance users on approve, voucher, and payment posting.
- Export category and vendor spend reports for finance review.

## Full Rollout (Week 3+)

- Backfill current month expenses.
- Start mandatory voucher flow for all approved expenses.
- Run weekly reconciliation between voucher register and bank/cash payments.
