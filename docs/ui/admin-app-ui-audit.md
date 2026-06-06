# Admin App UX/UI Audit — Post Design System V2

> **Scope:** Review of the completed Design System V2 implementation against premium / Google Stitch quality bar.  
> **Method:** Source review of `docs/ui/admin-app-design-system-v2.md`, `docs/admin-app/*`, `apps/admin`, and `packages/ui`. No runtime screenshots; findings reference actual components and screen files.  
> **Date:** June 2026

---

## Executive Summary

Design System V2 delivered meaningful visual uplift where it was applied: **module dashboards** (Dashboard, Finance, Academics, Admissions, People) now have gradient heroes, elevated KPI cards, unified search bars, and chart wrappers. **List items** (`StudentListItem`, `StaffListItem`, `ApplicationListItem`) feel modern and scannable.

However, V2 adoption is **patchy**. Roughly half the app still reads as **enterprise MVP**:

- Core **registry screens** (Students, Finance lists, Approvals inbox) use plain `Text` for empty states while secondary modules (Notifications, Announcements, Operations) already use V2 `EmptyState`.
- **360 profile layouts** (Student, Staff, Admissions) duplicate a legacy pill-tab pattern instead of sharing `SegmentedTabBar` or a dedicated scrollable tab primitive.
- **Typography** is split between new `typography.*` tokens and legacy `fontSizes.*` — often on the same screen.
- **Filter-heavy screens** push primary content below the fold; no collapse, sticky filter bar, or filter summary chip.
- **Global chrome** (`GlobalAppHeader`, bottom tabs) was not part of V2 and still feels utilitarian compared to module heroes below it.

**Overall grade:** Module landing pages ≈ **B+ (enterprise-polished)**. Registry/list/detail flows ≈ **C+ (functional MVP)**. 360 profiles and Settings ≈ **C (legacy patterns)**. Stitch parity requires closing the gap between “dashboard front door” and “everything behind it.”

---

## Cross-Cutting Evaluation

| Dimension | V2 wins | Still MVP / dated | Stitch target |
|-----------|---------|-------------------|---------------|
| **1. Visual hierarchy** | Hero → tabs → KPIs on module hubs; `DashboardSection` titles | Flat Settings hub; 360 headers compete with stack nav; filter walls before content | One clear focal point per screen; progressive disclosure |
| **2. Information density** | KPI grids balanced at 2-col | Academics dashboard duplicates breakdown; Approvals/Students 5+ filter rows; Executive tab stacks 4 KPIs + 3 charts | Priority-ordered content; hide secondary metrics behind “See all” |
| **3. Empty states** | V2 `EmptyState` in Notifications, Search, Communication, Operations | **Students, Staff, Admissions, Finance lists, Approvals inbox** — plain centered text | Illustrated empty states with contextual CTAs everywhere |
| **4. Color usage** | Semantic `StatusBadge` for invoices/students; hero gradients | Hardcoded badge palettes (`ApplicationStatusBadge`); Executive period chip `#E8F0FA`; chart `rgba(0,74,153)` literals | 100% token-driven; no hex literals in components |
| **5. Typography** | KPI values at Heading 22; overline labels | Headers/cards still on `fontSizes.md/lg`; mixed scales on one screen | Single type ramp; Display reserved for onboarding only |
| **6. Spacing** | 4pt grid in V2 components | Hardcoded `paddingHorizontal: 16` in StyleSheets; inconsistent section gaps between modules | All layout spacing from `spacing.*` tokens |
| **7. Accessibility** | `SearchBar` search role; `SegmentedTabBar` tab roles; 48px search height | 360 tab pills ~32px tall; Settings footer links; reject confirm as bare `Text`; charts lack text alternatives | WCAG AA contrast, 44px targets, tab semantics app-wide |
| **8. Mobile ergonomics** | Pull-to-refresh, infinite scroll, FAB clearance on Dashboard | No sticky search on long lists; horizontal filter fatigue; double headers (stack + 360 back bar) | Thumb-zone actions; collapsible filters; bottom sheets for actions |
| **9. Dashboard usefulness** | Live KPI widgets, executive analytics, approval preview | Overview lacks period selector; Operational Status placeholder; no sparklines on Overview KPIs | Role-shaped widgets with trends; approvals/alerts above fold on mobile |
| **10. Consistency** | Unified `SearchBar`, `FilterChip` (partial), list item pattern | Three tab implementations; two filter chip implementations; hero on 4/5 hubs (Students missing) | One tab bar, one filter row, one empty state, one list row |

---

## Module Audits

---

### Dashboard

**Screens:** `DashboardLayout`, `CriticalKpisSection`, `QuickActionsSection`, `OperationalStatusSection`, `ExecutiveDashboardSection`, `PendingApprovalsSection`, `AlertsSection`, `ExecutiveCharts`, `QuickActionFab`

#### What works well
- **Gradient hero + `SegmentedTabBar`** establish a premium module entry (`DashboardLayout.tsx`).
- **Widget framework** (`WidgetShell` / `KpiCard` / `WidgetGrid`) handles loading, error, and empty per-widget — never blanks the whole screen.
- **Alerts tab** uses full V2 `EmptyState` with icon and friendly copy.
- **Quick Action FAB** with RBAC-filtered bottom sheet is a strong mobile pattern.
- **Executive charts** wrapped in `ChartCard` with theme-derived line colors.

#### What feels dated
- **Executive period picker** uses inline `Pressable` chips with hardcoded `#E8F0FA` and no `FilterChip` / `typography.overline` styling (`ExecutiveDashboardSection.tsx:39–48`).
- **Pending approvals empty state** is a single line of secondary text — inconsistent with Alerts tab.
- **Operational Status** is an honest placeholder `EmptyState` but highlights incomplete product surface.
- **Layout padding** hardcoded to `16` in StyleSheet instead of `spacing.md`.

#### What feels enterprise / MVP
- Tab model (Overview / Executive / Approvals / Alerts) is correct for admin software but **Overview lacks the spec’s priority ordering** (approvals summary first on mobile).
- KPI widgets show numbers without **sparklines, deltas, or trend context** — static stat tiles.
- No **period selector** on Overview tab (spec calls for term/year selector on analytic surfaces).
- Share button on Executive uses ghost `Button` — functional, not delightful.

#### What should feel more like Google Stitch
- **Glanceable command center:** large hero metric (e.g. “94% attendance today”) with mini sparkline, not five equal-weight tiles.
- **Unified segmented control** styling between Dashboard tabs and 360 tabs.
- **Contextual hero meta** driven by live data (branch name, term) not static string “Real-time KPIs · Approvals · Alerts”.
- **Skeleton grid** on first load matching final widget layout (spec §0.5).

---

### Students (Registry)

**Screens:** `StudentRegistryScreen`  
**Components:** `StudentSearchBar`, `StudentFilters`, `StudentListItem`

#### What works well
- **V2 list items** — elevated cards, avatar, dual badges, chevron — best-in-class row pattern.
- **Unified `SearchBar`** with 48px touch target.
- **`FilterChip`** used in all five filter dimensions.

#### What feels dated
- **No `DashboardHero`** — only bottom-tab module without a gradient landing (Finance, Academics, Admissions, People all have one).
- **No result count** meta (“842 students”) unlike Staff registry hero meta.
- Plain **permission denial** copy with no illustration.

#### What feels enterprise / MVP
- **Five horizontal filter rows** (Grade, Class, Stream, Status, Gender) consume the entire above-the-fold area before any student appears — classic ERP filter panel.
- **Empty state:** `"No students match your filters."` as plain `Text` — no icon, no “Clear filters” action (`StudentRegistryScreen.tsx:189–201`).
- **Error state:** inline text + Retry, not `EmptyState`.
- Filters not collapsible; no “active filter” summary bar.

#### What should feel more like Google Stitch
- **Compact hero** with total enrollment + “Active” count.
- **Filter sheet** or collapsible “Filters (3)” chip opening a bottom sheet — list visible immediately.
- **EmptyState** with illustration and CTA to clear filters or add student (if permitted).
- **Sticky search bar** while scrolling long lists.

---

### Student 360

**Screens:** `StudentDetailScreen`  
**Layout:** `Student360Layout`, `Student360Header`  
**Tabs:** Overview, Attendance, Academics, Health, Transport, Requirements, Documents, Fees, Family

#### What works well
- **Permission-gated tabs** — clean RBAC without showing locked tabs.
- **Overview tab** aggregates summary widgets + timeline — correct 360 information architecture.
- **Several tabs** use V2 `EmptyState` (Documents, Transport, Requirements).
- **Fee and enrollment badges** on header communicate status at a glance.

#### What feels dated
- **Custom pill tab bar** duplicated in layout file — uses legacy `fontSizes.sm`, `palette.surface`, hairline borders (`Student360Layout.tsx:37–74`) instead of V2 `SegmentedTabBar` or shared scrollable tabs.
- **Header card** uses `shadows.sm` and flat surface — visually weaker than module `DashboardHero` users saw moments ago.
- **Overview parent block** uses manual uppercase labels instead of `FinanceFieldSection` / structured field grid.

#### What feels enterprise / MVP
- **8–9 tabs** in horizontal scroll — easy to lose active tab off-screen; no tab indicator or “More” overflow pattern.
- **Single ScrollView** wraps header + tabs + content — tab switches don’t reset scroll; header scrolls away entirely.
- **Not-found state:** plain error text + Retry, not `EmptyState`.
- Tab pills ~32px vertical padding — **below 44px touch target**.

#### What should feel more like Google Stitch
- **Sticky profile header** that collapses to compact bar (avatar + name + fee chip) on scroll — spec §2 mobile layout.
- **Scrollable tab bar** with active indicator pill and `accessibilityRole="tab"`.
- **Quick actions** in header or bottom sheet (record payment, send message) — spec §2 actions.
- **Overview widgets** with sparklines / trend chips for attendance and fees.

---

### Admissions

**Screens:** `AdmissionsWorkspaceScreen`, `ApplicationDetailScreen`  
**Components:** `Admissions360Layout`, `Admissions360Header`, `AdmissionsFunnelChart`

#### What works well
- **Best module landing in the app:** Hero → 5 KPI cards (tap-to-filter) → funnel chart → search/filters → list.
- **KPI tap filtering list** is excellent affordance — connects analytics to action.
- **`ApplicationListItem`** matches V2 list quality.
- **`DashboardSection`** wraps Applications block with proper title hierarchy.

#### What feels dated
- **List empty state** still plain text (`AdmissionsWorkspaceScreen.tsx:189–201`).
- **`ApplicationStatusBadge`** uses hardcoded hex palette map, not `StatusBadge` semantic tones (`ApplicationStatusBadge.tsx:6–12`).
- **Admissions360Header** lacks elevation present on Student/Staff headers — inconsistent 360 chrome.

#### What feels enterprise / MVP
- **Dashboard + list in one screen** — high density; funnel chart labels truncated (“Review”, “Wait”).
- **360 layout** reimplements same pill tabs as Student/Staff — third copy of same pattern.
- Enrollment/overview tabs use field sections but visual rhythm varies tab to tab.

#### What should feel more like Google Stitch
- **Funnel as visual pipeline** (connected stages) not just a bar chart — Stitch favors narrative data viz.
- **Empty applications state** with “No pending applications” celebration illustration when stats are zero.
- **Application 360 header** with status-colored accent strip (pending = amber, enrolled = green).

---

### People (Staff Registry)

**Screens:** `StaffRegistryScreen`  
**Components:** `StaffSearchBar`, `StaffFilters`, `StaffListItem`, `DashboardHero`

#### What works well
- **Full V2 dashboard pattern:** hero with staff count meta, search, filters, elevated list items.
- **Parity with Admissions/Finance** module entry experience.
- **Staff list item** shows department, role, employment badge — good information density per row.

#### What feels dated
- **`StaffFilters`** still uses legacy inline chip pattern (not migrated to `FilterChip` like Students).
- **List empty/error** handled inside `ListEmptyComponent` as plain text or inline retry — not `EmptyState`.

#### What feels enterprise / MVP
- Same **multi-row filter wall** problem as Students — five dimensions before content.
- Loading filters shows bare `ActivityIndicator` with no skeleton.

#### What should feel more like Google Stitch
- **Hero meta** could show “12 on leave today” or “3 pending reviews” — live contextual stats, not just total count.
- **Filter bottom sheet** pattern.
- **EmptyState** with “Adjust filters” CTA.

---

### Staff 360

**Screens:** `StaffDetailScreen`  
**Layout:** `Staff360Layout`, `Staff360Header`  
**Tabs:** Overview, Employment, Leave, Attendance, Payroll, Performance, Documents, Training

#### What works well
- **Rich tab coverage** with leave balance, attendance summaries, payroll snippets on Overview.
- **Secondary tabs** (Documents, Payroll, Performance, Training) use V2 `EmptyState`.
- **Employment badge** and structured field sections on Overview.

#### What feels dated
- **Custom back bar** with Unicode `←` character (`Staff360Layout.tsx`) instead of Ionicons + `accessibilityLabel` — inconsistent with `FinanceScreenHeader` / `AcademicScreenHeader`.
- **Duplicate navigation chrome** — stack header + inline back bar + profile title.
- Same **legacy pill tabs** as Student 360.

#### What feels enterprise / MVP
- **8 tabs** — heaviest 360 navigation in the app.
- Read-heavy profile with no prominent quick actions (clock in, request leave) in header.
- Overview tab packs many summary cards without visual breathing room.

#### What should feel more like Google Stitch
- **Unified 360 shell** shared across Student/Staff/Admissions with one tab component and one header pattern.
- **Collapsing header** with avatar, name, employment status, department.
- **Action chip row** under header (Message, Leave, Documents).

---

### Finance

**Screens:** `FinanceDashboardScreen`, `BillingListScreen`, `CollectionsScreen`, `ReconciliationScreen`, `StatementsScreen`, detail screens  
**Components:** `FinanceScreenHeader`, `FinanceSearchBar`, `InvoiceListItem`, `PaymentListItem`, `FinanceSummaryChart`

#### What works well
- **Module dashboard** follows V2 template: hero with collection meta, 5 KPIs, chart, workspace quick actions.
- **Sub-screens** share compact `FinanceScreenHeader` with Ionicons back + `hitSlop`.
- **`InvoiceStatusBadge`** migrated to semantic `StatusBadge`.

#### What feels dated
- **`FinanceScreenHeader`** uses `fontSizes` not `typography` scale.
- **Billing/Collections/Reconciliation lists** — plain text empty states.
- **`FinanceSummaryChart`** hardcodes `rgba(0, 74, 153)` instead of theme color function.

#### What feels enterprise / MVP
- **FinanceSummaryChart normalizes values to percentages** while labels imply currency (“Today”, “Month”, “Outstanding”) — misleading data presentation; reads as rushed analytics add-on.
- No donut “collected vs target” chart spec’d in UI spec §3.
- Access denied states are minimal one-liners.
- Transaction/invoice detail screens use field sections — functional, not visually distinguished from lists.

#### What should feel more like Google Stitch
- **Currency-formatted bar chart** or horizontal progress bars for collection vs target.
- **Defaulter highlight card** with warning tone on dashboard (semantic color, not just another KPI tile).
- **Empty billing/collections states** with “Record payment” or “Create invoice” CTAs where permitted.
- **M-Pesa feed widget** visual treatment (live pulse indicator) per spec.

---

### Academics

**Screens:** `AcademicsDashboardScreen`, `ExamsListScreen`, `MarksScreen`, `MarksMatrixScreen`, `ReportCardsScreen`, `ModerationScreen`  
**Components:** `AcademicScreenHeader`, `ExamListItem`, `ExamBreakdownChart`, `AcademicKpiCard`, `AcademicTrendCard`

#### What works well
- **Dashboard hero** with pipeline meta count.
- **`ExamBreakdownChart`** in `ChartCard` — good visual anchor.
- **Horizontal `AcademicTrendCard` carousel** — Stitch-like horizontal metric scroll.
- **`ModerationScreen`** honest `EmptyState` explaining mobile read-only scope.

#### What feels dated
- **Duplicate exam breakdown** — bar chart AND `AcademicKpiCard` chip row show same data (`AcademicsDashboardScreen.tsx:116–134`).
- **Exams/Marks filters** use inline `Pressable` chips, not `FilterChip`.
- **`ExamStatusBadge`** likely still hardcoded (similar to Application badge pattern).

#### What feels enterprise / MVP
- **Busiest dashboard** — hero + 5 KPIs + chart + duplicate breakdown + trend carousel + moderation card + 6 quick actions = cognitive overload.
- **Marks screen** — three chip picker rows (exam, class, subject) before any data; table header row feels spreadsheet-like.
- List empty states: plain “No exams found” / “No marks recorded”.

#### What should feel more like Google Stitch
- **Single breakdown visualization** — chart OR chips, not both.
- **Progress rings** for marks submission % and report-card publish status (spec dashboard widgets).
- **Marks entry** with swipe-friendly row actions or floating “Save” bar.
- **Exam list** with status-colored left accent (like `AlertCard`) for draft/marking/moderation.

---

### Settings

**Screens:** `SettingsScreen`, `SessionScreen`, `AboutScreen`, section components  
**Components:** `SettingsHubLayout`, `SettingCard`, `SettingsSectionHeader`

#### What works well
- **Section chip navigation** (School, Academic, Grading, Roles) is clear and RBAC-friendly.
- **Read-only lock icons** on `SettingCard` communicate mobile scope honestly.
- **Modal presentation** for Session/About keeps hub uncluttered.

#### What feels dated
- **No hero or module context** — jumps straight to chip row; flat compared to every other hub.
- **`SettingCard`** uses legacy `fontSizes` + `shadows.sm`, not V2 elevation/typography.
- **Footer links** (“Session & security”, “About”) are small primary-colored text — not proper list rows or buttons.

#### What feels enterprise / MVP
- Read-only field grid feels like **admin config dump**, not a settings experience.
- Loading/error per section is inline text — no skeleton cards.
- No search within settings (fine for MVP, but Stitch settings usually have search).

#### What should feel more like Google Stitch
- **Settings hero** — school name, logo placeholder, “Read-only on mobile” badge.
- **Grouped list rows** (iOS Settings style) instead of card grid for long module lists.
- **Footer actions** as full-width list items with chevrons, 48px min height.
- **Section transitions** with subtle cross-fade or shared element on chip change.

---

### Approvals

**Screens:** `ApprovalsWorkspaceScreen`, `ApprovalsInbox`, `ApprovalDetailScreen`  
**Components:** `ApprovalFilters`, `ApprovalCard`, `ApprovalDetailView`, `ApprovalActionBar`, `PendingApprovalsPanel`

#### What works well
- **`ApprovalCard`** — clear title, source, badges, timestamp; good scan pattern.
- **`ApprovalActionBar`** — fixed bottom bar with 44px buttons; proper mobile action pattern.
- **`ApprovalDetailView`** — structured hero card + field rows + priority/status badges.
- **Dashboard embed** (`PendingApprovalsPanel`) surfaces work queue without leaving home.

#### What feels dated
- **`ApprovalFilters`** reimplements chip rows instead of `FilterChipRow` — 3 dimensions × many chips.
- **Inbox empty state** in `ApprovalList` — centered plain text.
- **Reject confirm** on detail screen is bare `Text` with `onPress` — not a `Button`; poor touch target (`ApprovalDetailScreen.tsx`).

#### What feels enterprise / MVP
- **Highest filter overhead in the app** — status, priority, source type rows before first card.
- Context hint text helps but doesn’t reduce visual weight.
- Dashboard panel empty: `"No pending approvals"` plain text vs Alerts’ illustrated empty state.

#### What should feel more like Google Stitch
- **Inbox as priority queue** — critical/high items with colored left stripe, not flat cards only.
- **Swipe actions** (approve/reject) on list rows for power users.
- **Empty inbox celebration** — “All caught up” with illustration (spec: “No pending approvals 🎉”).
- **Filter summary chip** — “Pending · High priority · 3 filters” collapsible.

---

## Global Chrome (Affects All Modules)

| Element | Current state | Gap |
|---------|---------------|-----|
| **`GlobalAppHeader`** | Flat `palette.surface`, hairline border, legacy `fontSizes` | No V2 elevation; branch label is static text; doesn’t harmonize with gradient heroes below |
| **Bottom tab bar** | Standard React Navigation tabs, primary active color | No filled/active pill indicator; icons only — acceptable but not Stitch-polished |
| **Drawer** | Functional permission-filtered menu | Not audited in depth; likely flat list, no section grouping visuals |
| **Dark mode** | Tokens support dark surfaces | Heroes/charts/badges not verified for dark-mode contrast in audit |

---

## Top 20 Visual Improvements (Ranked by Impact)

| Rank | Improvement | Modules affected | Impact | Effort |
|------|-------------|------------------|--------|--------|
| **1** | **Standardize `EmptyState` on all registry/list screens** — replace plain `Text` in Students, Staff, Admissions, Finance lists, Approvals inbox, Exams, Marks, Billing, Collections | Students, People, Admissions, Finance, Academics, Approvals | **Critical** — biggest “MVP feel” gap; instant premium lift | Low |
| **2** | **Extract shared `ScrollableTabBar` primitive** — unify Dashboard `SegmentedTabBar`, Student/Staff/Admissions 360 pills, Settings chips; add tab a11y roles | All 360 profiles, Settings, Dashboard | **Critical** — eliminates largest consistency debt | Medium |
| **3** | **Add `DashboardHero` to Students registry** with enrollment meta count | Students | **High** — only tab hub missing hero; breaks bottom-nav parity | Low |
| **4** | **Collapsible filter pattern** — “Filters (n)” chip → bottom sheet instead of 3–5 always-visible chip rows | Students, Staff, Approvals, Exams, Marks | **High** — restores above-the-fold content on mobile | Medium |
| **5** | **Fix `FinanceSummaryChart`** — show absolute currency values or labeled progress bars, not normalized % | Finance | **High** — current chart undermines trust in analytics | Low |
| **6** | **Migrate all badges to `StatusBadge`** — replace hardcoded maps in `ApplicationStatusBadge`, `ExamStatusBadge`, `ApprovalPriorityBadge` | Admissions, Academics, Approvals | **High** — color consistency + dark mode | Low |
| **7** | **Remove Academics dashboard duplicate breakdown** — keep chart OR `AcademicKpiCard` row, not both | Academics | **High** — reduces clutter immediately | Low |
| **8** | **Migrate inline filter chips to `FilterChip`** — Executive period picker, Exams year/term, Marks pickers, ApprovalFilters, StaffFilters | Dashboard, Academics, Approvals, People | **High** — visual consistency | Medium |
| **9** | **Typography sweep: `fontSizes` → `typography`** in headers, 360 layouts, `GlobalAppHeader`, screen headers | App-wide | **High** — subtle but pervasive polish | Medium |
| **10** | **360 collapsing sticky header** — compact profile bar on scroll with fee/employment chip | Student 360, Staff 360, Admissions 360 | **High** — spec’d mobile behavior; major Stitch signal | High |
| **11** | **Upgrade `GlobalAppHeader` to V2** — `surfaceRaised`, elevation, typography scale, optional branch pill | App-wide | **High** — hero below header creates jarring transition today | Medium |
| **12** | **Dashboard Overview priority reorder** — surface pending approvals / fee collection before secondary KPIs on mobile | Dashboard | **Medium** — improves usefulness without new APIs | Low |
| **13** | **Add sparklines or delta text to Overview KPI widgets** | Dashboard | **Medium** — transforms static tiles into analytics | Medium |
| **14** | **Settings hub visual upgrade** — hero + grouped list rows + proper footer actions | Settings | **Medium** — currently weakest hub | Medium |
| **15** | **Approval inbox empty celebration + swipe actions** | Approvals | **Medium** — emotional design + power-user ergonomics | Medium |
| **16** | **Sticky search bar on long registries** | Students, Staff, Admissions lists | **Medium** — mobile ergonomics | Low |
| **17** | **Chart accessibility summaries** — `accessibilityLabel` with data description on all charts | Dashboard, Finance, Academics, Admissions | **Medium** — a11y compliance | Low |
| **18** | **Replace hardcoded colors** — `#E8F0FA`, `#ccc`, chart rgba literals → theme tokens | Dashboard Executive, Finance charts | **Medium** — dark mode + brand consistency | Low |
| **19** | **Skeleton loading for list/registries** — row skeletons instead of centered spinners | Students, Staff, Finance lists | **Medium** — perceived performance | Medium |
| **20** | **Unify 360 back navigation** — remove Unicode back bar; rely on stack header or shared `ScreenHeader` with Ionicons | Staff 360, Admissions 360 | **Low-Medium** — removes double-header awkwardness | Low |

---

## Stitch Quality Benchmark — Gap Summary

Google Stitch / Material You premium apps typically exhibit:

1. **One visual front door per module** — ✅ largely done for dashboards; ❌ Students registry missing.
2. **Consistent component vocabulary** — ⚠️ partial; three tab patterns, two filter patterns, two empty-state patterns.
3. **Data with narrative** — ⚠️ charts exist but Finance chart misleading; Overview KPIs lack trends.
4. **Breathing room** — ❌ filter walls and Academics redundancy create clutter.
5. **Delightful zero states** — ❌ core workflows use plain text.
6. **Motion and depth** — ⚠️ elevation on cards; no shared motion language; no collapsing headers.
7. **Accessibility as default** — ⚠️ good in new primitives; gaps in 360 tabs, Settings links, chart alt text.

**Estimated distance to Stitch parity:** Module dashboards ~70% there. End-to-end user journeys (search → list → 360 → action) ~45%. Settings and global chrome ~30%.

---

## Recommended Phasing (Audit Only — Not Implementation)

| Phase | Focus | Items from Top 20 |
|-------|-------|-------------------|
| **Phase A — Quick wins** | Empty states, badge migration, chart fix, dedup Academics, Students hero | #1, #3, #5, #6, #7, #18 |
| **Phase B — Consistency** | Tab bar primitive, FilterChip migration, typography sweep, header upgrade | #2, #8, #9, #11 |
| **Phase C — Mobile UX** | Collapsible filters, sticky search, skeletons, 360 header | #4, #10, #16, #19 |
| **Phase D — Delight** | Dashboard trends, Settings upgrade, approval swipe/celebration, chart a11y | #12–15, #17, #20 |

---

## Appendix: V2 Component Adoption Matrix

| Component | Adopted | Not yet adopted |
|-----------|---------|-----------------|
| `DashboardHero` | Dashboard, Finance, Academics, Admissions, People | **Students**, Settings |
| `SegmentedTabBar` | Dashboard | Student/Staff/Admissions 360, Settings |
| `SearchBar` | All domain search bars | — |
| `FilterChip` | Students, Applications | Staff, Approvals, Executive, Exams, Marks |
| `StatusBadge` | Invoice, Student enrollment/fee | Application, Exam, Approval priority |
| `EmptyState` | Notifications, Search, Communication, Operations, some 360 tabs | **Core registries**, Finance lists, Approvals inbox |
| `ChartCard` | Executive, Finance, Academics, Admissions charts | — |
| `KpiCard` / `WidgetShell` | All module dashboards | — |
| `typography.*` | V2 components | Most screen-level inline styles |
| `elevation[*]` | V2 list items, widgets, heroes | 360 headers, Settings cards, GlobalAppHeader |

---

*This audit is descriptive only. No code changes were made. For V2 token reference see [`admin-app-design-system-v2.md`](./admin-app-design-system-v2.md). For target UX spec see [`docs/admin-app/03-admin-ui-specifications.md`](../admin-app/03-admin-ui-specifications.md).*
