# Sprints 11–15 — Admin App Workspace Completion Report

**Date:** 2026-06-10
**Scope:** Communication (S11), Operations (S12), Reports & Analytics (S13), CBC surfaces (S14), Accounting surfacing (S15)
**Goal:** Every workspace functional end-to-end against existing backend APIs, with V2 design (heroes, sticky search, filter sheets, skeletons, semantic badges) applied consistently.

---

## Sprint 11 — Communication Workspace V2

| Screen | Changes |
|--------|---------|
| `CommunicationDashboardScreen` | `DashboardHero` (new `communication` variant), 4 KPI widgets (announcements / expired / SMS delivered / SMS failed with health delta), grouped quick actions, elevated recent-announcement cards |
| `AnnouncementsListScreen` | Migrated to `RegistryListLayout` — sticky client-side search, primary "New announcement" CTA, `SkeletonListRows`, `ListEmptyState` with retry |
| `SmsHistoryScreen` | `RegistryListLayout` + **filter bottom sheet** (status), sticky search across contact/title/message, `StatusBadge` per delivery state |
| `TemplatesListScreen` | `RegistryListLayout`, sticky search, V2 cards, skeletons |
| `SmsComposeScreen` | Template picker as `FilterChip` row, **Sender ID selector** (School/Finance — previously unexposed API capability), recipient count, V2 inputs, segment/cost estimate |
| `CommunicationScreen` (orphan placeholder) | **Deleted** |

## Sprint 12 — Operations Workspace V2 + new coverage

### Polish (V2 patterns)
- `OperationsDashboardScreen` — hero (`operations` variant), KPI grid, sections reorganised into **Transport / Logistics / Front desk & students**.
- `TripsListScreen`, `VehiclesListScreen` — sticky **server-side search**, V2 cards, skeletons; vehicles get icon delete affordance.
- `InventoryListScreen` — search + low-stock filter sheet, stock badges, rows now open the new item detail.
- `RequisitionsListScreen` — filter sheet (status) + sticky search + semantic status badges.
- `VisitorsListScreen` — filter sheet (presence) + sticky search + on-site badges.
- `AssetsListScreen` — filter sheet (status) + sticky search + status badges.
- `OperationsScreen` (orphan placeholder) — **deleted**.

### New screens (previously API-only, no UI)
| Screen | Backend route | New core hook |
|--------|---------------|---------------|
| `RequirementsRosterScreen` | `GET /teacher/requirements/students` | `useInfiniteRequirementsStudents` |
| `RequirementsStudentScreen` | `GET /teacher/requirements/students/{id}/templates` | existing `useStudentRequirements` |
| `LibraryBooksScreen` | `GET /library/books` | `useInfiniteLibraryBooks` |
| `InventoryItemDetailScreen` | `GET /inventory/items/{id}` | `useInventoryItem` |

Core additions: `operationsApi.listRequirementsStudents / listLibraryBooks / getInventoryItem`, types `RequirementsStudentRow`, `LibraryBookRecord`, extended `InventoryItemRecord` (description, unit_cost), new query keys.

## Sprint 13 — Reports & Analytics

- `ReportsHubScreen` — hero (`reports` variant), KPI widgets, quick actions including the new Executive Analytics, elevated recent-weekly cards.
- **NEW `ExecutiveAnalyticsScreen`** — full executive charting workspace in the Reports stack:
  - Period switcher (week/month/term/year) via `useExecutiveAnalytics` (now accepts `enabled`).
  - KPIs: collected, outstanding, inventory alerts, fixed assets (skeleton grid while loading).
  - 7 charts: fee collections, enrollment trend, attendance %, exam performance, staff growth, visitor traffic, enrollment mix pie.
  - Native share of the summary.
- `WeeklyReportsListScreen` — `RegistryListLayout`, sticky search, skeletons, V2 cards.
- `ReportsScreen` (orphan placeholder) — **deleted**.

## Sprint 14 — CBC surfaces

- `AssessmentsScreen`, `ReportCardsScreen` — skeleton loading rows replace spinners (search-first flows already V2 from Sprint 9/10).
- CBC formative/summative assessments, performance levels, and report-card grades remain fully readable via assessment history + report cards (already wired).
- **Blocked on backend:** CBC structure management (strands, sub-strands, competencies, rubrics) — these resources exist only as web routes (`routes/web.php`); no mobile API. Building UI now would create non-functional placeholders. See "Backend-blocked backlog" below.

## Sprint 15 — Accounting surfacing

- `FinanceDashboardScreen` — new **"Accounting & reporting"** section (RBAC-gated by `reports.view`) with cross-stack jumps: Expense reports, Executive analytics, Board pack.
- Payroll remains in Staff 360 (`PayrollTab` + payslip download) — already functional.
- **Blocked on backend:** general ledger, journals, trial balance, P&L, balance sheet, budgets, expense CRUD — none exist in `routes/api.php` (web only). Deliberately not stubbed.

## Cross-cutting wiring

- `DashboardHero` gained `communication`, `operations`, `reports` variants.
- **Deep links** added for all Communication screens (announcements detail/new, sms, sms detail, templates), all Operations registries/details (inventory, requisitions, visitors, assets, requirements, library), and all Reports screens (executive, board pack, expenses, weekly + detail).
- Notification deep links for announcements now resolve to **AnnouncementDetail** when an id is present (previously always landed on the list).
- New shared `OpsListCard` component for Operations registries (elevated card, lines, semantic badge, optional right slot).
- All three orphan placeholder screens removed.

## Verification

- `npm run typecheck` (apps/admin): **clean** (covers `@erp/core` + `@erp/ui` via workspace resolution).
- No linter errors across edited folders.

## Backend-blocked backlog (build APIs first)

| Area | Missing API |
|------|-------------|
| CBC structure | strands / substrands / competencies / rubrics REST endpoints |
| Accounting | GL, journals, trial balance, P&L, balance sheet, budgets, expense CRUD |
| Communication | `GET /communication/logs/{id}`, `GET /communication/templates/{id}`, template CRUD, WhatsApp send, recipient picker (class/parent) |
| Operations | `GET /visitors/{id}`, requisition create, inventory stock adjustments, asset lifecycle writes, clinic visit CRUD |
| Reports | weekly report body detail endpoint (mobile shows metadata only) |
