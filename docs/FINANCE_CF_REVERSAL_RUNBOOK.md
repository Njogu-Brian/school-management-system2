## Carry-forward reversal runbook (2026 Term 2)

This runbook reverses the Term 1 → Term 2 (2026) carry-forward internal transfers and restores independent invoices, then reallocates real payments using **oldest invoice first**.

### Safety
- **Run on local cloned DB first**.
- All mutating commands support `--dry-run`.
- Internal transfer payments are **not deleted**; they are **marked reversed** for auditability.
- Real payments are **never deleted**; only allocations are rewritten.

### Definitions (what is being reversed)
- Internal transfer payments created by carry-forward:
  - `payments.payment_channel = term_balance_transfer`
  - `payments.payment_method = Internal transfer`
  - `payments.transaction_code` like `TERM-CF-2026-T2-S{studentId}`
- Term 2 carry-forward invoice item:
  - `invoice_items.source = prior_term_carryforward`
  - votehead code `PRIOR_TERM_ARREARS`

### 1) Analyze (read-only)
Writes a snapshot to `storage/app/finance-migrations/cf_2026_t2/before.json`.

```bash
php artisan finance:cf:analyze --year=2026 --term=2
```

Optional single-student:

```bash
php artisan finance:cf:analyze --year=2026 --term=2 --student=RKS664
```

### 2) Reverse internal transfers (dry-run, then real)
Dry-run:

```bash
php artisan finance:cf:reverse --year=2026 --term=2 --dry-run
```

Execute:

```bash
php artisan finance:cf:reverse --year=2026 --term=2
```

This writes `storage/app/finance-migrations/cf_2026_t2/reverse_log.json`.

### 3) Reallocate real payments (oldest invoice first)
Dry-run:

```bash
php artisan finance:payments:reallocate-oldest-first --year=2026 --upToTerm=2 --dry-run
```

Execute:

```bash
php artisan finance:payments:reallocate-oldest-first --year=2026 --upToTerm=2
```

This writes `storage/app/finance-migrations/cf_2026_t2/after.json`.

### 4) Validate (read-only)
Produces `storage/app/finance-migrations/cf_2026_t2/validation.csv`.

```bash
php artisan finance:cf:validate --year=2026 --term=2
```

### Expected outcomes
- Term 1 invoice remains visible and unpaid/partial if not cleared.
- Term 2 invoice totals reflect only Term 2 items (no `prior_term_carryforward` line).
- Payments clear Term 1 first, then Term 2.
- Reversed internal transfers have **no allocations**.

### Rollback (local DB)
- Restore DB from your local backup snapshot.\n
