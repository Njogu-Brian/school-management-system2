# UI Audit — Design System V3

> **Canonical audit** (July 2026). Supersedes [`docs/ui/admin-app-ui-audit.md`](../ui/admin-app-ui-audit.md).  
> **Method:** Source inventory of `apps/admin` (~115 screens + 24 360 tabs) against V3 premium bar. No runtime screenshots.  
> **Bar:** Consistency, radius 24 cards, soft elevation, token-only styles, branded states, motion, custom chrome — flagship banking polish expressed as ScholarCore.

**Grades:** A = V3-ready · B = V2 strong / needs V3 tokens · C = functional MVP · D = legacy / inconsistent  
**Priority:** P0 critical consistency · P1 high user-facing · P2 polish · P3 nice-to-have

---

## 1. Executive summary

| Area | Grade | Verdict |
|------|:-----:|---------|
| Module hubs (Finance, Academics, Admissions, People, Dashboard) | B | Heroes + KPIs work; radius/motion/token debt remain |
| Core registries (Students, Staff, Finance lists, Approvals) | C | List items good; empty states & filter walls poor |
| 360 profiles | C | Rich data; legacy pill tabs, weak headers |
| Communication / Operations / Reports | B− / C+ | Partial EmptyState adoption; uneven chrome |
| Settings / global chrome | C / C− | Utilitarian vs hubs below |
| Auth / system | B− | Functional; brand polish incomplete |

**Overall:** Front doors ~**B**; journeys behind them ~**C**. Distance to V3 parity: hubs ~65%, end-to-end ~40%, chrome ~30%.

### Cross-cutting scorecard

| Dimension | Wins | Gaps vs V3 |
|-----------|------|------------|
| Hierarchy | Heroes on most hubs | Students missing hero; Settings flat |
| Empty states | Notifications, Communication, some Ops/360 | Core registries, Finance lists, Approvals inbox |
| Color | Semantic StatusBadge partial | Hex maps; chart literals; splash `#390754` drift |
| Typography | V2 scale in components | Dual `fontSizes` + `typography` in screens |
| Spacing / radius | 4pt in V2 components | Hardcoded 16; card radius 16 vs target 24 |
| Motion | Reanimated available | No motion token system / recipes applied |
| Chrome | Drawer + tabs work | Stock tab feel; GlobalAppHeader flat |
| A11y | SearchBar / some tabs | 360 pills ~32dp; chart alt text; Settings links |
| Dialogs | Few custom sheets | `Alert.alert` / bare Text confirms |
| Dark mode | Surfaces exist | Not verified end-to-end; semantic dark BGs weak |

---

## 2. Inventory counts

| Category | Count |
|----------|------:|
| `*Screen.tsx` | 115 |
| Student360 tabs | 9 |
| Staff360 tabs | 9 |
| Application360 tabs | 6 |
| Settings sections (inline) | 4 |
| `@erp/ui` components | ~98 |

---

## 3. Global chrome

| Surface | Grade | Problems | V3 target | Pri |
|---------|:-----:|----------|-----------|:---:|
| GlobalAppHeader | C | Flat surface, legacy fontSizes, weak vs heroes | surfaceRaised, typography.title, elevation 1, branch pill | P0 |
| Bottom tabs | C | Stock RN tabs look | Custom bar, filled active, indicator, radius-aware | P0 |
| Drawer | C+ | Functional, flat | Grouped sections, active row muted fill | P1 |
| OfflineBanner | B | Exists | Keep; token sweep | P2 |
| QuickActionFab | B | Exists | Elevation 5 + motion enter | P2 |

---

## 4. Auth (5)

| Screen | Grade | Problems | V3 pattern | Pri |
|--------|:-----:|----------|------------|:---:|
| LoginScreen | B− | Brand/spacing uneven | Auth pattern; token fields; primary CTA | P1 |
| AuthLoadingScreen | B | Basic | Branded skeleton | P2 |
| BiometricEnableScreen | B− | Generic copy layout | Benefit list + Continue | P2 |
| AccessDeniedScreen | C+ | May be plain | EmptyState access pattern | P1 |
| ModuleAccessDeniedScreen | C+ | Same | EmptyState | P1 |

---

## 5. Dashboard & shell (9)

| Screen | Grade | Problems | V3 pattern | Pri |
|--------|:-----:|----------|------------|:---:|
| DashboardScreen / Layout | B | Period chips hex; approvals empty plain; hardcoded pad 16; no Overview trends | Hub; FilterChip; EmptyState; sparklines | P0 |
| ApprovalCenterScreen | C+ | Overlaps workspace; filter wall | Approvals inbox pattern | P1 |
| ApprovalDetailScreen | B− | Reject confirm = bare Text | ConfirmDialog + ActionBar | P0 |
| NotificationsListScreen | B | EmptyState present | Keep; radius/token uplift | P2 |
| NotificationDetailScreen | B− | Detail chrome | Entity detail | P2 |
| GlobalSearchScreen | B | EmptyState present | Keep; section headers typography | P2 |
| ActivityCenterScreen | C+ | List chrome | Registry / timeline | P2 |
| AuditDetailScreen | C+ | — | Entity detail | P2 |
| UserProfileScreen | C | Settings-like but uneven | Settings grouped rows | P1 |

---

## 6. Students (5 + 9 tabs)

| Screen | Grade | Problems | V3 pattern | Pri |
|--------|:-----:|----------|------------|:---:|
| StudentRegistryScreen | C+ | **No hero**; 5 filter rows; plain empty/error | Hub lite + Registry + sheet filters | P0 |
| StudentDetailScreen | C | Legacy pills; header weak; scroll issues | 360 collapsing + ScrollableTabBar | P0 |
| StudentEditScreen | C+ | Form token debt | Form pattern | P1 |
| MedicalRecordFormScreen | C+ | — | Form | P2 |
| ReportCardDetailScreen (students) | C+ | — | Entity detail | P2 |

### Student360 tabs

| Tab | Grade | Notes | Pri |
|-----|:-----:|-------|:---:|
| OverviewTab | C+ | Summary OK; field rhythm uneven | P1 |
| FamilyTab | C+ | — | P2 |
| AcademicsTab | C+ | Nested academics widgets | P2 |
| AttendanceTab | C+ | — | P2 |
| FeesTab | B− | Badges help | P2 |
| HealthTab | C+ | — | P2 |
| DocumentsTab | B− | EmptyState used | P2 |
| RequirementsTab | B− | EmptyState | P2 |
| TransportTab | B− | EmptyState | P2 |

---

## 7. Finance (8)

| Screen | Grade | Problems | V3 pattern | Pri |
|--------|:-----:|----------|------------|:---:|
| FinanceDashboardScreen | B | Chart rgba literals; misleading % chart | Hub; currency chart fix | P0 |
| BillingListScreen | C | Plain empty | Registry + EmptyState | P0 |
| CollectionsScreen | C | Plain empty | Registry | P0 |
| ReconciliationScreen | C+ | Filters / empty | Registry + sheet | P1 |
| StatementsScreen | C+ | Ledger OK | Detail/ledger | P1 |
| InvoiceDetailScreen | C+ | fontSizes in headers | Entity detail | P1 |
| PaymentDetailScreen | C+ | — | Entity detail | P1 |
| TransactionDetailScreen | C+ | — | Entity detail | P1 |

---

## 8. People / HR (11 + 9 tabs)

| Screen | Grade | Problems | V3 pattern | Pri |
|--------|:-----:|----------|------------|:---:|
| StaffRegistryScreen | B− | Hero present; StaffFilters legacy; plain empty | Registry + FilterChip + EmptyState | P0 |
| StaffDetailScreen | C | Unicode back; legacy pills; double chrome | Shared 360 shell | P0 |
| StaffEditScreen | C+ | — | Form | P1 |
| PeopleScreen | C | Not primary stack home | Align or remove from UX surface | P3 |
| StaffClockScreen | C+ | — | Form / action | P2 |
| StaffClockTeamScreen | C+ | — | Registry | P2 |
| LeaveApplyScreen | C+ | — | Form | P1 |
| LeaveManagementScreen | C+ | — | Registry | P1 |
| PayrollRecordsScreen | C+ | — | Registry | P2 |
| PerformanceReviewDetailScreen | C+ | — | Entity detail | P2 |
| TrainingRecordDetailScreen | C+ | — | Entity detail | P2 |

### Staff360 tabs

| Tab | Grade | EmptyState? | Pri |
|-----|:-----:|:-----------:|:---:|
| OverviewTab | C+ | Partial | P1 |
| EmploymentTab | C+ | — | P2 |
| AttendanceTab | C+ | — | P2 |
| LeaveTab | B− | Cards help | P2 |
| PayrollTab | B− | Often EmptyState | P2 |
| TeachingTab | C+ | — | P2 |
| PerformanceTab | B− | EmptyState | P2 |
| TrainingTab | B− | EmptyState | P2 |
| DocumentsTab | B− | EmptyState | P2 |

---

## 9. Approvals (3)

| Screen | Grade | Problems | V3 pattern | Pri |
|--------|:-----:|----------|------------|:---:|
| ApprovalsWorkspaceScreen | C | Filter wall; plain inbox empty | Priority inbox + sheet filters | P0 |
| ApprovalCenterScreen | C+ | Duplicate entry points | Consolidate UX | P2 |
| ApprovalDetailScreen | B− | Reject UX weak | ConfirmDialog | P0 |

---

## 10. Admissions (2 + 6 tabs)

| Screen | Grade | Problems | V3 pattern | Pri |
|--------|:-----:|----------|------------|:---:|
| AdmissionsWorkspaceScreen | B | Best hub; list empty plain; status hex badge | Hub + EmptyState; StatusBadge | P0 |
| ApplicationDetailScreen | C | 360 chrome inconsistency | Shared 360 | P0 |

### Application360 tabs

Overview · Student · Parents · Documents · Enrollment · Timeline — grade **C+ / B−**; Documents/Timeline stronger; unify field sections — **P1**.

---

## 11. Academics (22)

| Screen | Grade | Problems | Pri |
|--------|:-----:|----------|:---:|
| AcademicsDashboardScreen | B− | Duplicate breakdown; clutter | P0 |
| ExamsListScreen | C | Inline chips; plain empty | P0 |
| ExamDetailScreen | C+ | — | P1 |
| ExamClassSheetScreen | C | Spreadsheet density | P1 |
| AssessmentsScreen | C+ | — | P1 |
| AssessmentDetailScreen | C+ | — | P2 |
| AssessmentHistoryScreen | C+ | — | P2 |
| MarksScreen | C | Chip picker wall | P0 |
| MarksEntryScreen | C+ | Need sticky Save | P1 |
| MarksMatrixScreen | C | Dense matrix | P1 |
| MarksMatrixSetupScreen | C+ | — | P2 |
| MarksMatrixEntryScreen | C | — | P1 |
| ReportCardsScreen | C+ | Plain empties likely | P1 |
| ReportCardHistoryScreen | C+ | — | P2 |
| ReportCardDetailScreen | C+ | — | P2 |
| ModerationScreen | B | Honest EmptyState | P2 |
| LessonPlanReviewScreen | C+ | — | P2 |
| LessonPlanDetailScreen | C+ | Re-export review | P3 |
| MarkAttendanceScreen | C+ | Touch targets critical | P1 |
| CbcCurriculumScreen | C | — | P2 |
| CbcStrandsScreen | C | — | P2 |
| CbcSubstrandScreen | C | — | P2 |

---

## 12. Operations (25)

| Screen | Grade | Notes | Pri |
|--------|:-----:|-------|:---:|
| OperationsDashboardScreen | B− | Hub; ensure hero/EmptyState parity | P1 |
| TripsListScreen / TripForm / TripDetail | C+ | Registry/form/detail | P1 |
| VehiclesListScreen / VehicleForm | C+ | — | P1 |
| TeacherTransportScreen | C+ | Role-specific | P2 |
| DriverTripsScreen / DriverTripDetailScreen | C+ | Large CTAs needed | P1 |
| InventoryListScreen / InventoryItemDetailScreen | C+ | Partial EmptyState (audit seed) | P1 |
| RequirementsRosterScreen / RequirementsStudentScreen | C+ | — | P2 |
| LibraryBooksScreen / LibraryCirculationScreen / IssueBookScreen | C+ | — | P2 |
| RequisitionsListScreen / RequisitionForm / RequisitionDetail | C+ | — | P2 |
| VisitorsListScreen / VisitorCheckIn / VisitorDetail | C+ | Check-in success feedback | P1 |
| AssetsListScreen / AssetForm / AssetDetail | C+ | — | P2 |

---

## 13. Communication (10)

| Screen | Grade | Notes | Pri |
|--------|:-----:|-------|:---:|
| CommunicationDashboardScreen | B− | Hub | P1 |
| AnnouncementsListScreen | B− | EmptyState often present | P2 |
| AnnouncementDetailScreen / Form | C+ / B− | Form pattern | P1 |
| SmsComposeScreen / History / LogDetail | C+ | — | P1 |
| TemplatesListScreen / Form / Detail | C+ | — | P2 |

---

## 14. Reports (11)

| Screen | Grade | Notes | Pri |
|--------|:-----:|-------|:---:|
| ReportsHubScreen | B− | Card grid hub | P1 |
| ExecutiveAnalyticsScreen | B− | ChartCard; period chips → FilterChip | P1 |
| BoardPackScreen | C+ | Dense | P2 |
| ExpenseReportsScreen / ExpensesList / ExpenseDetail | C+ | — | P2 |
| IncomeStatementScreen / BalanceSheetScreen / LedgerScreen | C | Table density; token headers | P1 |
| WeeklyReportsListScreen / WeeklyReportDetailScreen | C+ | — | P2 |

---

## 15. Settings (4 + sections)

| Screen | Grade | Problems | Pri |
|--------|:-----:|----------|:---:|
| SettingsScreen | C | No hero; SettingCard legacy type/shadow; footer text links | P0 |
| SessionScreen | C+ | Modal OK | P2 |
| AboutScreen | C+ | Version footer | P2 |
| GeofenceSettingsScreen | C | — | P2 |
| Academic / School / Roles / Grading sections | C | Config dump feel | P1 |

---

## 16. Misc

| Screen | Grade | Pri |
|--------|:-----:|:---:|
| ApiDiagnosticsScreen | C (dev) | P3 |
| shared/ReportCardDetailScreen | C+ | P2 |

---

## 17. Top improvements (V3-ranked)

| Rank | Item | Pri |
|-----:|------|:---:|
| 1 | EmptyState on all registries/lists | P0 |
| 2 | Unify ScrollableTabBar for all 360 + Settings chips | P0 |
| 3 | DashboardHero on Students (+ Settings hero) | P0 |
| 4 | FilterTrigger + FilterBottomSheet (collapse chip walls) | P0 |
| 5 | Fix FinanceSummaryChart currency semantics | P0 |
| 6 | Migrate all badges → StatusBadge | P0 |
| 7 | Deduplicate Academics dashboard breakdown | P0 |
| 8 | FilterChip migration (Executive, Exams, Marks, Staff, Approvals) | P0 |
| 9 | Typography: ban fontSizes in features | P0 |
| 10 | Radius uplift card 16→24, control→18 | P0 |
| 11 | GlobalAppHeader + custom bottom tabs | P0 |
| 12 | Shared ScreenHeader; remove Unicode backs | P0 |
| 13 | ConfirmDialog / Toast / SuccessDialog kit | P0 |
| 14 | Motion tokens + list/sheet recipes | P1 |
| 15 | 360 collapsing sticky header | P1 |
| 16 | SkeletonListRows default on registries | P1 |
| 17 | Sticky search on long lists | P1 |
| 18 | KPI sparklines / deltas on Dashboard | P2 |
| 19 | Chart a11y summaries | P1 |
| 20 | Splash color align to primary | P1 |
| 21 | Approval swipe + celebration empty | P2 |
| 22 | AMOLED palette wiring | P3 |
| 23 | Tablet master-detail registries | P2 |

---

## 18. Stage 2 implementation backlog (after Stage 1 foundation)

Ordered for shipping consistency, not coding in Phase 1 docs.

### Stage 1 (foundation — prerequisite)

Tokens (radius, motion, type extensions, AMOLED keys) · ThemeContext · Button/TextField/SearchBar radius · Dialog/Toast kit · ScreenHeader · Bottom tab chrome · GlobalAppHeader · eslint hex/fontSizes bans

### Stage 2 modules

1. **Dashboard** — chips, empties, trends, padding tokens  
2. **Finance** — chart fix, list empties, header typography  
3. **Students** — hero, filters sheet, EmptyState, 360 shell  
4. **People** — FilterChip, EmptyState, 360 shell  
5. **Approvals** — inbox empty, filters, ConfirmDialog  
6. **Admissions** — EmptyState, StatusBadge, 360 shell  
7. **Academics** — dedupe hub, FilterChip, marks UX, attendance targets  
8. **Operations** — hub parity, transport CTAs, visitor success  
9. **Communication** — form/detail token sweep  
10. **Reports** — hub + statements typography/tables  
11. **Settings** — hero + grouped rows + footer  
12. **Auth / Profile / Search / Notifications** — polish pass  

### Stage 3

Pixel review · dark mode QA · a11y audit · 60fps · remove leftover fontSizes/hex · tablet layouts

---

## 19. Adoption matrix (components)

| Component | Adopted | Missing / inconsistent |
|-----------|---------|------------------------|
| DashboardHero | Dashboard, Finance, Academics, Admissions, People | **Students**, Settings |
| SegmentedTabBar | Dashboard | Settings (partial chips) |
| ScrollableTabBar | Partial layout package | Student/Staff/Admissions 360 still custom pills |
| SearchBar | Domain search bars | — |
| FilterChip | Students, Applications | Staff, Approvals, Executive, Exams, Marks |
| StatusBadge | Invoice, Student | Application, Exam, Approval priority |
| EmptyState | Notifications, Search, Communication, some Ops/360 | Core registries, Finance lists, Approvals |
| ChartCard | Executive, Finance, Academics, Admissions | a11y summaries |
| Dialog / Toast | — | **NEW** |
| typography.* | V2 components | Many screens still fontSizes |
| radius.card 24 | Not yet | All cards still ~16 |

---

*Documentation only. No application code was modified for this audit. Next gate: approve V3 kit → Stage 1 foundation implementation.*
