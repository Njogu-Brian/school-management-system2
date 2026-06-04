# 03 — Admin App UI Specifications (Core Frames)

> **Scope:** High-fidelity UI specs for the **5 core frames** — Dashboard, Student 360, Finance Cockpit, CBC Hub, Settings Hub — suitable for **Google Stitch / Figma** generation.
> **Platform:** React Native (Expo) Admin App · **mobile-first, tablet-optimized**, scales to desktop/web.
> **Authored as:** UX Architect · Product Designer · School ERP Consultant. **No code.**
> **Inputs:** [`01-admin-discovery.md`](./01-admin-discovery.md), [`02-admin-information-architecture.md`](./02-admin-information-architecture.md), API inventory in [`../system-audit/02-module-inventory.md`](../system-audit/02-module-inventory.md). New endpoints are marked **(new)**.

---

## 0. Global design system (applies to every frame — do not redraw per screen)

### 0.1 Breakpoints & responsive intent
| Tier | Width | Layout model |
|------|-------|--------------|
| **Mobile** | < 600dp | Single column; bottom tab bar (5 max) + "More"; stacked cards; full-screen detail |
| **Tablet** | 600–1024dp | **Master–detail** (list left / detail right); left nav rail collapsible; 2-col widget grid |
| **Desktop/Web** | > 1024dp | Persistent left nav rail + content + optional right context panel; 3–4-col widget grid |

**Mobile-first rule:** design the mobile column first; tablet/desktop add a persistent rail and a detail/context pane — content priority never changes.

### 0.2 Navigation shell (from IA §1–2)
- **Mobile:** bottom tabs = Dashboard · Students · Finance · Operations · **More** (drawer holds Admissions, Academics, People, Communication, Reports, Settings). Top bar = branch switcher, search, bell, approvals badge.
- **Tablet/Desktop:** left nav rail (11 areas, permission-filtered) + secondary sub-nav as tabs; top bar persistent.
- Menu is **permission-first + branch-scoped**.

### 0.3 Design tokens (Stitch/Figma variables)
| Token group | Values |
|-------------|--------|
| Spacing | 4 / 8 / 12 / 16 / 24 / 32 (8pt base) |
| Radius | card 16, control 12, chip 999 |
| Elevation | sm (cards), md (sheets), lg (modals) |
| Type scale | Display 28/Bold · H1 22 · H2 18 · Body 15 · Caption 13 · Mono (numbers/codes) |
| Color (themeable per tenant) | primary, primary-light, surface, surface-alt, text, text-muted, border, success, warning, danger, info |
| Status colors | paid/approved=success · partial/pending=warning · unpaid/rejected/overdue=danger · info=info |
| Tap target | ≥ 44×44 · min contrast AA · light + dark |

### 0.4 Shared component library (`@erp/ui`)
`AppHeader` (title, back, actions) · `BranchSwitcher` · `GlobalSearchBar` · `NotificationBell` · `ApprovalsBadge` · `StatTile` · `TrendChart`/`DonutChart`/`BarChart` · `DataCard` · `ListRow` (avatar, title, meta, status) · `FilterChips` · `SegmentedTabs` · `StatusBadge` · `Avatar` · `Sheet`/`BottomSheet` · `Drawer` · `FormField` (text/select/date/file) · `Button`/`IconButton` · `EmptyState` · `Skeleton` (tile/row/grid) · `ErrorBanner` · `Toast` · `ConfirmDialog` · `Pagination`/`InfiniteList`.

### 0.5 Universal state pattern (referenced as "standard states")
- **Loading:** skeletons (tiles for dashboards, rows for lists, header+tab skeleton for profiles); button spinners on submit.
- **Empty:** `EmptyState` (illustration + title + subtitle + optional CTA); copy is screen-specific.
- **Error:** `ErrorBanner` + Retry; offline variant "Showing cached data" when cache exists; per-widget/tab isolation (never blank the whole screen).
- **Offline:** top offline strip; queued writes show "pending sync" chip.
- **Success:** toast; destructive actions use `ConfirmDialog`.

### 0.6 Cross-cutting behaviors
- **Branch scope** on every data call (`branch_id`); **permission gating** hides actions/tabs/widgets.
- **Maker-checker** financial actions show a "Sent for approval" state.
- **Period selector** (term/year) on analytic surfaces.
- **Deep links** from notifications/search open the exact frame + tab + entity.

---

# 1. DASHBOARD

### Purpose
Role-aware, branch-aware command center: glanceable health, pending work (approvals), and early-warning alerts, each drilling into its source.

### Target users
Principal, Director, Admin, Finance Director/Bursar/Accountant, Academic Director/Head Teacher (role-shaped widgets). Operational roles land on their module instead.

### Layout structure
- **Header:** branch switcher · title "Dashboard" · period selector · search · bell · approvals badge.
- **Segmented tabs:** **Overview · Approvals · Alerts**.
- **Overview body:** responsive widget grid.
- **Approvals tab:** embedded Approval Center (see §ApprovalCenter in IA §8).
- **Alerts tab:** prioritized alert list grouped by domain.

### Components
`SegmentedTabs`, `StatTile`, `TrendChart`, `DonutChart`, `DataCard`, `ListRow`, `FilterChips`, `Skeleton`, `EmptyState`, `ErrorBanner`, `PeriodSelector`, quick-action `Button`s.

### Widget definitions (Overview)
| Widget | Visual | Data | Tap → |
|--------|--------|------|-------|
| Enrollment & attendance today | StatTile + sparkline | total students, present % | Students / Attendance |
| Fee collection vs target | DonutChart + trend | collected, target, % | Finance ▸ Dashboard |
| Outstanding / defaulters | StatTile | balance, defaulter count | Finance ▸ Collections |
| Unreconciled transactions | StatTile (warning) | count, amount | Finance ▸ Reconciliation |
| Staff present | StatTile | clocked-in / total | People ▸ Attendance |
| Pending approvals | StatTile (badge by type) | counts | Approval Center |
| Admissions funnel | BarChart | applied→enrolled | Admissions |
| Coverage / marks progress | Progress bars | % submitted, % covered | Academics |
| Report-card publish status | StatTile | published / pending | Report Cards |
| Incidents / visitors today | StatTile | counts | Operations |
| Transport on-route | StatTile (live) | active trips | Operations ▸ Transport |
| Quick post announcement | Action card | — | Communication composer |

### Actions
Switch branch · change period · tap widget (drill-through) · reorder/hide widgets (P1) · approve/reject from Approvals tab · dismiss/snooze alert · quick announcement.

### Empty states
Per widget: "No data for selected period." Approvals: "No pending approvals 🎉". Alerts: "All clear — no alerts."

### Loading states
Skeleton tile grid; Approvals/Alerts row skeletons. Widgets load independently (no global blocker).

### Error states
Per-widget inline error + Retry; cached value shown with "stale" tag when available; never blank the dashboard.

### Mobile layout
Single-column scroll: priority order = Approvals summary card → Fee collection → Attendance → Outstanding → Alerts preview → remaining widgets. Tabs as top segmented control.

### Tablet layout
2-col widget grid; optional right rail pinning Approvals summary. Tabs as a sub-header.

### Desktop layout
Left nav rail + 3–4-col widget grid + right context panel (Approvals/Alerts live preview).

### API dependencies
`GET /dashboard/stats?branch_id&academic_year_id&term_id` · module summaries `GET /finance/summary`, `/hr/summary`, `/transport/summary` · `GET /approvals?status=pending&count` **(new)** · `GET /alerts` **(new)** · `GET /announcements` (quick post → `POST /announcements`).

---

# 2. STUDENT 360

### Purpose
One profile = everything about a learner, as tabs — replacing scattered Family/Medical/Discipline/Academic-History menus (the #1 UX win).

### Target users
Admin, Secretary, Academic Director/Head Teacher, Bursar/Accountant (Fees), Nurse (Health), Class Teacher (scoped), Receptionist.

### Layout structure
- **Entry:** Students list (search + filters: class, stream, status, category) → tap → profile.
- **Profile header (sticky):** avatar, name, adm no, class/stream, status pill (active/alumni/archived), **fee-balance chip**, quick-action menu.
- **Tab bar:** Overview · Academics · CBC & Portfolio · Report Cards · Attendance · Fees · Family · Health · Discipline · Transport · Requirements · Documents (permission-gated; overflow scroll).
- **Body:** selected tab content.

### Components
`ListRow` (list), `Avatar`, `StatusBadge`, `FeeStatusBadge`, `SegmentedTabs`/scrollable `Tabs`, `DataCard`, `StatTile`, `TrendChart`, calendar heatmap, timeline, `FormField`, `Sheet` (quick actions), `EmptyState`, `Skeleton`.

### Widget / tab definitions
| Tab | Key content | Source |
|-----|-------------|--------|
| Overview | attendance %, latest results, fee balance, alerts, next event | aggregate |
| Academics | subjects, exam results, timetable | exams/timetable |
| CBC & Portfolio | strand performance levels, portfolio evidence | CBC (new) |
| Report Cards | term reports, publish status, download PDF | report-cards |
| Attendance | calendar heatmap, trends, at-risk | attendance |
| Fees | statement, invoices, payments, balance, plan; record/pay | finance |
| Family | guardians, siblings, contacts, family-update link | identity |
| Health | medical profile, clinic visits | clinic (new) |
| Discipline | behaviour/incidents, merit/demerit | discipline (new) |
| Transport | route, drop-point, pickup history | transport |
| Requirements | collected vs template | inventory |
| Documents | admission docs, certificates, generated | documents |

### Actions
Header quick actions (permission-gated): record payment · send message · generate document · transfer/promote · archive/restore. Tab actions: download report card, send reminder, add clinic visit, log incident, edit family.

### Empty states
Per tab: "No results published yet" / "No outstanding balance 🎉" / "No clinic visits" / "No transport assigned." List: "No students match your filters."

### Loading states
List row skeletons; profile = header skeleton + active-tab skeleton.

### Error states
Per-tab error + Retry; header loads independently; offline → cached read-only with banner.

### Mobile layout
List → full-screen profile; header collapses on scroll; tabs scrollable; quick actions in a bottom sheet.

### Tablet layout
**Master–detail:** student list (left ~35%) + profile (right); tabs as top sub-nav; quick actions inline in header.

### Desktop layout
Nav rail + list + profile + optional right context panel (e.g. recent activity / audit).

### API dependencies
`GET /students?branch_id&filters` · `GET /students/{id}` · `GET /students/{id}/stats` · `GET /students/{id}/attendance-calendar` · `GET /students/{id}/statement` · `GET /report-cards?student_id` · CBC `GET /students/{id}/competencies` **(new)** · Health `GET /students/{id}/clinic` **(new)** · Discipline `GET /students/{id}/discipline` **(new)** · Transport assignment · Documents `GET /documents?documentable=student&id`. Actions: `POST /payments`, `POST /messages/send`, `POST /document-templates/{id}/generate`, promote/transfer **(new)**, `POST /students/{id}/archive|restore`.

---

# 3. FINANCE COCKPIT

### Purpose
Replace ~25 implementation-detail finance menus with 6 workflow areas; make money movement, balances, and books coherent.

### Target users
Finance Director, Bursar, Accountant; Principal (read); Admin.

### Layout structure
- **Area sub-nav:** Dashboard · Billing · Collections · Reconciliation · Accounting · Payroll.
- **Each area:** header (branch, period, search, primary action) + list/queue + detail drawer/panel.

### Components
`SegmentedTabs` (areas), `StatTile`, `DonutChart`, `TrendChart`, `ListRow`, `FilterChips`, `DataTable` (tablet/desktop), detail `Sheet`/panel, `StatusBadge`, `FormField`, `ConfirmDialog`, maker-checker `ApprovalRibbon`, `EmptyState`, `Skeleton`.

### Widget / area definitions
| Area | Primary surface | Key widgets/actions |
|------|-----------------|---------------------|
| **Dashboard** | KPI grid | collected vs target (donut), outstanding, defaulters, unreconciled, M-Pesa feed, budget vs actual |
| **Billing** | Fee catalog + posting | voteheads/structures list; **posting**: dry-run preview → diff → commit; concessions/discounts/plans |
| **Collections** | Invoices/payments list | record/allocate/reverse payment (maker-checker); statement; balances; clearance; defaulters export |
| **Reconciliation** | Unmatched queue | smart-match suggestions; confirm/reject/share-across-siblings; transaction-fix audit |
| **Accounting** | GL | chart of accounts; journal entries (balanced); trial balance/P&L/BS/cash flow; budgets; expenses/vouchers/vendors; period close |
| **Payroll** | Runs | generate→process→lock; payslips; advances; statutory; "posts to GL" indicator |

### Actions
Post fees (preview→commit) · record/allocate/reverse payment · confirm/reject/share transaction · create journal entry · run/close period · generate statement/payslip · export (async). Financial actions route through **maker-checker** (show "Awaiting approval").

### Empty states
Reconciliation: "All transactions reconciled." Billing: "No fee structures for this term." Accounting: "No journal entries this period." Payroll: "No payroll run for this month."

### Loading states
KPI skeleton tiles; queue/list skeletons; posting preview spinner; statement async generation indicator.

### Error states
Per-widget retry; posting validation errors inline; reconciliation action error → rollback + toast; export failure → retry. Cached dashboard fallback.

### Mobile layout
Areas as scrollable segmented tabs; lists full-width; detail opens as full-screen sheet; posting/reconciliation are step flows (wizard).

### Tablet layout
**Master–detail:** queue/list left + detail/posting-diff right; KPI dashboard as 2-col grid; data tables paginated.

### Desktop layout
Nav rail + area tabs + table + right detail panel; multi-column tables; bulk actions toolbar (e.g. bulk approve, bulk reminder).

### API dependencies
`GET /finance/summary` · Billing: `GET /fee-structures`, `GET /voteheads` **(catalog)**, posting preview/commit `POST /finance/posting/*` **(new/extend)** · Collections: `GET /invoices`, `GET /payments`, `POST /payments`, `GET /students/{id}/statement` · Reconciliation: `GET /finance/transactions`, `POST /finance/transactions/{id}/confirm|reject|share`, `POST /finance/transactions/mark-swimming` · Accounting: GL endpoints **(new)** (`/accounting/coa`, `/accounting/journals`, `/accounting/statements`, `/budgets`), expenses `GET/POST /expenses` **(new in API)** · Payroll: `GET /payroll-records`, run/process/lock **(new in API)**, `GET /payrolls/{id}/payslip`.

---

# 4. CBC HUB

### Purpose
Configure and oversee competency-based curriculum: curriculum library, performance-level/rubric config, coverage tracking, portfolio oversight, CBC report-card templates. (Capture lives in the Staff App.)

### Target users
Academic Director, Head Teacher, Senior Teacher (scoped), Admin.

### Layout structure
- **Sub-nav:** Curriculum · Performance Levels · Coverage · Portfolios · Report Templates.
- Tree/list + detail; coverage uses a class×strand matrix.

### Components
`TreeView` (learning area → strand → sub-strand → outcome), `ListRow`, `MatrixGrid` (coverage), `StatTile`, `BarChart` (level distribution), `FormField`, file uploader (curriculum PDF), `StatusBadge`, `Skeleton`, `EmptyState`.

### Widget / section definitions
| Section | Content | Notes |
|---------|---------|-------|
| Curriculum | learning areas → strands → sub-strands → outcomes tree; upload/verify KICD PDF | LLM-assisted + human-verified ingestion |
| Performance Levels | configure E.E./M.E./A.E./B.E. descriptors per outcome | correct MoE bands (not %) |
| Coverage | class × strand matrix: planned vs delivered %, "behind schedule" flags | from schemes/lessons |
| Portfolios | per-class portfolio completeness; evidence audit | oversight only |
| Report Templates | CBC report-card layout (areas→strands→competencies→narrative) | drives §2 Report Cards |

### Actions
Upload & verify curriculum · edit strand tree · configure performance-level descriptors & rubrics · view coverage drill-down · flag/notify behind-schedule classes · manage report-card template · trigger AI generation (governed).

### Empty states
"No curriculum uploaded for this grade." "No coverage data yet." "No portfolios submitted."

### Loading states
Tree skeleton; matrix skeleton; PDF parse = progress indicator (processing → processed/failed).

### Error states
Parse failure ("Curriculum could not be parsed — re-upload / verify"); matrix load retry; per-section isolation.

### Mobile layout
Sub-nav as segmented tabs; tree as expandable list; coverage matrix → horizontally scrollable / condensed per-class list.

### Tablet layout
Tree (left) + detail (right); coverage matrix full grid; portfolio gallery.

### Desktop layout
Nav rail + sub-nav + tree + detail panel; coverage matrix as full heatmap with filters.

### API dependencies
`GET /cbc/curriculum?grade&learning_area` **(new)** · `POST /cbc/curriculum/upload` + parse status **(extend curriculum-designs)** · `GET/PUT /cbc/performance-levels` **(new)** · `GET /cbc/coverage?class&term` **(new)** · `GET /cbc/portfolios?class` **(new)** · `GET/PUT /cbc/report-templates` **(new)** · AI generate `POST /curriculum-assistant/generate` (governed).

---

# 5. SETTINGS HUB

### Purpose
Unified, tenant/branch-aware configuration — replacing config scattered across Academics/Finance/HR. Progressive disclosure of advanced/rare config.

### Target users
Admin, Super Admin; scoped module owners (e.g. Finance Director → Finance settings).

### Layout structure
- **Sub-nav / sections:** School · Academic · Finance · Communication · Integrations. (Roles & Permissions surfaced from People; Backup/Audit from Operations▸Security — cross-linked.)
- Section = grouped setting cards; edit in inline panel or sheet.

### Components
`SectionList`, `SettingCard` (label, value, control), `FormField` (text/select/toggle/color/file), `BranchSwitcher`, color picker (branding), `FilePicker` (logo), `ConfirmDialog`, `Toast`, `Skeleton`, `EmptyState`, search-within-settings.

### Widget / section definitions
| Section | Settings |
|---------|----------|
| **School** | identity, branding (logo/colors), branches, academic calendar (years/terms/holidays/school days), feature/module toggles, gallery |
| **Academic** | grading schemes, exam types, attendance reason codes/notification rules, report-card defaults |
| **Finance** | payment methods, bank accounts, thresholds, document/receipt settings, COA setup, gateway config |
| **Communication** | SMS/WhatsApp/email/push providers, templates, placeholders, automation (fee reminders), opt-out policy |
| **Integrations** | M-Pesa/Jenga, Google, S3, AI providers, webhooks health; per-tenant credentials (vaulted) |

### Actions
Edit setting (inline) · upload logo · pick brand colors (live preview) · toggle module/feature · configure provider · test integration (e.g. send test SMS, M-Pesa ping) · manage branches.

### Empty states
"No branches configured — add your first branch." "No templates yet." "Integration not connected."

### Loading states
Section/card skeletons; "saving…" inline on controls; test-connection spinner.

### Error states
Per-setting validation; save failure → revert + toast; integration test failure → diagnostic message; permission-restricted settings shown read-only with lock icon.

### Mobile layout
Sections as a list → drill into section → setting cards full-width; sheets for edit.

### Tablet layout
Sections (left list) + settings detail (right); branding live-preview panel.

### Desktop layout
Nav rail + sections + detail + preview; integrations as a status grid.

### API dependencies
`GET/PUT /settings/*` (general, branding, regional, modules) · `GET /app-branding` (mobile feed) · calendar `GET/PUT /academic-config` · `GET/PUT /settings/finance` **(consolidate)** · communication providers/templates · `GET/PUT /integrations/*` **(new)** · branches `GET/POST /branches` **(new)** · roles `GET /roles` & permissions (cross-link to People).

---

## 6. Stitch / Figma generation guidance

### 6.1 Generation order
1. **Design system first:** tokens (0.3), components (0.4), navigation shell (0.2), state patterns (0.5) → the kit everything reuses.
2. **Then the 5 frames** in this order: **Dashboard → Student 360 → Finance Cockpit → CBC Hub → Settings Hub.**
3. For each frame generate **3 artboards**: Mobile (375×812), Tablet (834×1112 master-detail), Desktop/Web (1280×800).

### 6.2 Per-frame artboard checklist
- Default (data) state · Loading (skeleton) · Empty · Error · plus one **key interaction** (Dashboard: Approvals tab; Student 360: Fees tab; Finance: Reconciliation detail/posting diff; CBC: coverage matrix; Settings: branding live-preview).

### 6.3 Prompts should specify
- Mobile-first; 8pt spacing; themeable tokens (light + dark); ≥44dp targets; status-color semantics; master–detail on tablet; left-rail on desktop.
- Reuse named components from §0.4 (consistency across frames).

### 6.4 Out of scope here (later docs)
- The remaining ~50 screens (`04-admin-screen-specs-extended.md`).
- Motion/interaction spec and accessibility annotations (`05-admin-interaction-a11y.md`).

---

## Traceability
| Frame | Discovery verdict | IA section |
|-------|-------------------|-----------|
| Dashboard | Rebuild (role/branch-aware) | IA §3 |
| Student 360 | Rebuild (merge 6 menus → tabs) | IA §9 |
| Finance Cockpit | Rebuild (6 workflow areas) | IA §10 |
| CBC Hub | Rebuild (config & oversight) | IA §1 (Academics▸CBC) |
| Settings Hub | Rebuild (tenant/branch-aware) | IA §1 (Settings) |

> These 5 frames + the design system establish the Admin App's visual language and core frameworks. Once approved in Stitch/Figma, build the **foundation** (Auth, Navigation, Dashboard, Role Management), then proceed **module-by-module** using `04-admin-screen-specs-extended.md`.
