# Sprint 2 — Batch 3 Report: Approval Center Framework

**Status:** Complete  
**Scope:** Reusable approval infrastructure (UI + core + admin wiring). Initial sources: leave requests and lesson plan review queue. No new Laravel routes.  
**Verification:** `tsc --noEmit` passes for `packages/core`, `packages/ui`, and `apps/admin`.

---

## 1. Objective

Build an **Approval Center framework** that supports multiple workflows through a shared registry and components, without implementing every backend workflow in this batch.

**Statuses:** pending, approved, rejected, escalated, expired (escalated/expired derived client-side where the API only exposes `pending`).

**Priorities:** critical, high, medium, low (derived client-side from dates, `is_late`, and age).

**Dashboard:** Pending Approvals panel on the School Command Center, linked to Approval Center list + detail.

---

## 2. Approval architecture

```text
@erp/core
  types/approval.ts          — ApprovalItem, statuses, priorities, API record shapes
  api/approvals.api.ts       — leave + lesson plan HTTP (reuse existing routes)
  approvals/normalize.ts     — API → ApprovalItem, composite ids, status/priority rules
  approvals/fetchApprovals.ts — merge sources, filter, sort
  query/hooks                — useApprovalList, useApprovalDetail, useApprovalActions

@erp/ui/approvals            — presentational components (no data fetching)
  ApprovalStatusBadge / ApprovalPriorityBadge
  ApprovalCard / ApprovalList / ApprovalFilters
  ApprovalDetailView / ApprovalActionBar

apps/admin/features/approvals
  registry/approvalRegistry.ts — source metadata + permissions
  models/index.ts              — re-exports core types
  screens/                     — ApprovalCenter, ApprovalDetail
  utils/                       — card + detail field mappers

apps/admin/navigation
  DashboardStackNavigator      — DashboardHome → ApprovalCenter → ApprovalDetail

apps/admin/features/dashboard
  PendingApprovalsPanel        — top 5 pending items + “View all”
  PendingApprovalsSection      — dashboard section wrapper
```

### Composite identity

Items use `{sourceType}:{numericId}` (e.g. `leave_request:42`, `lesson_plan:9`) for navigation and query cache keys.

### Registry pattern

`APPROVAL_SOURCE_REGISTRY` lists enabled sources with labels and permissions. `fetchApprovalItems` merges enabled sources; new workflows add a registry entry + normalizer + API list method (no UI rewrite).

---

## 3. Files created

### `@erp/core`

| File | Purpose |
| --- | --- |
| `src/types/approval.ts` | Domain models and API record types |
| `src/api/approvals.api.ts` | Leave + lesson plan API module |
| `src/approvals/normalize.ts` | Normalization, status/priority heuristics |
| `src/approvals/fetchApprovals.ts` | Multi-source fetch + client filters |
| `src/approvals/index.ts` | Barrel |
| `src/query/hooks/useApprovalList.ts` | TanStack list query |
| `src/query/hooks/useApprovalDetail.ts` | Detail query (lesson plan refresh) |
| `src/query/hooks/useApprovalActions.ts` | Approve/reject mutations + invalidation |

### `@erp/ui/src/approvals/`

| File | Purpose |
| --- | --- |
| `types.ts` | Presentational types |
| `ApprovalStatusBadge.tsx` | Status chip |
| `ApprovalPriorityBadge.tsx` | Priority chip |
| `ApprovalCard.tsx` | List row card |
| `ApprovalList.tsx` | FlatList + loading/error/empty |
| `ApprovalFilters.tsx` | Status / priority / type chips |
| `ApprovalDetailView.tsx` | Detail layout |
| `ApprovalActionBar.tsx` | Approve / reject bar |
| `index.ts` | Barrel |

### `apps/admin`

| File | Purpose |
| --- | --- |
| `src/features/approvals/registry/approvalRegistry.ts` | Source registry |
| `src/features/approvals/models/index.ts` | Model re-exports |
| `src/features/approvals/utils/mapToCard.ts` | `ApprovalItem` → `ApprovalCardData` |
| `src/features/approvals/utils/detailFields.ts` | Detail field builder |
| `src/features/approvals/hooks/useCanViewApprovals.ts` | Permission gate |
| `src/features/approvals/screens/ApprovalCenterScreen.tsx` | Filtered list |
| `src/features/approvals/screens/ApprovalDetailScreen.tsx` | Detail + actions |
| `src/features/approvals/index.ts` | Barrel |
| `src/features/dashboard/components/PendingApprovalsPanel.tsx` | Dashboard panel |
| `src/features/dashboard/sections/PendingApprovalsSection.tsx` | Section wrapper |
| `src/navigation/dashboardStackTypes.ts` | Stack param list |
| `src/navigation/DashboardStackNavigator.tsx` | Dashboard nested stack |

### Docs

| File | Purpose |
| --- | --- |
| `docs/execution/sprint-2-batch-3-report.md` | This report |

---

## 4. Files modified

| File | Change |
| --- | --- |
| `packages/core/src/index.ts` | Export approvals module |
| `packages/core/src/types/index.ts` | Export approval types |
| `packages/core/src/api/index.ts` | Export `approvalsApi` |
| `packages/core/src/query/index.ts` | Export approval hooks |
| `packages/core/src/query/queryKeys.ts` | `approvals.list` / `approvals.detail` keys |
| `packages/ui/src/index.ts` | Export approvals components |
| `apps/admin/package.json` | `@react-navigation/stack` |
| `apps/admin/src/navigation/BottomTabsNavigator.tsx` | Dashboard tab → stack navigator |
| `apps/admin/src/features/dashboard/components/DashboardLayout.tsx` | Pending approvals section, hero copy |
| `apps/admin/src/features/dashboard/sections/index.ts` | Export pending section |
| `apps/admin/src/features/dashboard/sections/CriticalKpisSection.tsx` | Subtitle copy |
| `apps/admin/src/features/dashboard/sections/QuickActionsSection.tsx` | Navigate to Approval Center |
| `mobile-app/package-lock.json` | Stack dependency lockfile |

---

## 5. APIs reused

| Operation | Endpoint |
| --- | --- |
| List leave | `GET /api/leave-requests` (`status`, `per_page`, `page`) |
| Approve leave | `POST /api/leave-requests/{id}/approve` |
| Reject leave | `POST /api/leave-requests/{id}/reject` |
| Review queue | `GET /api/lesson-plans/review-queue` |
| List lesson plans | `GET /api/lesson-plans` (`submission_status`) |
| Lesson plan detail | `GET /api/lesson-plans/{id}` |
| Approve lesson plan | `POST /api/lesson-plans/{id}/approve` |
| Reject lesson plan | `POST /api/lesson-plans/{id}/reject` |

**Note:** There is no `GET /leave-requests/{id}`; leave detail uses the list payload passed through navigation (detail query uses `initialData`).

---

## 6. APIs created

**None.**

---

## 7. Invalidation

On successful approve/reject:

- `queryKeys.approvals.all`
- `queryKeys.dashboard.pendingApprovals()`
- `queryKeys.dashboard.all` (KPI stats)

---

## 8. Risks

| Risk | Mitigation |
| --- | --- |
| Escalated / expired are client-derived, not API enums | Documented; filters apply after normalization |
| Leave detail cannot refetch by id | Pass `item` from list; lesson plans refresh via `GET /lesson-plans/{id}` |
| Lesson plan review queue 403 for non-reviewers | Falls back to `GET /lesson-plans?submission_status=submitted`; panel may show leave only |
| Reject UX is minimal (inline reason, no modal) | Sufficient for framework batch; polish later |
| Escalate action not wired | `ApprovalActionBar` supports it; registry/workflows not added yet |
| Approve/reject invalidates broad dashboard keys | Acceptable for KPI freshness; narrow keys when term filters exist |

---

## 9. Manual test checklist

1. Sign in as Admin with `dashboard.approvals.view` or `dashboard.view`.
2. Command Center → **Pending approvals** panel loads up to 5 items; **View all** opens Approval Center.
3. Filter by status / priority / type; list updates.
4. Open item → detail → approve or reject (reject requires reason).
5. Return to dashboard; pending KPI and panel counts refresh after mutation.
