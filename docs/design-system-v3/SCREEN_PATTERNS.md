# Screen Patterns — Design System V3

> Templates every Admin screen should follow. Pick a pattern before inventing layout.

## Shared requirements (all patterns)

Every screen includes as applicable:

| State | Treatment |
|-------|-----------|
| Loading | Skeleton matching final layout (not spinner-only on hubs/lists) |
| Empty | `EmptyState` / `ListEmptyState` + contextual CTA |
| Error | `EmptyState` tone danger/info + Retry |
| Offline | `OfflineBanner` + queued/disabled writes messaging |
| Success | Toast or SuccessDialog for important mutations |
| Permission | AccessDenied / ModuleAccessDenied / EmptyState |

Tokens only · safe areas · checklist before merge.

---

## 1. Module hub / Dashboard

**Examples:** Dashboard, FinanceDashboard, AcademicsDashboard, AdmissionsWorkspace, CommunicationDashboard, OperationsDashboard, ReportsHub

```
GlobalAppHeader
DashboardHero (gradient / photo-safe)
[optional SegmentedTabBar]
KPI grid (WidgetGrid / KpiCard)     ← loading via WidgetShell
ChartCard(s)                        ← one narrative viz, avoid duplicates
DashboardSection → lists / actions
[FAB / quick actions]
```

**V3 rules:** One hero · KPIs before dense filters · Students registry must gain hero · avoid duplicate metrics (Academics debt).

---

## 2. Registry / searchable list (CRUD index)

**Examples:** StudentRegistry, StaffRegistry, BillingList, ExamsList, AnnouncementsList, VehiclesList, AssetsList

```
[optional compact hero / count meta]
SearchBar (sticky on scroll)
FilterTriggerButton → FilterBottomSheet
FlatList of domain ListItems
ListEmptyState / SkeletonListRows
```

**V3 rules:** No 3–5 always-visible filter rows · result count in meta · pull-to-refresh.

---

## 3. Detail / 360 profile

**Examples:** StudentDetail, StaffDetail, ApplicationDetail

```
Collapsing profile header (avatar, name, status chips)
ScrollableTabBar (a11y tabs, height ≥ 44)
Tab content (Overview first)
Optional sticky Profile360CompactBar
```

**V3 rules:** One shared Profile360 shell · no Unicode back bar · Overview = summary widgets + timeline · secondary tabs use EmptyState.

---

## 4. Entity detail (non-360)

**Examples:** InvoiceDetail, PaymentDetail, ExamDetail, NotificationDetail, ExpenseDetail, TripDetail

```
ScreenHeader
Status header card (tone accent)
Field sections / ledger
Primary actions (bottom bar or section)
```

---

## 5. Form / edit / wizard

**Examples:** StudentEdit, StaffEdit, AnnouncementForm, TemplateForm, TripForm, AssetForm, LeaveApply, MedicalRecordForm, MarksEntry, SmsCompose

```
ScreenHeader
Grouped fields in raised cards (radius.card)
label + TextField / pickers
Helper & inline errors
Sticky primary CTA (Save / Submit) above safe area
LoadingDialog on long submits
```

Multi-step wizard: progress indicator · Back/Next · validate per step.

---

## 6. Approvals inbox & detail

```
Inbox: priority queue cards + FilterTrigger + Empty “All caught up”
Detail: ApprovalDetailView + ApprovalActionBar (Approve / Reject)
Reject → ConfirmDialog (not bare Text)
```

Optional: swipe actions on inbox rows (Stage 2 delight).

---

## 7. Finance workspace

```
Hub pattern + workspace quick links
Lists: invoices/payments with StatusBadge
Statements: StatementLedger
Reconciliation: filters in sheet
M-Pesa: bottom sheet prompt
Charts: absolute currency — never misleading normalized %
```

---

## 8. Academics / attendance / marks

```
Hub: single breakdown viz + trends
Lists: ExamListItem with status accent
Marks: pickers in sheet or compact sticky bar; matrix with Save bar
Attendance: large touch targets for status toggles
Moderation: honest EmptyState if mobile read-only
```

---

## 9. Reports & analytics

```
ReportsHub cards → push report screens
ExecutiveAnalytics / BoardPack: ChartCards + period FilterChip
Tables: horizontal scroll with sticky first column if needed
Export actions in header
a11y labels summarizing chart data
```

---

## 10. Communication

```
Hub → Announcements / SMS / Templates
List + Form + Detail patterns
Message-style detail: clear hierarchy, timestamps caption
```

---

## 11. Operations (transport, inventory, library, visitors, assets)

```
OperationsDashboard hub
Sub-registries use Registry pattern
Driver/Teacher flows: large CTAs, offline-friendly
Visitor check-in: form pattern + success feedback
```

---

## 12. Settings

```
Optional Settings hero (school name, read-only badge)
Grouped list cards (radius.card) — Profile / Account / General style
Section headers title
Rows: icon well + label + chevron (+ NEW badge sparingly)
Footer: version caption
Session / About / Diagnostics as sheets or stack children
```

---

## 13. Profile & notifications

```
UserProfile: settings-like grouped rows
NotificationsList: EmptyState + list rows; detail push
```

---

## 14. Authentication & access

```
Login: brand mark, fields, primary CTA, biometric affordance
AuthLoading: branded splash skeleton
BiometricEnable: benefit copy + Continue / Skip
AccessDenied / ModuleAccessDenied: EmptyState + exit CTA
```

---

## 15. Search & activity

```
GlobalSearch: SearchBar + sectioned results + EmptyState
ActivityCenter / AuditDetail: timeline list + detail pattern
```

---

## 16. System states (standalone)

| Screen type | Pattern |
|-------------|---------|
| No internet | Full-page EmptyState + Retry |
| Maintenance | PlaceholderScreen / branded message |
| Permission OS | Branded dialog explaining why |
| Offline write blocked | Banner + disable primary CTA |

---

## Pattern selection cheat sheet

| If you are building… | Start from |
|----------------------|------------|
| New module home | Hub / Dashboard |
| Long searchable list | Registry |
| Person/org dossier | 360 |
| Invoice/exam/trip | Entity detail |
| Create/edit | Form |
| Approve queue | Approvals |
| Charts/board pack | Reports |
| Config | Settings |
| Gate | Auth / Access |

When unsure, copy **AdmissionsWorkspace** (hub) or **StudentListItem registry** (list) structure and swap domain components — do not invent a third layout language.
