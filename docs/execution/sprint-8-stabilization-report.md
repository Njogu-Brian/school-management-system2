# Sprint 8 — Admin App Stabilization and Completion

**Date:** 2026-06-05  
**Scope:** Stabilize partial implementations across Dashboard, Approvals, Student 360, Staff 360, RBAC, deep linking, and consolidation — **before** Operations workspace.  
**Out of scope:** Operations workspace (paused per sprint directive).

**Build health:** `tsc --noEmit` passes in `packages/core`, `packages/ui`, and `apps/admin` (2026-06-05).

---

## Executive summary

Sprint 8 addressed the highest-severity integration defects from [`admin-app-integration-audit.md`](../review/admin-app-integration-audit.md). RBAC aliases now harmonize Laravel Spatie names with mobile canonical keys. Dashboard quick actions navigate correctly. Placeholder Alerts/Operational Status were replaced with live approval-derived alerts and `EmptyState` where no API exists. Approvals is a first-class drawer workspace aggregating leave, lesson plans, and admissions. Student 360 gained four tabs; Staff 360 gained payroll plus three placeholder tabs. Deep linking now covers nested stacks for Students, Finance, Academics, and Approvals.

| Workspace | Pre-sprint | Post-sprint | Delta |
|-----------|------------|-------------|-------|
| Dashboard | ~42% | **~68%** | +26 |
| Approvals | ~32% | **~78%** | +46 |
| Student 360 | ~52% | **~82%** | +30 |
| Staff 360 | ~58% | **~75%** | +17 |
| Students (registry) | ~88% | **~88%** | — |
| Academics | ~78% | **~80%** | +2 |
| People (registry) | ~82% | **~84%** | +2 |
| Admissions | ~86% | **~86%** | — |
| Finance | ~82% | **~82%** | — |
| Settings | ~38% | **~38%** | — |
| Operations | 0% (placeholder) | **0%** | — (not started) |

---

## Priority 1 — Stabilization

### 1.1 RBAC permission harmonization

**Delivered:** `packages/core/src/rbac/permissionAliases.ts` maps Laravel → mobile equivalents, including:

| Laravel / legacy | Mobile canonical |
|------------------|------------------|
| `admin.dashboard` | `dashboard.view` |
| `staff.view` | `people.view` |
| `dashboard.approvals.view` | `approvals.view` |
| `inventory.view` / `inventory.manage` | `operations.view` |
| Plus finance, transport, admissions, academics aliases |

`permissionModel.ts` expands aliases on grant; `can()` checks reverse alias grants. `AREA_VIEW_PERMISSIONS` includes `approvals` area and `staff.view` for People.

### 1.2 Dashboard completion

| Item | Status |
|------|--------|
| Students quick action → `Students/StudentRegistry` | Fixed |
| Finance quick action → `Finance/CollectionsList` | Fixed |
| Admissions quick action → `Admissions/AdmissionsWorkspace` | Fixed |
| Approvals quick action → `Approvals/ApprovalsHome` drawer | Fixed |
| Alerts section | Live pending approvals (up to 5) or `EmptyState` |
| Operational status | `EmptyState` (no mobile integration-health API) |

**Helper:** `apps/admin/src/navigation/navigateWorkspace.ts` — cross-tab and cross-drawer navigation.

### 1.3 Deep linking

`apps/admin/src/navigation/linking.ts` extended with nested paths:

| Area | Example paths |
|------|----------------|
| Students | `students/:studentId/:tab?`, `students/report-cards/:reportCardId` |
| Finance | `finance/invoices/:invoiceId`, `finance/payments/:paymentId`, etc. |
| Academics | `academics/exams/:examId`, `academics/lesson-plans/:lessonPlanId`, etc. |
| Approvals | `approvals`, `approvals/:id` |
| Admissions | `admissions/:applicationId` |
| People | `people/:staffId` |

`StudentDetail` accepts optional `tab` route param for Student 360 tab deep links.

### 1.4 Consolidation

| Item | Resolution |
|------|------------|
| Duplicate `ReportCardDetailScreen` | Single `SharedReportCardDetailScreen` in `features/shared/screens/`; Students + Academics stacks re-export thin wrappers |
| Lesson plan moderation duplication | **Canonical flow:** Approvals workspace. `ModerationScreen` redirects to Approvals with `EmptyState` + action button. `LessonPlanReviewScreen` retained for academics detail context |

---

## Priority 2 — Student 360 completion

| Tab | Screen | API reused | Notes |
|-----|--------|------------|-------|
| Health | `HealthTab.tsx` | `GET /students/{id}` | `has_allergies`, `allergies_notes`, `is_fully_immunized`, `blood_group`, `preferred_hospital`, emergency contact |
| Transport | `TransportTab.tsx` | `GET /students/{id}` (`trip_id`) + `GET /routes/{id}` | Trip-backed routes API |
| Requirements | `RequirementsTab.tsx` | `GET /teacher/requirements/students/{id}/templates` | Term checklist |
| Documents | `DocumentsTab.tsx` | — | `EmptyState` — no student documents read API |

**Core changes:** `StudentRecord` / `StudentDetail` extended with health + transport fields; `toStudentDetail()` maps API payload; `Student360TabId` extended in `@erp/core` and `@erp/ui`.

**Operations API layer (no new backend):** `packages/core/src/api/operations.api.ts`, `useTransportRoute`, `useStudentRequirements`.

---

## Priority 3 — Staff 360 completion

| Tab | Screen | API reused | Notes |
|-----|--------|------------|-------|
| Payroll | `PayrollTab.tsx` | `GET /payroll-records?staff_id=` | Full history list |
| Performance | `PerformanceTab.tsx` | — | Placeholder `EmptyState` |
| Documents | `DocumentsTab.tsx` | — | Placeholder `EmptyState` |
| Training | `TrainingTab.tsx` | — | Placeholder `EmptyState` |

`Staff360TabId` extended in `@erp/ui`.

---

## Priority 4 — Approvals workspace

| Deliverable | Status |
|-------------|--------|
| First-class drawer area | `ApprovalsStackNavigator` wired in `DrawerNavigator` |
| Aggregate leave requests | Reused `GET /leave-requests` |
| Aggregate lesson plans | Reused review queue + filtered index |
| Aggregate admissions | Reused `GET /admissions?status=` (pending, under_review, waitlisted, enrolled, rejected) |
| Requisitions | **Not included** — no API in `routes/api.php` |
| Pending / Approved / Rejected | `ApprovalFilters` status chips on `ApprovalsWorkspaceScreen` |
| Shared inbox component | `ApprovalsInbox` used by drawer + dashboard `ApprovalCenterScreen` |

**New source type:** `online_admission` in approval domain (`admissionToApprovalItem`). Admission items open in Admissions workspace via detail link (`canAct: false` — mutations stay in `ApplicationDetailScreen`).

---

## APIs reused vs added

### Reused (existing Laravel Sanctum routes)

| Endpoint | Consumer |
|----------|----------|
| `GET /students/{id}` | Health + Transport assignment fields |
| `GET /routes/{id}` | Student 360 Transport tab |
| `GET /teacher/requirements/students/{id}/templates` | Student 360 Requirements tab |
| `GET /payroll-records?staff_id=` | Staff 360 Payroll tab |
| `GET /leave-requests` | Approvals workspace |
| `GET /lesson-plans/review-queue`, `GET /lesson-plans` | Approvals workspace |
| `POST /lesson-plans/{id}/approve\|reject` | Approval detail actions |
| `GET /admissions`, `GET /admissions/{id}` | Approvals workspace + detail |
| `GET /report-cards/{id}` | Shared report card detail |
| `GET /dashboard/stats`, pending approval fetchers | Dashboard KPIs + alerts |

### Added

**None.** All Sprint 8 data access reuses existing backend routes. Client-only additions: permission alias map, approval normalization for admissions, operations API client wrapping existing transport/requirements routes.

---

## Screens implemented / changed

### New screens

| Screen | Location |
|--------|----------|
| `ApprovalsWorkspaceScreen` | `features/approvals/screens/` |
| `SharedReportCardDetailScreen` | `features/shared/screens/` |
| `HealthTab`, `TransportTab`, `RequirementsTab`, `DocumentsTab` | `features/students/student360/tabs/` |
| `PayrollTab`, `PerformanceTab`, `DocumentsTab`, `TrainingTab` | `features/people/staff360/tabs/` |
| `ApprovalsInbox` | `features/approvals/components/` |

### New navigation

| File | Role |
|------|------|
| `ApprovalsStackNavigator.tsx` | Drawer stack |
| `approvalsStackTypes.ts` | Param list |
| `navigateWorkspace.ts` | Cross-workspace navigation |

### Modified (stabilization)

- `QuickActionsSection`, `AlertsSection`, `OperationalStatusSection`
- `ModerationScreen` (redirect to Approvals)
- `StudentDetailScreen`, `StaffDetailScreen`
- `linking.ts`, `types.ts`, `areaRoutes.ts`, `DrawerNavigator.tsx`
- `ReportCardDetailScreen` (students + academics → shared)
- `ApprovalDetailScreen`, `ApprovalCenterScreen`, approval registry/filters
- RBAC: `permissionAliases.ts`, `permissions.ts`, `permissionModel.ts`, `rolePresets.ts`, `navigation.ts`

### Removed / cleaned

- Dead `PeopleScreen` export from `features/people/index.ts` (placeholder not in navigator)

---

## Remaining blockers before Play Store release

| Priority | Blocker | Impact |
|----------|---------|--------|
| **P0** | Production API 404 on Settings Hub + some Student 360 academics routes | Settings and academics tabs fail on deployed host |
| **P0** | No student/staff documents read APIs | Documents tabs are intentional placeholders |
| **P1** | No unified `/approvals` backend — client merges sources | Acceptable for MVP; pagination across sources is client-side |
| **P1** | No requisitions API | Cannot add procurement approvals |
| **P1** | No mobile integration-health / alerts feed API | Operational status remains `EmptyState` |
| **P1** | Dashboard IA tabs (Overview / Approvals / Alerts segmented control) not implemented | Single scroll layout remains |
| **P2** | Operations, Communication, Reports drawer placeholders | Perceived incomplete app |
| **P2** | No payslip PDF / performance review APIs | Staff 360 placeholders |
| **P2** | Branch switcher, period selector, notification deep links | Chrome / IA gaps from audit |

**Operations workspace:** Explicitly **not started** this sprint.

---

## TypeScript verification

```bash
cd mobile-app/packages/core && npx tsc --noEmit
cd mobile-app/packages/ui && npx tsc --noEmit
cd mobile-app/apps/admin && npx tsc --noEmit
```

All three pass as of 2026-06-05.

---

## Recommended next sprint

1. Deploy Settings Hub + academics API routes to production (unblock Settings + Student 360 academics).
2. **Operations workspace** discovery is complete ([`01-operations-workspace-audit.md`](../operations/01-operations-workspace-audit.md)) — implementation can begin when prioritized.
3. Student/staff document APIs (smallest backend add) or defer to web-only.
4. Dashboard segmented tabs + branch/period selectors per UI spec.

---

*Sprint 8 stabilization report — generated after implementation pass.*
