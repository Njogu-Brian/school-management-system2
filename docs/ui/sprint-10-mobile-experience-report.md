# Sprint 10 — Mobile Experience Transformation

**Date:** 2026-06-06  
**Scope:** Phase C.1–C.4 + Phase D.1–D.3 (Admin mobile app + `@erp/ui`)

---

## Summary

Sprint 10 shifts focus from feature breadth to **mobile-native usability**: collapsible filter sheets, sticky search, skeleton loading, unified global search, collapsing 360 headers, KPI trend deltas, and a redesigned Settings hub.

---

## Phase C — Mobile UX

### C.1 Filter Experience ✅

**New primitives (`@erp/ui`):**

- `FilterBottomSheet` — slide-up modal with Apply / Clear
- `FilterTriggerButton` — `Filters (n)` chip with active count
- `countActiveFilters()` — shared badge counter

**Applied to:**

| Module | Screen |
|--------|--------|
| Students | `StudentRegistryScreen` |
| Staff | `StaffRegistryScreen` |
| Admissions | `AdmissionsWorkspaceScreen` |
| Approvals | `ApprovalsInbox` |
| Finance | `BillingListScreen`, `ReconciliationScreen` |
| Academics | `ExamsListScreen` (status + year + term in sheet) |

Inline filter walls removed from list headers; filters open in bottom sheet.

### C.2 Sticky Search ✅

**New layout:** `RegistryListLayout` — Gmail-style sticky search + filter trigger; hero scrolls away.

**Applied to:** Students, Staff, Admissions, Finance (Billing, Collections, Reconciliation), Academics (Exams), Approvals (custom sticky chrome).

### C.3 Skeleton Loading ✅

**New:** `SkeletonListRows`, `SkeletonWidgetGrid`  
**Upgraded:** `WidgetShell` loading state (no spinners), `ApprovalList`

**Applied to:** All registry screens above + Admissions KPI grid + Global Search results.

### C.4 Global Search ✅

- `GlobalSearchScreen` — sticky `SearchBar`, module chips, **grouped results by module** (Students, Staff, Finance, …)
- `GlobalAppHeader` — tappable **“Search anything…”** prompt bar (opens global search via existing nav)
- Recent searches use `FilterChip` row

---

## Phase D — Premium Polish

### D.1 Collapsing 360 Headers ✅

- `Profile360Layout` — animated large header fade + fixed compact bar on scroll
- `Profile360CompactBar` — name + subtitle strip
- Wired in `Student360Layout`, `Staff360Layout`, `Admissions360Layout`

### D.2 Dashboard Intelligence ✅

- `kpiAdapters.ts` — period-over-period trends from chart series (`↑ 2.3%` / `↓ 1.1%`)
- Enrollment, collections, outstanding fees use chart deltas when data exists; contextual captions remain as fallback

### D.3 Settings Redesign ✅

- `SettingsHubLayout` — school hero + grouped section cards (Google Workspace Admin feel)
- `SettingsScreen` — school name from API, footer links (Session, About, dev diagnostics) as cards
- `DashboardHero` — new `settings` variant

---

## Files of Note

```
packages/ui/src/filters/          FilterBottomSheet, FilterTriggerButton
packages/ui/src/layout/           RegistryListLayout, Profile360CompactBar
packages/ui/src/feedback/         SkeletonListRows
packages/ui/src/settings/         SettingsHubLayout (redesign)
apps/admin/src/features/*/screens Registry migrations
apps/admin/src/features/search/   GlobalSearchScreen grouped results
apps/admin/src/features/dashboard/adapters/kpiAdapters.ts
```

---

## Verification

```bash
cd mobile-app/apps/admin && npm run typecheck
```

Passing as of sprint completion.

---

## Deferred (Sprint 11+)

Operations, Communication, Reports, CBC, Accounting — per product roadmap.
