# Sprint 2 Batch 2 — School Command Center (Live KPIs)

**Date:** 2026-06-04  
**Scope:** Connect five critical KPI widgets to existing Laravel APIs via TanStack Query. No new backend routes in this batch.

---

## APIs reused

| KPI | HTTP | Controller / notes |
|-----|------|-------------------|
| Enrollment | `GET /api/dashboard/stats` | `ApiDashboardController::adminDashboard` → `total_students` |
| Attendance | Same | `present_today`; client derives % vs `total_students` |
| Collections | Same | `fees_collected` (scoped by `academic_year_id`, `term_id` query params) |
| Outstanding fees | Same | `outstanding_balance` (+ `total_invoiced` for collection-rate delta) |
| Pending approvals | `GET /api/leave-requests?status=pending&per_page=1` | Paginated `data.total` |
| Pending approvals (add.) | `GET /api/lesson-plans/review-queue?per_page=1` | Paginated `data.total`; 403 → counted as 0 |

**Auth:** Existing bearer token via `@erp/core` `apiClient` and `SessionProvider` / `AuthProvider`.

**Not used (audit):** `GET /finance/summary` — not registered in `routes/api.php` for mobile; avoided.

---

## APIs created

**None.** All metrics are sourced from existing endpoints.

---

## Query architecture

```
App.tsx
  QueryProvider (@erp/core)
    AuthProvider
      DashboardScreen → KpiWidgetBase → useKpiWidgetData(widgetId)
                              ↓
        useDashboardStats()  ──► fetchAdminDashboardStats() ──► dashboardApi.getStats()
        usePendingApprovals() ──► fetchPendingApprovalsSummary() ──► leave + lesson-plan counts
                              ↓
        kpiAdapters (apps/admin) ──► KpiCard props
                              ↓
        mapQueryToWidgetState ──► WidgetShell state
```

**Modules**

| Layer | Path |
|-------|------|
| API module | `packages/core/src/api/dashboard.api.ts` |
| Types | `packages/core/src/types/dashboard.ts` |
| Fetchers | `packages/core/src/query/fetchers.ts` |
| Hooks | `packages/core/src/query/hooks/useDashboardStats.ts`, `usePendingApprovals.ts` |
| Keys | `packages/core/src/query/queryKeys.ts` |
| Widget state map | `packages/core/src/query/widgetQueryState.ts` |
| Adapters | `apps/admin/src/features/dashboard/adapters/kpiAdapters.ts` |
| Widget hook | `apps/admin/src/features/dashboard/hooks/useKpiWidgetData.ts` |

**WidgetShell mapping**

| TanStack Query | WidgetShell |
|----------------|-------------|
| `isPending` / `isLoading` | `loading` |
| `isError` | `error` (+ `onRetry` → `refetch`) |
| `isSuccess` + adapter `isEmpty` | `empty` |
| `isSuccess` + data | `success` |

Queries run only when `authStatus === 'authenticated'` and the widget passes RBAC visibility (`useIsWidgetVisible`).

---

## Caching strategy

- **Library:** `@tanstack/react-query` v5.
- **Shared client:** `createAppQueryClient()` — default `staleTime: 60s`, `gcTime: 10m`, `retry: 2`, refetch on window focus and reconnect.
- **Dashboard stats:** `queryKey: ['dashboard', 'stats', filters]` — one cached payload shared by four KPI widgets (single network request per mount/focus cycle).
- **Pending approvals:** `queryKey: ['dashboard', 'pending-approvals']`, `staleTime: 45s` — aggregates two lightweight `per_page=1` list calls.

---

## Invalidation strategy

**Current batch:** Implicit refresh via `staleTime` + `refetchOnWindowFocus` / `refetchOnReconnect`; per-widget **Retry** calls `query.refetch()`.

**Recommended follow-ups (not implemented here):**

- On logout: `queryClient.clear()` in auth teardown.
- After finance actions (payment post, invoice update): `invalidateQueries({ queryKey: queryKeys.dashboard.stats() })`.
- After leave approve/reject or lesson-plan review: `invalidateQueries({ queryKey: queryKeys.dashboard.pendingApprovals() })`.
- Term/year filter UI (Batch 3): pass `filters` into `useDashboardStats({ filters })` — keys already include filter object.

---

## Risks

| Risk | Mitigation |
|------|------------|
| Non-admin role receives non-admin `dashboard/stats` shape | Adapters expect admin fields; may show partial/empty KPIs for wrong role — enforce admin-only app access. |
| Attendance % is same-day present count / all students, not lesson attendance rate | Matches web admin dashboard semantics; document for users. |
| Pending approvals under-counts salary advances, expenses, etc. | Only leave + lesson plans; extend with additional list endpoints when product defines full approval inbox. |
| Lesson-plan review 403 for users without reviewer permission | Caught; contributes 0 to total (may under-report for some roles). |
| `outstanding_fees` empty when balance and invoiced are both 0 | Intentional empty state; zero balance with invoices still shows success. |
| Four KPIs share one stats query — filter change will refetch all | Acceptable; add scoped invalidation when term picker lands. |

---

## Verification

```bash
cd mobile-app && npm install
cd apps/admin && npm run typecheck
cd ../../packages/core && npx tsc --noEmit
```

Manual: sign in as Admin → Command Center → confirm KPIs load, error + Retry on offline, empty states for zero students / zero pending approvals.
