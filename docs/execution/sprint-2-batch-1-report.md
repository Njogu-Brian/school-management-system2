# Sprint 2 — Batch 1 Report: School Command Center Framework

**Status:** Complete  
**Scope:** Reusable dashboard infrastructure with placeholder data only. No APIs, no real metrics.  
**Verification:** `tsc --noEmit` passes for `packages/ui` and `apps/admin` (strict mode).

---

## 1. Objective

Build the **School Command Center** framework for the Admin dashboard:

- Layout shell and section composition  
- Widget system with **loading / empty / error / success** states  
- Five KPI widgets + four dashboard sections  
- Permission integration (`dashboard.view`, `finance.view`, `students.view`)  

---

## 2. Component architecture

```text
DashboardScreen
  └── DashboardLayout (dashboard.view gate)
        ├── Hero / period placeholder banner
        ├── CriticalKpisSection
        │     └── WidgetGrid
        │           └── [visible widgets from registry]
        │                 └── KpiWidgetBase → WidgetShell + KpiCard
        ├── QuickActionsSection → QuickAction chips (permission-filtered)
        ├── AlertsSection → AlertCard list (dashboard.view / alerts)
        └── OperationalStatusSection → status rows (dashboard.view)

@erp/ui/dashboard          — presentational primitives (no @erp/core dep)
apps/admin/.../dashboard   — registry, placeholders, permission wiring
```

### Layer responsibilities

| Layer | Responsibility |
| --- | --- |
| **`WidgetShell`** | Uniform async chrome: skeleton, empty, error+retry, success slot |
| **`KpiCard`** | Success-state metric body (label, value, delta, icon) |
| **`AlertCard`** | Alert row with severity accent |
| **`QuickAction`** | Tappable shortcut chip |
| **`DashboardSection`** | Titled section with optional subtitle |
| **`WidgetGrid`** | 2-column wrap layout for KPI cells |
| **`DASHBOARD_WIDGET_REGISTRY`** | Widget id → required permissions |
| **`useVisibleDashboardWidgets`** | Filters registry with `useRbac().can()` |
| **`KPI_PLACEHOLDERS`** | Static values until API hooks land |

---

## 3. Files created

### `@erp/ui/src/dashboard/`

| File | Purpose |
| --- | --- |
| `types.ts` | `WidgetDisplayState`, `WidgetSeverity` |
| `WidgetShell.tsx` | loading / empty / error / success wrapper |
| `KpiCard.tsx` | KPI success content |
| `AlertCard.tsx` | Alert list item |
| `QuickAction.tsx` | Quick action chip |
| `DashboardSection.tsx` | Section header + children |
| `WidgetGrid.tsx` | KPI grid layout |
| `index.ts` | Barrel |

### `apps/admin/src/features/dashboard/`

| File | Purpose |
| --- | --- |
| `types/widget.ts` | `DashboardWidgetId`, `DashboardWidgetDefinition` |
| `data/placeholders.ts` | Static KPI, alerts, status, quick actions |
| `config/widgetRegistry.ts` | Permission metadata per widget |
| `hooks/useDashboardWidgets.ts` | Visibility + section helpers |
| `widgets/KpiWidgetBase.tsx` | Shared KPI + `WidgetShell` |
| `widgets/EnrollmentKpiWidget.tsx` | … |
| `widgets/AttendanceKpiWidget.tsx` | … |
| `widgets/CollectionsKpiWidget.tsx` | … |
| `widgets/OutstandingFeesKpiWidget.tsx` | … |
| `widgets/PendingApprovalsKpiWidget.tsx` | … |
| `widgets/widgetMap.tsx` | id → component |
| `sections/CriticalKpisSection.tsx` | Critical KPIs |
| `sections/QuickActionsSection.tsx` | Quick actions |
| `sections/AlertsSection.tsx` | Alerts |
| `sections/OperationalStatusSection.tsx` | Operational status |
| `components/DashboardLayout.tsx` | Command center shell |
| `docs/execution/sprint-2-batch-1-report.md` | This report |

---

## 4. Files modified

| File | Change |
| --- | --- |
| `packages/ui/src/index.ts` | Export `./dashboard` |
| `apps/admin/src/features/dashboard/screens/DashboardScreen.tsx` | Renders `DashboardLayout` instead of placeholder copy |
| `apps/admin/src/features/dashboard/index.ts` | Export layout, registry, hooks, types |

---

## 5. Widget registry & permissions

| Widget | Permissions (≥1 required) | Placeholder metric |
| --- | --- | --- |
| Enrollment KPI | `dashboard.view`, `students.view` | 1,248 enrolled |
| Attendance KPI | `students.view` | 94.2% today |
| Collections KPI | `finance.view` | KES 4.2M MTD |
| Outstanding Fees KPI | `finance.view` | KES 1.1M |
| Pending Approvals KPI | `dashboard.view`, `dashboard.approvals.view` | 7 pending |

**Sections:**

| Section | Gate |
| --- | --- |
| Critical KPIs | Each widget via registry |
| Quick actions | Per-action permission array |
| Alerts | `dashboard.alerts.view` or `dashboard.view` |
| Operational status | `dashboard.view` |
| **Layout root** | `dashboard.view` |

Users without `finance.view` do not see Collections or Outstanding Fees KPIs. Users without `students.view` do not see Attendance (or Enrollment unless they have `dashboard.view`).

---

## 6. Widget state model

`WidgetDisplayState`: `'loading' | 'empty' | 'error' | 'success'`

- **Default today:** all KPI widgets render `success` with `KPI_PLACEHOLDERS`.  
- **Override hook:** `WIDGET_DEMO_STATES` map (empty by default) for local QA.  
- **Future:** TanStack Query `status` maps directly to `WidgetShell` `state` prop.

`WidgetShell` supports optional `onRetry` for error state (wired when API hooks exist).

---

## 7. Future API integration points

| Widget id | Planned endpoint / source | Query key sketch |
| --- | --- | --- |
| `enrollment_kpi` | `GET /dashboard/stats` → enrollment | `['dashboard','enrollment', branchId, termId]` |
| `attendance_kpi` | `GET /dashboard/stats` → attendance | `['dashboard','attendance', branchId, date]` |
| `collections_kpi` | `GET /finance/summary` | `['finance','summary', branchId, period]` |
| `outstanding_fees_kpi` | `GET /finance/summary` | `['finance','outstanding', branchId]` |
| `pending_approvals_kpi` | Approvals aggregate / `GET /dashboard/stats` | `['approvals','pending', branchId]` |
| Alerts section | Alerts feed / early-warning API | `['dashboard','alerts', branchId]` |
| Operational status | Integrations health / backup status | `['system','health']` |

**Integration steps (next batch):**

1. Add `@erp/core/api/dashboard.api.ts` + query hooks.  
2. Replace `KPI_PLACEHOLDERS` reads in `KpiWidgetBase` with hook data.  
3. Map `isLoading` / `isError` / `isEmpty` → `WidgetShell` state.  
4. Implement `onRetry` → `refetch()`.  
5. Wire quick-action `onPress` → navigation with module filters.  

---

## 8. Risks

| Risk | Mitigation |
| --- | --- |
| **Placeholder drift from API shape** | Stable `DashboardWidgetId` registry; map API DTOs in one adapter per widget |
| **Many parallel summary calls** | Batch endpoint or parallel queries with per-widget error boundaries (already supported by `WidgetShell`) |
| **Permission mismatch vs API** | Client hides widgets; server must still enforce branch scope on stats endpoints |
| **Grid layout on small phones** | `WidgetGrid` uses ~48% width cells; test on narrow devices in QA |
| **Quick actions inert** | `onPress` no-op until navigation targets exist (Sprint 2+) |

---

## 9. Blockers

None. Framework is ready for live data wiring in Sprint 2 Batch 2+ without restructuring components.

---

## 10. How to verify manually

1. Log in as a role with `dashboard.view` + `finance.view` + `students.view` (e.g. Super Admin fallback).  
2. Open **Dashboard** tab — see Command Center with KPI grid, quick actions, alerts, status.  
3. Log in as **Bursar** fallback (finance only) — only finance KPIs + dashboard-gated sections appear in Critical KPIs.  
4. Temporarily set `WIDGET_DEMO_STATES.collections_kpi = 'loading'` in `placeholders.ts` to confirm skeleton state.
