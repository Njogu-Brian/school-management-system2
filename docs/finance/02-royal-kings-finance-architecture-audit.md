# Royal Kings finance architecture audit (Phase 0)

**Status:** Complete (implementation-aligned)  
**Scope:** Admin + parent mobile finance integrity; receivables subledger only.

## Root cause: dashboard zeros vs Fee balances

| Path | Filters | Result |
|------|---------|--------|
| Dashboard (before fix) | `FinanceTermKpiService` → `schoolOutstandingSummary($year)` — year + placement roster + **due only** | Could return 0 when placement/year empty or mismatched |
| Fee balances (before) | `GET /invoices?has_balance=1&due_bucket=overdue` — all years, active students | Showed real arrears |
| Dashboard + Fee balances (after) | `activeOverdueArrearsSummary()` via `/finance/summary` + `/finance/fee-balances` | Same universe |

## Admin map

| Screen | Route | API | Laravel |
|--------|-------|-----|---------|
| Finance Dashboard | `FinanceDashboard` | `GET /finance/summary` | `ApiFinanceSummaryController@show` |
| Billing | `BillingList` | `GET /invoices` | `ApiInvoiceController@index` |
| Fee balances | `FeeBalances` | `GET /finance/fee-balances` | `ApiFinanceSummaryController@feeBalances` |
| Collections — Payments | `CollectionsList` | `GET /payments` | `ApiPaymentController@index` |
| Collections — Transactions | same | `GET /finance/transactions` | `ApiFinanceTransactionsController@index` |
| Reconciliation | `ReconciliationList` | same transactions API (`view=unassigned`) | same |
| Statements | `Statements` | `GET /students/{id}/statement` | `ApiStudentStatementController@show` |
| Accounting | Reports drawer | income / BS / ledger / expenses | various `Api*Reports*` |

## Parent map

| Screen | API | Notes |
|--------|-----|-------|
| Fees home | `GET /parent/family-fees` | Family outstanding / overdue / upcoming |
| Family payment | `POST /parent/family-fees/prompt` | STK; tag `FAMILY_OLDEST_FIRST` |
| Per-child statement | `GET /students/{id}/statement` | + public `view_url` / `pdf_url` |
| Wallet | `GET/POST /parent-wallet*` | Separate from school-fee STK |

## Allocation

- Admin / per-student: existing `PaymentAllocationService` FIFO
- Parent family STK: oldest outstanding **published** invoice first across linked children (`MpesaPaymentController` + `FAMILY_OLDEST_FIRST` notes)

## Transaction lifecycle (UI)

Received → Matched → Awaiting posting → Posted / Unmatched / Duplicate / Failed  
`processed` ≠ payment posted (`payment_created`).
