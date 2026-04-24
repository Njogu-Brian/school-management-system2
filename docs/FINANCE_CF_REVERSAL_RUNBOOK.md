## Prior-term carry-forward — scrapped

The intra-year **"Balance from prior term(s)"** feature has been permanently disabled.
Student statements now flow naturally using each term's own invoice items, credit notes,
discounts and payments. The running balance across terms is simply the cumulative sum of
those real transactions. No synthetic "Balance from prior term(s)" line and no matching
"Internal transfer" payment are created anymore, regardless of whether a student has
moved to a new term or a new academic year.

### What changed in code

- `InvoiceService::finalizeInvoiceAfterEnsure` no longer carries balances into Term 2/3.
- `InvoiceService::carryForwardPriorTermBalancesIfNeeded` is a no-op.
- `InvoiceService::syncCarryForwardFromTerm1` is a no-op (called from `UniformFeeService`
  and `InvoiceService::updateItemAmount`; those callers were also cleaned up).
- `InvoiceService::applyPriorTermCarryForwardIfNeeded` returns `false`.
- The **Prior-term balances** card on `finance/invoices` was removed.
- `InvoiceController@carryForwardPriorTermBalances` returns a neutral "feature disabled"
  notice so any cached/bookmarked POSTs are harmless.

### One-time database cleanup (run on production)

Removes existing `prior_term_carryforward` invoice items, reverses the internal
`TERM-CF-*` / `Internal transfer` payments, and rebalances invoices by reallocating
real payments oldest-invoice-first.

Dry-run first to see what will be touched:

```bash
php artisan finance:cf:scrap-all --dry-run
```

Execute:

```bash
php artisan finance:cf:scrap-all
```

Scope to a specific student while debugging:

```bash
php artisan finance:cf:scrap-all --student=RKS589
```

Restrict to one year:

```bash
php artisan finance:cf:scrap-all --year=2026
```

Skip the reallocation step (rare — only if payments are already correctly distributed):

```bash
php artisan finance:cf:scrap-all --no-reallocate
```

Each run writes a JSON log to
`storage/app/finance-migrations/cf_scrap_all_<timestamp>/scrap_log.json` with per-student
counts of items deleted, transfer payments reversed, invoices recalculated, and real
payments reallocated.

### Legacy commands (still available for auditing)

- `finance:cf:analyze --year=YYYY --term=T` — read-only snapshot of carry-forward state.
- `finance:cf:reverse --year=YYYY --term=T` — reverse a single year/term (subset of the
  scrap-all behavior above).
- `finance:cf:validate --year=YYYY --term=T` — produce a validation CSV.
- `finance:payments:reallocate-oldest-first --year=YYYY --upToTerm=T` — oldest-first
  reallocation without touching carry-forward artifacts.
