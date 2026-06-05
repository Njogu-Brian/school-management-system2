# Sprint 6 — Finance Workspace MVP

**Date:** 2026-06-04  
**Scope:** Replace Finance placeholder with a complete read-only Finance Workspace (Dashboard, Billing, Collections, Statements, Reconciliation) in `@erp/admin`, following Students/Staff/Admissions patterns.  
**Out of scope:** General Ledger, Chart of Accounts, journals, trial balance, budgeting, procurement, assets, invoice generation, payment recording/editing.

**Finance Workspace completion: 100%** of the defined MVP scope (5 workspace areas + navigation + data layer + RBAC). Deferred items are explicitly post-MVP (see §9).

---

## 1. APIs reused

All endpoints are existing Laravel Sanctum routes — **no backend routes were added or modified**.

| Area | Method | Path | Purpose |
|------|--------|------|---------|
| Dashboard | `GET` | `/dashboard/stats` | Role-shaped stats (`collections_today`, `collections_month`, `outstanding_balance`, etc.) |
| Dashboard | `GET` | `/payments` | Aggregate collected today / this month (non-finance roles) |
| Dashboard | `GET` | `/invoices` | Outstanding balance & students-in-arrears (finance role / fallback) |
| Dashboard | `GET` | `/finance/transactions?view=unassigned` | Pending reconciliation count |
| Billing | `GET` | `/invoices` | Paginated invoice list with filters |
| Billing | `GET` | `/invoices/{id}` | Invoice detail (line items, voteheads, history) |
| Collections | `GET` | `/payments` | Paginated payment list with filters |
| Collections | `GET` | `/payments/{id}` | Payment detail (allocations, receipt URL) |
| Statements | `GET` | `/students/{id}/statement` | Student fee statement (via `useStudentStatement` — shared with Student 360) |
| Statements | `GET` | `/students` | Student search for statement lookup |
| Reconciliation | `GET` | `/finance/transactions` | Unified bank/C2B queue (`view`: unassigned, confirmed, rejected) |
| Reconciliation | `GET` | `/finance/transactions/{id}?type=bank\|c2b` | Transaction detail |
| Reconciliation | `POST` | `/finance/transactions/{id}/confirm` | Confirm matched transaction |
| Reconciliation | `POST` | `/finance/transactions/{id}/reject` | Reject transaction |

**Not wired in MVP (available but out of scope):** `POST /payments`, M-Pesa STK/link, `GET /fee-structures` (fee catalog browse), posting/commit web flows.

---

## 2. APIs added

**None.** KPIs are composed client-side in `fetchFinanceDashboardKpis` from the endpoints above. No new `/finance/summary` endpoint was introduced.

---

## 3. Files created

### `@erp/core`

| File | Role |
|------|------|
| `packages/core/src/types/finance.ts` | Invoice, payment, transaction, filter, KPI types |
| `packages/core/src/finance/normalize.ts` | Summary normalizers, status/method labels, queue→view mapping |
| `packages/core/src/finance/fetchFinanceDashboard.ts` | Dashboard KPI composition |
| `packages/core/src/finance/index.ts` | Finance module exports |
| `packages/core/src/api/finance.api.ts` | REST client for invoices, payments, transactions |
| `packages/core/src/query/hooks/useFinance.ts` | TanStack Query hooks + reconciliation mutations |

### `@erp/ui`

| File | Role |
|------|------|
| `packages/ui/src/finance/types.ts` | Presentational prop types |
| `packages/ui/src/finance/FinanceSearchBar.tsx` | Search input |
| `packages/ui/src/finance/InvoiceStatusBadge.tsx` | Invoice status chip |
| `packages/ui/src/finance/InvoiceFilters.tsx` | Billing filter bar |
| `packages/ui/src/finance/InvoiceListItem.tsx` | Invoice row |
| `packages/ui/src/finance/PaymentListItem.tsx` | Payment row |
| `packages/ui/src/finance/ReconciliationFilters.tsx` | Queue/status filter |
| `packages/ui/src/finance/FinanceTransactionListItem.tsx` | Reconciliation row |
| `packages/ui/src/finance/FinanceFieldSection.tsx` | Detail field groups |
| `packages/ui/src/finance/StatementLedger.tsx` | Statement debit/credit ledger |
| `packages/ui/src/finance/FinanceScreenHeader.tsx` | Section header with back |
| `packages/ui/src/finance/index.ts` | Barrel export |

### `apps/admin`

| File | Role |
|------|------|
| `src/features/finance/screens/FinanceDashboardScreen.tsx` | KPI dashboard + workspace quick links |
| `src/features/finance/screens/BillingListScreen.tsx` | Invoice registry |
| `src/features/finance/screens/InvoiceDetailScreen.tsx` | Read-only invoice detail |
| `src/features/finance/screens/CollectionsScreen.tsx` | Payment registry |
| `src/features/finance/screens/PaymentDetailScreen.tsx` | Read-only payment detail + receipt URL |
| `src/features/finance/screens/StatementsScreen.tsx` | Student search + statement ledger |
| `src/features/finance/screens/ReconciliationScreen.tsx` | Transaction queue |
| `src/features/finance/screens/TransactionDetailScreen.tsx` | Detail + confirm/reject |
| `src/features/finance/hooks/useBillingRegistryState.ts` | Billing filters/search debounce |
| `src/features/finance/hooks/useCollectionsRegistryState.ts` | Collections filters/search debounce |
| `src/features/finance/hooks/useReconciliationRegistryState.ts` | Reconciliation queue state |
| `src/features/finance/utils/formatters.ts` | Local KES display helpers |
| `src/navigation/FinanceStackNavigator.tsx` | Finance stack navigator |
| `src/navigation/financeStackTypes.ts` | Stack param list |

### Docs

| File | Role |
|------|------|
| `docs/finance/01-finance-workspace-audit.md` | Sprint 6 Batch 1 discovery (prerequisite) |

---

## 4. Files modified

| File | Change |
|------|--------|
| `packages/core/src/api/index.ts` | Export `financeApi` |
| `packages/core/src/index.ts` | Export finance types, normalizers, `formatFinanceAmount` |
| `packages/core/src/query/index.ts` | Export `useFinance` hooks |
| `packages/core/src/query/queryKeys.ts` | `finance.*` query key tree |
| `packages/core/src/types/index.ts` | Re-export finance types |
| `packages/ui/src/index.ts` | Export `finance` UI module |
| `apps/admin/src/features/finance/index.ts` | Screen barrel (replaces placeholder) |
| `apps/admin/src/navigation/BottomTabsNavigator.tsx` | Finance tab → `FinanceStackNavigator` |

### Files removed

| File | Reason |
|------|--------|
| `apps/admin/src/features/finance/screens/FinanceScreen.tsx` | Placeholder replaced by full stack |

---

## 5. Query architecture

```
queryKeys.finance
├── dashboard()                    → useFinanceDashboardKpis
├── invoices(filters)              → useInfiniteInvoiceList
├── invoiceDetail(id)              → useInvoiceDetail
├── payments(filters)              → useInfinitePaymentList
├── paymentDetail(id)              → usePaymentDetail
├── transactions(filters)          → useInfiniteFinanceTransactions
└── transactionDetail(id, type)    → useFinanceTransactionDetail

Shared (Statements):
queryKeys.students.statement(id, year) → useStudentStatement (Student 360 hook)
queryKeys.students.list(filters)       → useInfiniteStudentList (student search)
```

**Patterns mirrored from Admissions/Students:**

- `useInfiniteQuery` for registries (billing, collections, reconciliation) with `initialPageParam: 1` and `getNextPageParam` from `lastPage`.
- Detail queries keyed by entity id (+ `type` for transactions).
- Registry state hooks (`useBillingRegistryState`, etc.) own debounced search and filter state; filters are part of the query key so cache partitions correctly.
- Reconciliation mutations (`useReconciliationActions`) invalidate `queryKeys.finance.all` on success.
- No duplicate fetches: dashboard uses its own key; list screens do not re-fetch dashboard stats.

---

## 6. Caching strategy

| Hook | `staleTime` | Notes |
|------|-------------|-------|
| `useFinanceDashboardKpis` | 60s | Composed from 2–4 parallel API calls |
| `useInfiniteInvoiceList` | 45s | Filter object in key |
| `useInvoiceDetail` | 60s | Per-invoice cache |
| `useInfinitePaymentList` | 45s | `active_only: true` default |
| `usePaymentDetail` | 60s | Per-payment cache |
| `useInfiniteFinanceTransactions` | 45s | `view` derived from queue tab |
| `useFinanceTransactionDetail` | 60s | Keyed by id + type |
| `useStudentStatement` | (existing) | Shared with Student 360 Fees tab |

**Refresh:** Pull-to-refresh on all list screens and dashboard (`refetch` / `isRefetching`). Infinite lists append pages; pull-to-refresh resets to page 1 via TanStack Query default refetch behavior.

**Invalidation:** Confirm/reject on reconciliation invalidates entire `finance` namespace (dashboard KPIs, lists, and affected detail).

---

## 7. RBAC model

| Permission | Usage |
|------------|-------|
| `finance.view` | Gate on every Finance screen (`useCan('finance.view')`) |

**No new permissions were invented.** Tab visibility continues to use existing `ADMIN_TAB_PERMISSIONS.finance` → `[finance.view]` in `@erp/core/rbac`.

Screens show an access-denied message when the permission is missing. Reconciliation confirm/reject actions are available to users who can open the workspace; server-side authorization on `POST /finance/transactions/{id}/confirm|reject` remains the authority.

---

## 8. Risks

1. **Dashboard KPI accuracy** — Collected today/month for non-finance roles sums the first page of payments (`per_page: 100`). Outstanding fees and students-in-arrears for finance roles sum/count from the first page of invoices. Large schools may see under-counted KPIs until a dedicated summary endpoint exists.
2. **No `/finance/summary`** — Client composition adds latency (2–4 round trips) and is harder to keep consistent than a single server aggregate.
3. **Invoice list authorization gap** — Per audit, `GET /invoices` index lacks a finance role guard (detail is guarded). Mobile relies on Sanctum + existing server behavior.
4. **Statement year** — Statements default to the current calendar year only; no year picker in MVP.
5. **Reconciliation list raw/summary pairing** — List pages store both normalized summaries and raw records by index; safe while API returns aligned arrays, but fragile if normalization ever drops rows.

---

## 9. Follow-up recommendations

| Priority | Item |
|----------|------|
| High | Add `GET /finance/summary` (or extend `/dashboard/stats`) for accurate KPIs without client aggregation |
| High | Record Payment flow (`POST /payments`) for Collections write path |
| Medium | Fee Structures browse under Billing (`GET /fee-structures`) |
| Medium | Statement year/term selector |
| Medium | Extend `scripts/smoke-admin-api.ps1` with finance list/detail endpoints |
| Low | Invoice posting / fee commit (web-parity mutations) |
| Future | Finance Transformation (GL, COA, journals, budgeting, procurement, assets) |

---

## 10. Workspace feature matrix

| Phase | Deliverable | Status |
|-------|-------------|--------|
| 1 | Data layer (types, normalizers, API, hooks, keys) | ✅ |
| 2 | Finance Dashboard (5 KPIs, loading/error/empty, refresh) | ✅ |
| 3 | Billing list + invoice detail (read-only, search/filter/pagination) | ✅ |
| 4 | Collections list + payment detail (read-only, receipt URL) | ✅ |
| 5 | Statements (student search, ledger, Student 360 calculations) | ✅ |
| 6 | Reconciliation list + detail (confirm/reject via API) | ✅ |
| 7 | Finance stack navigation | ✅ |
| 8 | RBAC (`finance.view`) | ✅ |
| 9 | TypeScript, no mocks, state handling | ✅ |
| 10 | This report | ✅ |

---

## 11. Navigation structure

```
Finance (bottom tab)
└── FinanceStackNavigator
    ├── FinanceDashboard
    ├── BillingList → InvoiceDetail
    ├── CollectionsList → PaymentDetail
    ├── Statements
    └── ReconciliationList → TransactionDetail
```

Deep linking prefix unchanged: `finance` (see `linking.ts`, `BottomTabsNavigator`).

---

## 12. Verification

```bash
cd mobile-app/apps/admin && npm run typecheck
```

**Result:** `tsc --noEmit` passes with zero errors.

**Manual test checklist:**

- [ ] Finance tab visible with `finance.view`
- [ ] Dashboard KPIs load and pull-to-refresh works
- [ ] Billing: search, status filter, infinite scroll, invoice detail
- [ ] Collections: date/search filters, payment detail, open receipt URL when present
- [ ] Statements: student search → statement with invoiced/paid/balance + ledger
- [ ] Reconciliation: Pending / Confirmed / Rejected tabs, confirm/reject on eligible rows
- [ ] Access denied without `finance.view`

**Deploy note:** No backend changes — EC2 deploy not required for this sprint. Mobile build/publish only.
