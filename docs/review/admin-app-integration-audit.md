# Admin App Integration Audit

**Status:** Complete (read-only audit)  
**Date:** 2026-06-05  
**Scope:** All implemented Admin App workspaces — navigation, APIs, screens, RBAC, defects, and Play Store stabilization backlog.  
**No code changes** were made.

**Sources:** `docs/execution/*`, `docs/admin-app/*`, `docs/prd/02-MASTER-PRODUCT-BACKLOG.md`, `mobile-app/apps/admin`, `mobile-app/packages/core`, `mobile-app/packages/ui`, Laravel `routes/api.php`, `api-debugging-report.md`.

**Build health:** `npm run typecheck` in `apps/admin` passes (2026-06-05).

---

## Executive summary

The Admin App has a **solid shell** (auth, RBAC provider, drawer + tabs, TanStack Query, shared `@erp/core` / `@erp/ui`) and **production-grade read workspaces** for **Students**, **People/Staff 360**, **Admissions**, **Finance**, and **Academics**. The **Dashboard** and **Approvals** surfaces are **partially wired**. **Settings** is implemented but **blocked on production API deployment** for several endpoints.

| Area | Completion (vs IA/UI target) | Integration health |
|------|------------------------------|-------------------|
| Dashboard | **~42%** | Partial live KPIs; Alerts/Operational status are placeholders; no Overview/Approvals/Alerts tabs |
| Approvals | **~32%** | Leave + lesson plans only; no unified inbox API |
| Students (registry) | **~88%** | Strong list/filters; minor client-side filter gaps |
| Student 360 | **~52%** | 5 tabs live; 7+ IA tabs missing |
| Academics workspace | **~78%** | Sprint 7 MVP complete; CBC/Curriculum deferred |
| People (registry) | **~82%** | Staff directory live; dead `PeopleScreen` export |
| Staff 360 | **~58%** | 4 tabs; Performance/Payroll tab missing |
| Admissions | **~86%** | Read + mutations (enroll/reject/waitlist) |
| Finance | **~82%** | Read-only MVP complete; no Accounting/Payroll areas |
| Settings | **~38%** | 4 read sections; production 404 on Settings Hub routes |

**Top blockers before Play Store:**

1. **Production API drift** — Settings Hub + Student 360 academics routes return **404** on deployed host ([`api-debugging-report.md`](../execution/api-debugging-report.md)).
2. **RBAC taxonomy mismatch** — Laravel Spatie names (`admin.dashboard`, `staff.view`) vs mobile names (`dashboard.view`, `people.view`); server permissions **replace** role presets when non-empty.
3. **Dead dashboard quick actions** — chips render but only Approvals navigates.
4. **Incomplete deep linking** — nested stack routes (Finance, Students, Academics, Dashboard) not in `linking.ts`.
5. **Placeholder drawer modules** — Operations, Communication, Reports (not in this audit’s 10 areas but affect “complete app” perception).

---

## Methodology

For each workspace:

- **Screens implemented** — from `apps/admin/src/features/*` and stack navigators.
- **APIs used** — from `@erp/core` hooks and `packages/core/src/api/*`.
- **Navigation paths** — drawer → tab → stack → screen.
- **Permissions** — `@erp/core` `AdminPermission` + screen-level `useCan`.
- **Dependencies** — cross-workspace hooks, shared UI, backend routes.

Verified via static analysis, execution sprint reports, and grep for `PlaceholderScreen`, `navigate(`, `isError`, `isLoading`.

---

# 1. Dashboard

## 1.1 Target (IA / UI spec)

Role-aware command center with **Overview · Approvals · Alerts** tabs, branch/period selectors, widget grid, drill-through to modules ([`03-admin-ui-specifications.md`](../admin-app/03-admin-ui-specifications.md) §1).

## 1.2 Implemented

| Screen / component | Path | Status |
|--------------------|------|--------|
| `DashboardScreen` | Tab **Dashboard** → `DashboardStack` → `DashboardHome` | Live (permission gate) |
| `DashboardLayout` | Composes sections below | Live |
| `CriticalKpisSection` | Enrollment, attendance, finance, approvals KPIs | **Partial live** (`useKpiWidgetData`) |
| `PendingApprovalsSection` | Preview panel → Approval Center | Live |
| `QuickActionsSection` | Shortcut chips | **Partial** (navigation broken for 3/4 actions) |
| `AlertsSection` | Alert cards | **Placeholder** (`ALERT_PLACEHOLDERS`) |
| `OperationalStatusSection` | Integration health rows | **Placeholder** (`OPERATIONAL_STATUS_PLACEHOLDERS`) |
| `ApprovalCenterScreen` | `Dashboard` → `ApprovalCenter` | Live (separate stack screen, not a tab) |
| `ApprovalDetailScreen` | `Dashboard` → `ApprovalDetail` | Live |

**Navigation:** `DrawerNavigator` → `Workspace` (tabs) → `Dashboard` → `DashboardStackNavigator`.

## 1.3 APIs used

| API | Hook / adapter | Widget / section |
|-----|----------------|------------------|
| `GET /dashboard/stats` | `useDashboardStats` | Role-shaped KPIs (enrollment, attendance, finance tiles) |
| `GET /students` (count proxy) | `useKpiWidgetData` | Enrollment KPI |
| `GET /payments`, `GET /invoices` | `useKpiWidgetData` | Finance KPIs (role-dependent) |
| Leave + lesson-plan queues | `usePendingApprovals` → `fetchPendingApprovalsSummary` | Pending approvals KPI + panel |

**Not used (spec):** `GET /finance/summary`, `GET /hr/summary`, `GET /transport/summary`, `GET /approvals` (unified), `GET /alerts`, `GET /announcements`.

## 1.4 Permissions

| Gate | Permission(s) |
|------|----------------|
| Area (tab visibility) | `dashboard.view` via `AREA_VIEW_PERMISSIONS` |
| Screen gate | `useCan('dashboard.view')` |
| KPI widgets | Per-widget in `DASHBOARD_WIDGET_REGISTRY` (`students.view`, `finance.view`, etc.) |
| Alerts section | `dashboard.alerts.view` OR `dashboard.view` |
| Approvals panel | `dashboard.approvals.view` OR `dashboard.view` |

## 1.5 Dependencies

- **Approvals workspace** — shared `ApprovalCenterScreen` on dashboard stack.
- **Finance / Students** — KPI adapters pull cross-domain endpoints.
- **RBAC** — `useRbac`, `useVisibleDashboardWidgets`.

## 1.6 Defects

| Severity | Issue |
|----------|-------|
| **Critical** | Laravel returns `admin.dashboard`; mobile requires `dashboard.view` — Admin users with server permissions may **lose Dashboard tab** (preset fallback skipped when permissions array non-empty). |
| **Medium** | UI spec **segmented tabs** (Overview / Approvals / Alerts) not implemented — single scroll only. |
| **Medium** | Quick actions `qa_students`, `qa_finance`, `qa_admissions` have **no `onPress` navigation** (`QuickActionsSection` only handles `qa_approvals`). |
| **Medium** | Alerts and Operational status are **static placeholders** — misleading in production. |
| **Low** | No branch switcher, period selector, global search, notification bell, approvals badge in chrome ([`GlobalAppHeader`](../packages/ui/src/layout/GlobalAppHeader.tsx) is title + menu only). |
| **Low** | Deep link `schoolerpadmin://dashboard` maps to tab only — no nested `ApprovalCenter` path in `linking.ts`. |

**Completion: ~42%**

---

# 2. Approvals

## 2.1 Target

Unified Approval Center for leave, expenses, requisitions, lesson plans, transport changes, admissions, etc. (IA §8, backlog E4).

## 2.2 Implemented

| Screen | Path | Status |
|--------|------|--------|
| `ApprovalCenterScreen` | Dashboard stack | Live |
| `ApprovalDetailScreen` | Dashboard stack | Live |
| `PendingApprovalsPanel` | Embedded in Dashboard | Live |

**Sources aggregated client-side** (`approvals/normalize.ts`, `useApprovalList`):

| Source type | API | Actions |
|-------------|-----|---------|
| Leave | `GET /leave-requests`, `POST approve/reject` | Live |
| Lesson plan | `GET /lesson-plans/review-queue`, `GET /lesson-plans/{id}`, `POST approve/reject` | Live |

## 2.3 APIs used

See `packages/core/src/api/approvals.api.ts` — reuses existing Laravel routes only.

**Missing vs IA:** requisitions, driver-change, special assignments, expenses, fee concessions, admissions, staff profile changes — **no mobile aggregation**.

## 2.4 Permissions

| Gate | Permission(s) |
|------|----------------|
| View center | `dashboard.approvals.view` OR `dashboard.view` |
| Leave actions | Server-side approver role / subordinate rules |
| Lesson plan actions | Senior Teacher / Admin (API enforced) |

## 2.5 Dependencies

- Mounted on **Dashboard stack** (not standalone drawer area).
- Academics **Moderation** screen duplicates lesson-plan queue with richer context.

## 2.6 Defects

| Severity | Issue |
|----------|-------|
| **Critical** | **Not a unified inbox** — only 2 of ~10 approval domains; product promise unmet. |
| **Medium** | **Duplicate UX** — lesson plans appear in Approvals and Academics ▸ Moderation. |
| **Medium** | No `GET /approvals` endpoint — client fan-out will not scale. |
| **Low** | `dashboard.approvals.view` not seeded in Laravel — relies on preset or `dashboard.view` alias gap. |

**Completion: ~32%**

---

# 3. Students (registry)

## 3.1 Target

Searchable, filterable student directory with drill-down to Student 360 (IA §Students).

## 3.2 Implemented

| Screen | Path | Status |
|--------|------|--------|
| `StudentRegistryScreen` | Tab **Students** → `StudentRegistry` | Live |

## 3.3 APIs used

| API | Hook |
|-----|------|
| `GET /students` | `useInfiniteStudentList` |
| `GET /classes` | `useClassrooms` |
| `GET /classes/{id}/streams` | `useClassroomStreams` |

**Client-side filters:** gender, enrollment status, grade level (server lacks these on list).

## 3.4 Permissions

| Gate | Permission(s) |
|------|----------------|
| Area tab | `students.view` |
| Screen | `useCan('students.view')` |

## 3.5 Dependencies

- Navigates to **Student 360** with optional `summary` prefetch.
- **Admissions** post-enroll navigates here via drawer nested navigation.

## 3.6 Defects

| Severity | Issue |
|----------|-------|
| **Medium** | Gender/status filters applied **client-side** after fetch — inaccurate pagination totals. |
| **Low** | Deep linking: `linking.ts` has `Students: 'students'` but **no** `StudentDetail` / `ReportCardDetail` nested paths. |
| **Low** | No archive/alumni toggle (API excludes archived; no admin path to view alumni). |

**Completion: ~88%**

---

# 4. Student 360

## 4.1 Target

12+ tabs: Overview, Academics, CBC, Report Cards, Attendance, Fees, Family, Health, Discipline, Transport, Requirements, Documents (UI spec §2).

## 4.2 Implemented

| Screen / tab | Path | Status |
|--------------|------|--------|
| `StudentDetailScreen` | `Students` → `StudentDetail` | Live shell |
| Overview tab | `OverviewTab` | Live (`useStudentStats`, detail) |
| Attendance tab | `AttendanceTab` | Live (`useStudentAttendanceTrend`) |
| Academics tab | `AcademicsTab` | Live (gated `academics.view`) |
| Fees tab | `FeesTab` | Live (gated `finance.view`) |
| Family tab | `FamilyTab` | Live (from detail payload) |
| `ReportCardDetailScreen` | `Students` → `ReportCardDetail` | Live |

## 4.3 APIs used

| Tab | APIs |
|-----|------|
| Overview | `GET /students/{id}`, `GET /students/{id}/stats` |
| Attendance | `GET /students/{id}/attendance-calendar` |
| Fees | `GET /students/{id}/statement` |
| Family | Embedded in `GET /students/{id}` (`parent`, `guardians`) |
| Academics | `GET /students/{id}/academic-summary`, `assessment-history`, `GET /report-cards` |

## 4.4 Permissions

| Tab | Permission(s) |
|-----|----------------|
| Shell | `students.view` |
| Academics | `academics.view` |
| Fees | `finance.view` |

## 4.5 Dependencies

- Shares hooks with **Academics workspace** and **Finance Statements**.
- **Admissions** enrollment success navigates here.

## 4.6 Defects

| Severity | Issue |
|----------|-------|
| **Critical** | **Production 404** on `academic-summary` / `assessment-history` when backend not deployed ([`api-debugging-report.md`](../execution/api-debugging-report.md)) — Academics tab shows "Could not load academic data". |
| **Medium** | **7+ tabs missing** — Health, Transport, Discipline, Requirements, Documents, CBC, standalone Report Cards tab per IA. |
| **Medium** | **Duplicate report card UI** — `ReportCardDetailScreen` exists in both Students and Academics stacks (divergent implementations). |
| **Low** | Overview uses summary fallback with **null placeholders** when detail loading — acceptable but thin. |
| **Low** | Tab-level error handling good on Attendance/Fees; Academics banner blocks whole tab on summary OR history error. |

**Completion: ~52%**

---

# 5. Academics (workspace)

## 5.1 Target

Dashboard, Assessments, Exams, Marks, Report Cards, CBC Hub, Curriculum, Moderation (IA); Sprint 7 scoped subset without CBC/Curriculum.

## 5.2 Implemented

| Screen | `AcademicsStackNavigator` route | Status |
|--------|--------------------------------|--------|
| `AcademicsDashboardScreen` | `AcademicsDashboard` | Live |
| `AssessmentsScreen` | `Assessments` | Live (student search) |
| `AssessmentHistoryScreen` | `AssessmentHistory` | Live |
| `AssessmentDetailScreen` | `AssessmentDetail` | Live |
| `ExamsListScreen` | `ExamsList` | Live |
| `ExamDetailScreen` | `ExamDetail` | Live |
| `MarksScreen` | `Marks` | Live |
| `MarksMatrixScreen` | `MarksMatrix` | Live |
| `ReportCardsScreen` | `ReportCards` | Live |
| `ReportCardHistoryScreen` | `ReportCardHistory` | Live |
| `ReportCardDetailScreen` | `ReportCardDetail` | Live |
| `ModerationScreen` | `Moderation` | Live |
| `LessonPlanReviewScreen` | `LessonPlanReview` | Live (approve/reject) |

**Drawer path:** `Admissions` sibling → Drawer **Academics** → stack.

## 5.3 APIs used

Per [`sprint-7-academics-workspace-report.md`](../execution/sprint-7-academics-workspace-report.md):

- `GET /exams`, `/exams/{id}`, `/exams/{id}/marking-options`
- `GET /marks`, `/marks/matrix`, `/marks/matrix/context`
- `GET /reports/exams/trends` (dashboard)
- `GET /students`, `/students/{id}/academic-summary`, `/assessment-history`
- `GET /report-cards`, `/report-cards/{id}`
- `GET /lesson-plans/review-queue`, `/lesson-plans/{id}`, approve/reject POST
- `GET /settings/academic-years`, `/terms`, `/classes`

## 5.4 Permissions

| Gate | Permission(s) |
|------|----------------|
| Area | `academics.view` |
| Exams / marks | `academics.view` + `exams.view` (screen-level) |
| Report cards | `report_cards.view` |
| Moderation | `lesson_plans.view` |

## 5.5 Dependencies

- Overlaps **Student 360 Academics** and **Approvals** (lesson plans).
- Uses `@erp/ui/academics` components.

## 5.6 Defects

| Severity | Issue |
|----------|-------|
| **Medium** | No **school-wide report card registry** API — student search path only. |
| **Medium** | **CBC Hub / Curriculum** not started (deferred — empty would be expected; not in navigator). |
| **Medium** | Exam list lacks server filters (`classroom_id`, `term_id`) — client burden. |
| **Low** | `linking.ts` — `Academics: 'academics'` only; no nested exam/report routes. |
| **Low** | Marks read-only — correct for Admin; no progress/submission % aggregate. |

**Completion: ~78%** (of Sprint 7 MVP scope; ~55% of full IA academics tree)

---

# 6. People (workspace)

## 6.1 Target

Staff directory, leave, attendance, performance, roles (IA §People).

## 6.2 Implemented

| Screen | Path | Status |
|--------|------|--------|
| `StaffRegistryScreen` | Tab **People** → `StaffRegistry` | **Live** |
| `PeopleScreen` | — | **Dead code** (placeholder; not in navigator) |

**Note:** UI spec shows **Operations** in bottom tabs; implementation uses **People** instead ([`BottomTabsNavigator.tsx`](../apps/admin/src/navigation/BottomTabsNavigator.tsx)).

## 6.3 APIs used

| API | Hook |
|-----|------|
| `GET /staff` | `useInfiniteStaffList` |
| `GET /staff/filter-options` | `useStaffFilterOptions` |
| `GET /staff/{id}` | `useStaffDetail` (Staff 360) |

## 6.4 Permissions

| Gate | Permission(s) |
|------|----------------|
| Area tab | `people.view` |
| Registry | `people.view` OR `staff.view` |

**RBAC mismatch:** Laravel seeds `staff.view`, not `people.view`. Area gate uses `people.view` only — users with **only** `staff.view` from server may **fail area guard** unless preset fallback applies (empty permissions).

## 6.5 Dependencies

- Staff 360 detail on same stack.
- Leave data shared with **Approvals**.

## 6.6 Defects

| Severity | Issue |
|----------|-------|
| **Critical** | `people.view` vs `staff.view` taxonomy mismatch for drawer/tab gate. |
| **Low** | `PeopleScreen.tsx` exported but unused — confusing dead navigation artifact. |
| **Low** | Deep link `people/:staffId` defined; other stacks not mirrored. |

**Completion: ~82%** (registry only; full People IA includes Leave/Attendance/Performance sub-areas)

---

# 7. Staff 360

## 7.1 Target

Overview, Employment, Leave, Attendance, Performance, Payroll (IA).

## 7.2 Implemented

| Tab | Component | Status |
|-----|-----------|--------|
| Overview | `OverviewTab` | Live |
| Employment | `EmploymentTab` | Live |
| Leave | `LeaveTab` | Live |
| Attendance | `AttendanceTab` | Live |

**Path:** Tab **People** → `StaffDetail`.

## 7.3 APIs used

| Data | API |
|------|-----|
| Profile | `GET /staff/{id}` |
| Leave balances | `GET /staff/{id}/leave-balances` |
| Leave requests | `GET /leave-requests?staff_id=` |
| Attendance history | `GET /staff/{id}/attendance-history` |
| Latest payroll (overview) | `GET /payroll-records?staff_id=` (gated `finance.view`) |

## 7.4 Permissions

| Gate | Permission(s) |
|------|----------------|
| Shell | `people.view` OR `staff.view` |
| Payroll snippet | `finance.view` |

## 7.5 Dependencies

- **Approvals** for pending leave on overview.
- **People registry** navigation.

## 7.6 Defects

| Severity | Issue |
|----------|-------|
| **Medium** | No **Performance** or dedicated **Payroll** tab. |
| **Medium** | No staff edit / photo upload (acceptable for MVP but IA includes actions). |
| **Low** | Attendance history requires Secretary-grade API access — may 403 for some roles without clear UI copy. |
| **Low** | Loading/error/retry patterns present on tabs — **good**. |

**Completion: ~58%**

---

# 8. Admissions

## 8.1 Target

Dashboard KPIs, applications list, Admissions 360 with workflow actions (IA §Admissions).

## 8.2 Implemented

| Screen | Path | Status |
|--------|------|--------|
| `AdmissionsWorkspaceScreen` | Drawer **Admissions** → `AdmissionsWorkspace` | Live |
| `ApplicationDetailScreen` | → `ApplicationDetail` | Live |
| Tabs: Overview, Student, Parents, Documents, Timeline, Enrollment | `application360/tabs/*` | Live |
| Workflow actions | Overview (status/waitlist/reject), Enrollment (enroll) | Live |

## 8.3 APIs used

| API | Purpose |
|-----|---------|
| `GET /admissions/stats` | Dashboard KPIs |
| `GET /admissions` | Paginated list |
| `GET /admissions/{id}` | Detail |
| `GET /admissions/{id}/files/{field}` | Document download |
| `PUT /admissions/{id}/status` | Status update |
| `POST /admissions/{id}/waitlist` | Waitlist |
| `POST /admissions/{id}/reject` | Reject |
| `POST /admissions/{id}/enroll` | Enroll → student |

## 8.4 Permissions

| Gate | Permission(s) |
|------|----------------|
| Area | `admissions.view` |
| Screen | `useCan('admissions.view')` |
| Server | `ApiAdmissionsController` checks role + `admissions.view` |

**Note:** `RolesAndPermissionsSeeder` Admin role **omits** `admissions.view` — may rely on `Comprehensive2025Seeder` or role name check on API only.

## 8.5 Dependencies

- Post-enroll navigation to **Student 360** (drawer → Workspace → Students → StudentDetail).
- Enrollment uses `GET /classes`, transport trips from detail payload.

## 8.6 Defects

| Severity | Issue |
|----------|-------|
| **Medium** | Admissions **not in bottom tabs** (drawer only) — differs from receptionist IA landing. |
| **Medium** | Mutation routes require **backend deploy** after Sprint 5 Batch 2. |
| **Low** | No interview scheduling / fee capture (out of scope). |
| **Low** | List error/loading/refresh — **implemented**. |

**Completion: ~86%**

---

# 9. Finance

## 9.1 Target

Billing, Collections, Statements, Reconciliation, Accounting, Payroll (IA §Finance). Sprint 6 scoped read-only subledger only.

## 9.2 Implemented

| Screen | `FinanceStackNavigator` route | Status |
|--------|------------------------------|--------|
| `FinanceDashboardScreen` | `FinanceDashboard` | Live |
| `BillingListScreen` | `BillingList` | Live |
| `InvoiceDetailScreen` | `InvoiceDetail` | Live |
| `CollectionsScreen` | `CollectionsList` | Live |
| `PaymentDetailScreen` | `PaymentDetail` | Live |
| `StatementsScreen` | `Statements` | Live |
| `ReconciliationScreen` | `ReconciliationList` | Live |
| `TransactionDetailScreen` | `TransactionDetail` | Live (confirm/reject actions) |

**Path:** Tab **Finance** → stack (`initialRouteName: FinanceDashboard`).

## 9.3 APIs used

Per [`sprint-6-finance-workspace-report.md`](../execution/sprint-6-finance-workspace-report.md):

- `GET /dashboard/stats`, `/invoices`, `/invoices/{id}`, `/payments`, `/payments/{id}`
- `GET /students`, `/students/{id}/statement`
- `GET /finance/transactions`, `/finance/transactions/{id}`
- `POST confirm/reject on transactions`

## 9.4 Permissions

| Gate | Permission(s) |
|------|----------------|
| Area | `finance.view` |
| Screens | `useCan('finance.view')` |
| Reconciliation actions | Server-side finance role |

## 9.5 Dependencies

- **Student 360 Fees** shares `useStudentStatement`.
- **Dashboard** finance KPIs.

## 9.6 Defects

| Severity | Issue |
|----------|-------|
| **Medium** | No **Accounting / Payroll** workspace areas (IA children) — expected post-MVP. |
| **Medium** | `linking.ts` — `Finance: 'finance'` with **no nested** billing/collections paths. |
| **Low** | `POST /payments` exists on API but **not exposed** in Admin (read-only MVP — OK). |
| **Low** | Loading/error on list + detail screens — **generally good**. |

**Completion: ~82%** (Sprint 6 MVP); ~45% of full Finance IA

---

# 10. Settings

## 10.1 Target

School, Academic, Finance, Communication, Integrations sections; Roles from People; backup from Operations▸Security (UI spec §Settings).

## 10.2 Implemented

| Section | Component | Status |
|---------|-----------|--------|
| School | `SchoolSettingsSection` | Live read (`useSchoolSettings`) |
| Academic | `AcademicSettingsSection` | Live read (years, terms, classes, streams, subjects) |
| Grading | `GradingSettingsSection` | Live read |
| Roles | `RolesSettingsSection` | Live read |
| API Diagnostics | `ApiDiagnosticsScreen` | **DEV only** (`__DEV__`) |

**Path:** Drawer **Settings** → `SettingsScreen` (hub layout, not placeholder).

## 10.3 APIs used

| API | Hook |
|-----|------|
| `GET /settings/school` | `useSchoolSettings` |
| `GET /settings/academic-years` | `useAcademicYearsSettings` |
| `GET /settings/terms` | `useTermsSettings` |
| `GET /settings/classes` | `useSettingsClasses` |
| `GET /settings/classes/{id}/streams` | `useSettingsStreams` |
| `GET /settings/subjects` | `useSettingsSubjects` |
| `GET /settings/grading` | `useGradingSettings` |
| `GET /settings/roles` | `useRolesSettings` |

## 10.4 Permissions

| Gate | Permission(s) |
|------|----------------|
| Area | `settings.view` |
| Screen | `useCan('settings.view')` |
| Server | `ApiSettingsHubController::assertSettingsAccess` |

## 10.5 Dependencies

- **Academics workspace** uses overlapping `/settings/*` pickers.
- **Diagnostics** probes multiple endpoints.

## 10.6 Defects

| Severity | Issue |
|----------|-------|
| **Critical** | **Production 404** on all `/settings/*` routes when API not deployed — all sections show "Could not load …". |
| **Medium** | Missing IA sections: **Finance**, **Communication**, **Integrations** config. |
| **Medium** | **Read-only only** — no edit flows. |
| **Low** | API Health tool hidden outside `__DEV__` — no production support surface. |
| **Low** | Section error states exist but **no retry button** on some sections (text only). |

**Completion: ~38%**

---

# 11. Cross-cutting verification

## 11.1 Placeholder screens (dead or stub)

| Module | Screen | In navigator? |
|--------|--------|---------------|
| Operations | `OperationsScreen` | Yes (drawer) — **placeholder** |
| Communication | `CommunicationScreen` | Yes — **placeholder** |
| Reports | `ReportsScreen` | Yes — **placeholder** |
| People | `PeopleScreen` | **No** — dead export only |

## 11.2 Broken or incomplete navigation

| Issue | Severity |
|-------|----------|
| Dashboard quick actions (Students, Finance, Admissions) render but **do not navigate** | **Medium** |
| `linking.ts` missing nested stacks: Students (`StudentDetail`), Finance (all sub-screens), Academics, Dashboard (`ApprovalCenter`) | **Medium** |
| UI spec bottom tab **Operations** vs implemented tab **People** | **Low** (documented deviation) |
| No global search / notification deep routes | **Low** |

## 11.3 Duplicate functionality

| Duplication | Locations | Recommendation |
|-------------|-----------|----------------|
| Report card detail | `Students/ReportCardDetailScreen`, `Academics/ReportCardDetailScreen` | Consolidate shared screen or `@erp/ui` |
| Lesson plan moderation | Approvals center + Academics Moderation | Single queue link or hide duplicate |
| Student statement | Finance Statements + Student 360 Fees | Acceptable shared hook |
| Settings class pickers | Settings hub + Academics filters | Acceptable shared hooks |

## 11.4 RBAC mismatches (systemic)

| Mobile permission | Laravel (typical) | Impact |
|-------------------|-------------------|--------|
| `dashboard.view` | `admin.dashboard` | Dashboard tab / widgets hidden |
| `people.view` | `staff.view` | People tab area guard fails |
| `dashboard.approvals.view` | *(not seeded)* | Approvals visibility relies on `dashboard.view` fallback |
| `operations.view` | `transport.view` / `inventory.view` | Operations placeholder only today |
| `admissions.view` | Sometimes missing on Admin role | Drawer area hidden; API may still allow by role name |

**Root cause:** `resolveEffectivePermissions` prefers **non-empty server list** over presets (`permissionModel.ts` §33–42). Taxonomies were never unified (backlog E4).

## 11.5 Missing API endpoints (app expects)

| Endpoint | Affects |
|----------|---------|
| `GET /settings/*` (deploy) | Settings, Academics pickers |
| `GET /students/{id}/academic-summary` (deploy) | Student 360 Academics, Academics workspace |
| `GET /approvals` (new) | Unified Approvals |
| `GET /alerts` (new) | Dashboard Alerts tab |
| `GET /finance/summary` (new) | Dashboard finance widget accuracy |

## 11.6 Error handling & loading states

| Pattern | Assessment |
|---------|------------|
| List screens (Students, Staff, Admissions, Finance, Academics exams) | **Good** — spinner, error text, pull-to-refresh |
| Detail screens (Student/Staff 360) | **Good** — per-tab isolation with retry on Attendance/Fees/Leave |
| Dashboard placeholders | **Poor** — fake alert/ops data without "demo" badge |
| Settings sections | **Fair** — error text; inconsistent retry |
| Global `AppErrorBoundary` | **Present** at root only — no per-stack boundaries |
| Offline / stale cache UX | **Not implemented** (spec calls for stale tags) |

---

# 12. Completion summary

| # | Workspace | % complete | MVP shippable? |
|---|-----------|------------|----------------|
| 1 | Dashboard | **42%** | Partial — misleading placeholders |
| 2 | Approvals | **32%** | Partial — 2 sources only |
| 3 | Students registry | **88%** | Yes |
| 4 | Student 360 | **52%** | Partial — core tabs OK if API deployed |
| 5 | Academics | **78%** | Yes (read-only oversight) |
| 6 | People registry | **82%** | Yes (RBAC fix needed) |
| 7 | Staff 360 | **58%** | Partial |
| 8 | Admissions | **86%** | Yes |
| 9 | Finance | **82%** | Yes (read-only) |
| 10 | Settings | **38%** | Blocked until API deploy |

**Weighted average (10 areas): ~63%** of IA/UI target vision.  
**Play Store “honest MVP” (registry + 360 core + finance read + admissions): ~75%** once API deploy + RBAC fixed.

---

# 13. Defect register

## 13.1 Critical

| ID | Defect | Areas |
|----|--------|-------|
| C1 | Production API missing Settings Hub + assessment-read routes (404) | Settings, Student 360 Academics, Academics |
| C2 | RBAC taxonomy mismatch — server Spatie permissions override presets; `admin.dashboard` ≠ `dashboard.view`, `staff.view` ≠ `people.view` | All modules, nav visibility |
| C3 | Unified Approvals not implemented — only leave + lesson plans | Dashboard, Approvals |

## 13.2 Medium

| ID | Defect | Areas |
|----|--------|-------|
| M1 | Dashboard quick actions non-functional (except Approvals) | Dashboard |
| M2 | Alerts + Operational status show **fake** data without demo labeling | Dashboard |
| M3 | No Dashboard segmented tabs (Overview / Approvals / Alerts) | Dashboard |
| M4 | Student 360 missing IA tabs (Health, Transport, Discipline, Documents, etc.) | Student 360 |
| M5 | Duplicate `ReportCardDetailScreen` implementations | Students, Academics |
| M6 | Duplicate lesson-plan moderation (Approvals vs Academics) | Approvals, Academics |
| M7 | Deep linking incomplete for nested stacks | Navigation |
| M8 | Drawer placeholders (Operations, Communication, Reports) visible to leadership presets | Shell |
| M9 | Admissions mutations + new routes need deploy verification | Admissions |

## 13.3 Low

| ID | Defect | Areas |
|----|--------|-------|
| L1 | Dead `PeopleScreen` export | People |
| L2 | Bottom tab IA deviation (People vs Operations) | Shell |
| L3 | No branch switcher / period selector / global chrome | Shell |
| L4 | Client-side-only student list filters (gender/status) | Students |
| L5 | Settings sections lack retry on some errors | Settings |
| L6 | API diagnostics only in `__DEV__` | Settings |
| L7 | No per-navigator error boundaries | Shell |

---

# 14. Missing features (by workspace)

| Workspace | Not yet implemented (IA/backlog) |
|-----------|----------------------------------|
| Dashboard | Alerts tab, period selector, branch scope, widget drill-through, `GET /alerts` |
| Approvals | Expenses, requisitions, transport changes, concessions, admissions, profile changes |
| Students | Alumni/archive browse, bulk actions |
| Student 360 | Health, Transport, Discipline, Requirements, Documents, CBC portfolio tabs |
| Academics | CBC Hub, Curriculum, school-wide RC registry, exam moderation API |
| People | Leave/Attendance/Performance sub-nav, staff create/edit |
| Staff 360 | Performance tab, payroll history tab |
| Admissions | Interviews, application fees |
| Finance | Accounting, Payroll, payment capture, posting |
| Settings | Finance/Comm/Integrations sections, edit flows, backup link |

---

# 15. Navigation map (as implemented)

```text
AdminRootNavigator
└── DrawerNavigator [RBAC: drawerAreas]
    ├── Workspace → BottomTabsNavigator [RBAC: tabAreas]
    │   ├── Dashboard → DashboardStackNavigator
    │   │   ├── DashboardHome
    │   │   ├── ApprovalCenter
    │   │   └── ApprovalDetail
    │   ├── Students → StudentsStackNavigator
    │   │   ├── StudentRegistry
    │   │   ├── StudentDetail (Student 360 tabs)
    │   │   └── ReportCardDetail
    │   ├── Finance → FinanceStackNavigator
    │   │   ├── FinanceDashboard
    │   │   ├── BillingList → InvoiceDetail
    │   │   ├── CollectionsList → PaymentDetail
    │   │   ├── Statements
    │   │   └── ReconciliationList → TransactionDetail
    │   └── People → PeopleStackNavigator
    │       ├── StaffRegistry
    │       └── StaffDetail (Staff 360 tabs)
    ├── Admissions → AdmissionsStackNavigator
    │   ├── AdmissionsWorkspace
    │   └── ApplicationDetail (6 tabs)
    ├── Academics → AcademicsStackNavigator (14 screens)
    ├── Operations → OperationsScreen [PLACEHOLDER]
    ├── Communication → CommunicationScreen [PLACEHOLDER]
    ├── Reports → ReportsScreen [PLACEHOLDER]
    └── Settings → SettingsScreen (hub sections)
```

---

# 16. Recommended fixes

## 16.1 Immediate (pre-release)

1. **Deploy backend** — merge and deploy commits with `ApiSettingsHubController`, `ApiStudentAssessmentController`, `ApiAdmissionsController` mutations; run `route:cache` on production.
2. **RBAC harmonization** — either seed mobile permission keys on Laravel (`dashboard.view`, `people.view`, …) OR add alias map in `resolveEffectivePermissions` (`admin.dashboard` → `dashboard.view`, `staff.view` → `people.view`).
3. **Wire dashboard quick actions** — navigate to `Workspace` + correct tab/stack (mirror `ApplicationDetailScreen` post-enroll pattern).
4. **Label or remove placeholder dashboard data** — Alerts/Operational status should show `EmptyState` or "Coming soon", not fake metrics.
5. **Verify Admissions mutations** on production with `AdmissionsApiTest` equivalent smoke test.

## 16.2 Short-term (stabilization sprint)

6. Extend `linking.ts` with nested configs for Students, Finance, Academics, Dashboard approvals.
7. Consolidate `ReportCardDetailScreen` into one shared implementation.
8. Add retry buttons to all Settings section error states.
9. Remove or repurpose dead `PeopleScreen.tsx`.
10. Hide Operations/Communication/Reports drawer entries until MVP **or** show explicit "Coming in Sprint N" empty states (already placeholder — add release note in UI).
11. Add permission integration test: login as Admin → assert expected tabs visible.

## 16.3 Post-MVP

12. Dashboard segmented tabs + `GET /alerts`.
13. Unified `GET /approvals` aggregator on Laravel.
14. Student 360 remaining tabs as APIs ship.
15. Operations workspace (Sprint 8+ per operations audit).

---

# 17. Play Store stabilization backlog (prioritized)

Single ordered backlog before public release. **P0** = ship blocker; **P1** = high risk; **P2** = polish.

| Priority | ID | Task | Owner | Est. |
|----------|-----|------|-------|------|
| **P0** | STAB-01 | Deploy API routes: Settings Hub, assessment-read, admissions mutations to production | Backend/DevOps | 0.5–1 d |
| **P0** | STAB-02 | RBAC permission alias or seeder sync (`dashboard.view`, `people.view`, `admissions.view` on Admin/Secretary) | Backend + Mobile | 1–2 d |
| **P0** | STAB-03 | Production smoke test matrix: login, each tab, Student 360 tabs, Settings sections, Admissions enroll | QA | 1 d |
| **P0** | STAB-04 | Remove/replace fake Dashboard alert & operational placeholder data | Mobile | 0.5 d |
| **P1** | STAB-05 | Fix Dashboard quick action navigation (Students, Finance, Admissions) | Mobile | 0.5 d |
| **P1** | STAB-06 | Document known limitations in app (placeholder modules, read-only finance) — store listing + in-app About | Product | 0.5 d |
| **P1** | STAB-07 | EAS production build + env config audit (`EXPO_PUBLIC_API_URL`, OAuth client IDs) | Mobile/DevOps | 1 d |
| **P1** | STAB-08 | Consolidate duplicate ReportCardDetail; dedupe lesson-plan entry points | Mobile | 1 d |
| **P1** | STAB-09 | Deep linking nested routes (notifications prep) | Mobile | 1–2 d |
| **P1** | STAB-10 | Error retry parity on Settings + Student 360 Academics failure messaging (distinguish 404 vs 403) | Mobile | 1 d |
| **P2** | STAB-11 | Delete dead `PeopleScreen`; clean exports | Mobile | 0.25 d |
| **P2** | STAB-12 | Per-stack `AppErrorBoundary` or route-level error screens | Mobile | 1 d |
| **P2** | STAB-13 | Hide drawer placeholders for roles without `operations.view` OR unified "Coming soon" copy | Mobile | 0.5 d |
| **P2** | STAB-14 | Offline/stale indicator on cached dashboard KPIs | Mobile | 1–2 d |
| **P2** | STAB-15 | Production-safe API health screen (non-`__DEV__`) for support staff | Mobile | 1 d |

**Suggested release gate:** STAB-01 through STAB-05 complete + smoke test pass + signed AAB uploaded to internal track.

---

# 18. References

| Artifact | Path |
|----------|------|
| Build plan | [`docs/execution/admin-app-build-plan.md`](../execution/admin-app-build-plan.md) |
| API debugging | [`docs/execution/api-debugging-report.md`](../execution/api-debugging-report.md) |
| Sprint 6 Finance | [`docs/execution/sprint-6-finance-workspace-report.md`](../execution/sprint-6-finance-workspace-report.md) |
| Sprint 7 Academics | [`docs/execution/sprint-7-academics-workspace-report.md`](../execution/sprint-7-academics-workspace-report.md) |
| Sprint 5 Admissions | [`docs/execution/sprint-5-batch-1-report.md`](../execution/sprint-5-batch-1-report.md), [`sprint-5-batch-2-report.md`](../execution/sprint-5-batch-2-report.md) |
| Admin IA | [`docs/admin-app/02-admin-information-architecture.md`](../admin-app/02-admin-information-architecture.md) |
| UI specifications | [`docs/admin-app/03-admin-ui-specifications.md`](../admin-app/03-admin-ui-specifications.md) |
| Navigation | `mobile-app/apps/admin/src/navigation/*` |
| Core APIs | `mobile-app/packages/core/src/api/*` |
| RBAC | `mobile-app/packages/core/src/rbac/*` |

---

*End of Admin App Integration Audit.*
