# 01 — Admin App Discovery (Super Admin Perspective)

> **Lens:** What a **Super Admin** can see and do across the ERP today (the **web portal** is the current admin surface), and how each module should be **redesigned** for a dedicated **Admin App**.
> **Authored as:** Product Designer · ERP Consultant · UX Architect.
> **Sources:** [`../system-audit/`](../system-audit/) (route map, module inventory, role/finance/academic audits), [`../prd/`](../prd/), [`../app-split/`](../app-split/). **No code.**

---

## How to read this

- **Per module:** Existing screens · workflows · actions · reports · permissions · pain points · recommended redesign.
- **Per screen verdict:**
  - **Keep** — port largely as-is to the Admin App.
  - **Merge** — consolidate with overlapping screens into one.
  - **Remove** — dead/legacy/placeholder, or move out of the Admin App (e.g. to Staff App / parent portal).
  - **Rebuild** — re-architect (new data model, mobile-first UX, or new workflow).
- **Permissions note:** the portal enforces coarse `role:` gates (no `permission:` on web routes) with broad Gate bypasses for Super Admin/Admin/teacher-like users — so a **Super Admin effectively sees everything**. Redesign assumes the future **permission-first RBAC** ([`../system-audit/04-role-audit.md`](../system-audit/04-role-audit.md)).
- **Admin App scope:** management/configuration/approval/oversight. Capture/self-service (mark attendance, clock-in, pay fees) belongs to the **Staff/Parent apps** ([`../app-split/`](../app-split/)).

---

# 1. Dashboard

**Existing screens:** `DashboardController` role homes (admin, teacher, supervisor, parent, student, finance, transport); `AccountantDashboardController`; `HRAnalyticsController`.
**Existing workflows:** Land-on-login redirect by role; KPI tiles → drill into modules.
**Existing actions:** View KPIs; navigate; quick links.
**Existing reports:** Admin KPIs (students, attendance, fees, exams, transport); finance dashboard; accountant dashboard (overdue plans, upcoming installments, high-risk balances); HR analytics.
**Existing permissions:** `auth` + role-based home routing; bypasses make Super Admin see all.
**Pain points:** Operational, not executive/board-level; no trends/forecasts; siloed dashboards per role; no period comparison; not branch/tenant-aware; built for web, not mobile glanceability.
**Recommended redesign:** A single **role-aware, branch-aware dashboard shell** with composable widgets (stat tiles, sparklines, alerts) and a **period selector**; surface **pending approvals badge** and **early-warning** alerts; defer deep analytics to the Analytics platform but show a curated executive summary.

| Screen | Purpose | Current issues | Verdict |
|--------|---------|----------------|---------|
| Admin dashboard | School KPIs at a glance | Operational only; no trends; web-only | **Rebuild** |
| Finance dashboard | Collections/outstanding KPIs | Overlaps accountant dashboard | **Merge** → finance dashboard |
| Accountant dashboard | Overdue/upcoming/risk | Overlaps finance dashboard | **Merge** |
| HR analytics dashboard | Headcount/leave/attendance | Standalone; not in a unified shell | **Merge** into role dashboards |
| Transport dashboard | Trips/vehicles summary | Thin | **Rebuild** (with live ops) |
| Teacher/supervisor/parent/student homes | Role landings | Belong to Staff/Parent apps, not Admin App | **Remove** (from Admin App) |

---

# 2. Admissions

**Existing screens:** `OnlineAdmissionController` (public apply + admin review queue); manual add via `Students\StudentController::create/store`.
**Existing workflows:** Application `pending → under_review → enrolled / rejected / waitlisted`; on enroll → create student, link family, KNEC number, welcome comms, initial fee posting.
**Existing actions:** Review, approve/enroll, reject, waitlist, transfer, set status, destroy.
**Existing reports:** Review queue list; no funnel/conversion analytics.
**Existing permissions:** Public apply (no auth); Admin/Secretary review.
**Pain points:** No intake/cohort entity; no application fee; no document checklist/verification; positional waitlist only; no offer letter; no admissions funnel analytics.
**Recommended redesign:** A proper **Admissions pipeline** (board view: applied → reviewing → offered → accepted → enrolled), document checklist + verification, application-fee capture, offer-letter generation, intake/cohort tagging, and a funnel/conversion report.

| Screen | Purpose | Current issues | Verdict |
|--------|---------|----------------|---------|
| Online admissions review queue | Triage applications | List-only; no pipeline/funnel; no doc checklist | **Rebuild** |
| Manual add student (as admission) | Direct enrollment | Mixed with student CRUD; no admission context | **Merge** into admissions pipeline |
| Public application form | Parent-facing apply | Belongs to public/parent surface | **Keep** (outside Admin App) |

---

# 3. Students

**Existing screens:** `StudentController` (list, detail, create, edit, archived, alumni, bulk upload/import/update-import, export, bulk assign/archive/restore, parents-contact); `StudentCategoryController`; per-student `MedicalRecordController`, `DisciplinaryRecordController`, `ExtracurricularActivityController`, `AcademicHistoryController`; `FamilyController`, `FamilyIntegrityReportController`, `ParentInfoController`, `FamilyUpdateController`.
**Existing workflows:** Enroll → place (class/stream/category) → promote → archive/alumni → restore; sibling/family linking; family-update tokenized links.
**Existing actions:** CRUD, bulk import/export, archive/restore, promote, assign category/transport, family link/populate, integrity fix.
**Existing reports:** Student lists/filters, archived/alumni, parents-contact, family integrity report, phone normalization.
**Existing permissions:** Admin/Secretary/Teacher (scoped); Super Admin all.
**Pain points:** **Parent data triad** (`parent_info`/`families`/`users`) diverges; two exit concepts (archive vs alumni); medical/disciplinary are records, not workflows; bulk imports heavy/sync; no unified 360° student profile.
**Recommended redesign:** A **unified Student 360 profile** (bio, family, academics, CBC, fees, attendance, transport, health, discipline) reading from a **consolidated identity model**; registry workflows (enroll/transfer/promote/exit) with **clearance checklist**; async bulk import with preview.

| Screen | Purpose | Current issues | Verdict |
|--------|---------|----------------|---------|
| Students list | Browse/filter learners | Good base; needs branch scope + search | **Keep** (enhance) |
| Student detail | Single learner | Not 360°; tabs scattered across controllers | **Rebuild** → Student 360 |
| Add/Edit student | Registry CRUD | Multipart heavy; mixed with admission | **Keep** (refine) |
| Bulk upload/import/update | Mass data ops | Synchronous; error handling thin | **Rebuild** (async + preview) |
| Archived / Alumni | Exited learners | Two overlapping concepts | **Merge** → lifecycle status |
| Student categories | Config | Fine | **Keep** |
| Medical / Disciplinary / Extracurricular / Academic history | Per-student records | Records, not workflows; belong in Clinic/Discipline modules | **Merge** into Student 360 tabs |
| Family management + integrity | Household + dedupe | Symptom of data triad | **Rebuild** with consolidated model |
| Parent info | Guardian records | Part of triad | **Merge** into identity model |
| Family update links | Tokenized self-service | Useful | **Keep** |

---

# 4. Academics

**Existing screens:** `ClassroomController`, `StreamController`, `SubjectController`, `LearningAreaController`, `AssignTeachersController`, `StudentPromotionController`, `TimetableController` (+ whole-school engine), `HomeworkController`, `HomeworkDiaryController`, `StudentDiaryController`, `BehaviourController`, `StudentBehaviourController`, `EventCalendarController`, `AcademicConfigController` (years/terms/holidays).
**Existing workflows:** Structure setup → subject/teacher assignment → timetable generation/publish → daily academics (homework/diary/behaviour) → promotion.
**Existing actions:** CRUD classes/streams/subjects/learning areas; assign teachers; generate/publish/substitute timetable; promotions; calendar config.
**Existing reports:** Timetable views (class/teacher); lesson-plan analytics; heatmaps; weekly class/subject reports.
**Existing permissions:** Admin/Secretary/Teacher/Senior Teacher/Supervisor; CBC config Admin-only.
**Pain points:** Two class-subject sources of truth (`classroom_subject`/`classroom_subjects`); `subject_groups` dropped; timetable substitutions not applied to live timetables; legacy `classes` table; diary subsystem churn.
**Recommended redesign:** Consolidated **academic structure manager** (classes/streams/subjects/learning areas with one assignment model), a **timetable studio** (generate → review → publish → live substitutions with cover notifications), and academic calendar config — all branch-scoped.

| Screen | Purpose | Current issues | Verdict |
|--------|---------|----------------|---------|
| Classrooms / Streams | Structure | Solid; needs single assignment model | **Keep** |
| Subjects / Learning areas | Curriculum subjects | Two assignment sources; groups dropped | **Merge** assignment model |
| Assign teachers | Class/subject staffing | Spread across pivots | **Rebuild** (one assignment UI) |
| Timetable (whole-school) | Generate/publish | Substitutions not applied live | **Keep** + **Rebuild** substitutions |
| Promotions | Year rollover | Works; no cohort analytics | **Keep** |
| Homework / Diary | Classwork | Belongs mostly to Staff App; admin oversight only | **Merge** (oversight view) |
| Behaviours / Student behaviours | Conduct records | Overlaps Discipline module | **Merge** → Discipline |
| Academic calendar config | Years/terms/holidays | Fine | **Keep** |
| Events calendar | School events | Calendar only; no RSVP | **Keep** (enhance later) |

---

# 5. Attendance

**Existing screens:** `AttendanceController` (mark, records, at-risk, consecutive, student analytics, edit/update/unmark); `AttendanceNotificationController`; `AttendanceReasonCodeController`. (Duplicated in `teacher.php`/`senior_teacher.php`.)
**Existing workflows:** Daily class marking → analytics → parent notification rules.
**Existing actions:** Mark/edit/unmark; configure reason codes; configure notification rules; send notifications.
**Existing reports:** Records, at-risk, consecutive absences, student analytics; staff attendance separate (HR).
**Existing permissions:** Admin/Secretary/Teacher/Senior Teacher/Academic Admin/Supervisor.
**Pain points:** Marking is **once-per-day** (period-level schema unused); duplicated routes; no offline; analytics web-only.
**Recommended redesign:** **Marking moves to the Staff App** (offline). Admin App keeps **attendance oversight** (school/branch analytics, at-risk dashboards, notification-rule config, reason codes) + staff attendance oversight.

| Screen | Purpose | Current issues | Verdict |
|--------|---------|----------------|---------|
| Mark attendance | Capture | Belongs in Staff App | **Remove** (from Admin App) |
| Attendance records | Review marks | Useful oversight | **Keep** |
| At-risk / consecutive / student analytics | Early warning | Web-only; merge into one analytics view | **Merge** → attendance analytics |
| Reason codes | Config | Fine | **Keep** |
| Notification rules + send | Parent alerts | Should be event-driven | **Rebuild** (event-driven) |

---

# 6. CBC

**Existing screens:** `CBCStrandController`, `CBCSubstrandController`, `LearningAreaController`, `CompetencyController`, `PortfolioAssessmentController`, `CurriculumDesignController`, `CurriculumAssistantController`, `SchemeOfWorkController`, `LessonPlanController`.
**Existing workflows:** Curriculum design upload → parse → strands/substrands; scheme/lesson generation; portfolio capture; performance levels from % .
**Existing actions:** CRUD CBC tree; upload/parse curriculum PDFs; AI assist; manage portfolios.
**Existing reports:** None CBC-specific (heatmaps use subject %, not strands).
**Existing permissions:** Admin-only CBC config; teachers for schemes/lessons/portfolios.
**Pain points:** **Schema-deep but exam-driven in practice**; performance levels computed from % (codes E/M/A/B, not E.E./M.E./A.E./B.E. per outcome); regex (not LLM) curriculum parsing; portfolios optional; no competency-level reporting; no KNEC.
**Recommended redesign:** A **CBC configuration & oversight hub** in the Admin App: curriculum library management (LLM-assisted, human-verified), performance-level/rubric config, coverage tracking, portfolio completeness, and CBC report-card templates. Competency **capture** lives in the Staff App. (See [`../system-audit/06-academic-audit.md`](../system-audit/06-academic-audit.md).)

| Screen | Purpose | Current issues | Verdict |
|--------|---------|----------------|---------|
| CBC strands / substrands | Curriculum tree | Fidelity risk (regex parse) | **Rebuild** (verified ingestion) |
| Learning areas / competencies | Curriculum config | OK base | **Keep** (extend) |
| Curriculum designs (PDF) | Ingest KICD PDFs | Regex extraction; low fidelity | **Rebuild** (LLM + human verify) |
| Curriculum assistant | AI generate | Useful; governance needed | **Keep** |
| Schemes of work | Term plans | Coverage as JSON; no tracking | **Rebuild** (coverage tracking) |
| Lesson plans (review) | Supervisory review | Web has no submit; mobile-only | **Keep** (review) |
| Portfolio assessments | Evidence | Optional/secondary | **Rebuild** (primary evidence) |
| Performance levels config | Grading bands | % -based, wrong descriptors | **Rebuild** (outcome-level E.E/M.E/A.E/B.E) |

---

# 7. Examinations

**Existing screens:** `ExamController`, `ExamTypeController`, `ExamScheduleController`, `ExamMarkController`, `ExamGradeController`, `ExamPublishingController`, `ExamClassroomGradingController`, `GradingScheme`/`GradingBand` config, `ExamAnalyticsController`, `ExamReportsController`, `AssessmentController`.
**Existing workflows:** Create exam → schedule → enter marks (bulk/matrix) → grade → publish.
**Existing actions:** CRUD exams/types/schedules; marks entry; grading config; publish; analytics.
**Existing reports:** Class sheets (Excel/PDF), term workbook, teacher/subject performance, student insights, exam analytics; trends/mastery API-only.
**Existing permissions:** Admin/Secretary/Teacher/Senior Teacher.
**Pain points:** **Two assessment systems** (`assessments` vs `exams`); marks entry web duplicates mobile; no moderation workflow; trends/mastery only on API (no web parity).
**Recommended redesign:** Admin App owns **exam setup, scheduling, grading-scheme config, moderation, and publishing**; **marks entry moves to Staff App**; unify with CBC into one assessment engine (`type = formative|summative|national`).

| Screen | Purpose | Current issues | Verdict |
|--------|---------|----------------|---------|
| Exams / types / schedules | Setup | Solid | **Keep** |
| Marks entry (bulk/matrix) | Capture | Belongs in Staff App | **Remove** (from Admin App) |
| Grading schemes/bands | Config | Fine | **Keep** |
| Exam publishing | Release results | Add moderation step | **Rebuild** (+moderation) |
| Exam analytics/reports | Performance | Strong; promote API-only trends to UI | **Keep** + **Merge** trends |
| Assessments (legacy) | Numeric weekly | Parallel to exams | **Remove/Merge** into unified engine |

---

# 8. Report Cards

**Existing screens:** `ReportCardController` (generate, batch, publish, PDF, public token), `ReportCardSkillController`, `BehaviourController`, `StudentSkillGradeController`.
**Existing workflows:** Generate from exam marks → skills/behaviours/remarks → publish (public token, fee-gated parent access).
**Existing actions:** Generate/batch, publish, export PDF, manage skills/remarks.
**Existing reports:** Report card PDF; term assessment rollup.
**Existing permissions:** Admin/Secretary/Teacher.
**Pain points:** Exam-mark-driven body (not official CBC layout); fee-gating may block legitimate access; no teacher-comment approval chain.
**Recommended redesign:** **CBC-format report cards** (areas → strands → competencies → narrative + summative appendix + portfolio summary); teacher-comment workflow with approval; configurable access policy.

| Screen | Purpose | Current issues | Verdict |
|--------|---------|----------------|---------|
| Report card generate/batch | Produce reports | Exam-driven; not CBC layout | **Rebuild** |
| Report card publish | Release to parents | Fee-gate policy rigid | **Keep** (configurable gate) |
| Report card skills/remarks | Qualitative | Belongs in teacher flow | **Merge** into generation |
| Term assessment rollup | Class grid | Useful | **Keep** |

---

# 9. Finance

**Existing screens:** `VoteheadController`, `FeeStructureController`, `InvoiceController`, `OptionalFeeController`, `TransportFeeController`, `PaymentController`, `PaymentMethodController`, `BankAccountController`, `CreditNoteController`, `DebitNoteController`, `InvoiceAdjustmentController`, `PostingController`, `StudentStatementController`, `FeeBalanceController`, `FeeClearanceReportController`, `FeePaymentPlanController`, `FeeConcessionController`, `DiscountController`, `FeeReminderController`, `ScheduledFeeCommunicationController`, `BankStatementController`, `MpesaPaymentController`, `TransactionFixAuditController`, `BalanceBroughtForwardController`, `FeesComparisonImportController`, `SiblingBalanceTransferController`, `LegacyFinanceImportController`, `PaymentThresholdController`, `DocumentSettingsController`.
**Existing workflows:** Catalog → structure → posting → invoices → collection (M-Pesa/bank/cash) → allocation → reconciliation → statements/clearance; plans/reminders/concessions.
**Existing actions:** Post fees, generate/reverse invoices, record/allocate/reverse payments, reconcile bank/C2B (confirm/reject/share), credit/debit notes, plans, concessions, sibling transfer, imports.
**Existing reports:** Student/family statement, fee balance/defaulters, fee clearance, fees comparison, M-Pesa/C2B dashboards, transaction-fix audit.
**Existing permissions:** Admin/Secretary (+Finance Officer/Accountant on some).
**Pain points:** **Multiple payment rails** + heavy manual reconciliation; **no segregation of duties**; **unauthenticated webhooks**; denormalized balances; legacy import tables; M-Pesa refunds unimplemented; hostel fees not posted.
**Recommended redesign:** A streamlined **billing & collections cockpit**: unified transactions + smart reconciliation queue, posting with dry-run/diff, statements/clearance, plans/concessions — with **maker-checker** and **branch scope**. (See [`../system-audit/07-finance-audit.md`](../system-audit/07-finance-audit.md).)

| Screen | Purpose | Current issues | Verdict |
|--------|---------|----------------|---------|
| Voteheads / fee structures | Fee catalog | Solid | **Keep** |
| Invoices (generate/reverse/print) | Billing | Good; balance sync risk | **Keep** |
| Posting (preview/commit/reverse) | Fee posting | Strong (diffs) | **Keep** |
| Payments (record/allocate/reverse) | Collection | No maker-checker | **Rebuild** (+controls) |
| Bank statements / C2B reconciliation | Matching | Manual-heavy; many rails | **Rebuild** (unified queue) |
| Transaction-fix audit | Corrections | Ops-driven | **Keep** |
| Credit/Debit notes, adjustments | Adjustments | OK | **Keep** |
| Fee plans / concessions / discounts | Concessions | OK; approval gates | **Keep** |
| Reminders / scheduled fee comms | Automation | Overlaps Communication | **Merge** with Communication |
| Statements / fee balance / clearance | Reports | Strong | **Keep** |
| Legacy finance import / BBF / fees comparison | Migration tooling | One-time/legacy | **Remove** (after migration) |
| M-Pesa dashboards | Gateway ops | Useful | **Keep** |
| Sibling balance transfer | Edge workflow | Niche | **Keep** |
| Payment methods / bank accounts / thresholds / doc settings | Config | Fine | **Merge** into finance settings |

---

# 10. Accounting

**Existing screens:** `JournalController` (manual + bulk import — **fee adjustments, not GL**); `ExpenseController`, `ExpenseApprovalController`, `ExpenseReportController`, `ExpenseCategoryController`, `PaymentVoucherController`, `VendorController`; stub `ledger_postings` (EXPENSE/CASH_BANK).
**Existing workflows:** Expense draft → submit → approve → voucher → pay; "journals" adjust invoice lines.
**Existing actions:** Create/approve expenses, vouchers, vendor mgmt; fee journals.
**Existing reports:** Expense reports (category/vendor); **no financial statements**.
**Existing permissions:** Admin/Secretary/Finance Officer/Accountant.
**Pain points:** **No general ledger / chart of accounts / double-entry / trial balance / P&L / balance sheet / cash flow / budgeting / period close**; "Journal/Ledger" names mislead.
**Recommended redesign:** Build a **real Accounting module**: chart of accounts, balanced journal entries, auto-posting from fees/payments/expenses/payroll/bank, financial statements, budgets, period close. (See [`../system-audit/07-finance-audit.md`](../system-audit/07-finance-audit.md), [`../prd/02-MASTER-PRODUCT-BACKLOG.md`](../prd/02-MASTER-PRODUCT-BACKLOG.md) E15.)

| Screen | Purpose | Current issues | Verdict |
|--------|---------|----------------|---------|
| Fee journals | Invoice-line adjustments | Misnamed; not GL | **Rebuild** (separate from real GL) |
| Expenses + approval | Outgoing money | Works; GL stub only | **Keep** (+ GL posting) |
| Payment vouchers / vendors | Payables | OK | **Keep** |
| Expense reports | Category/vendor | Useful | **Keep** |
| Chart of accounts | — | **Missing** | **Rebuild** (new) |
| Journal entries (GL) | — | **Missing** | **Rebuild** (new) |
| Trial balance / P&L / BS / cash flow | — | **Missing** | **Rebuild** (new) |
| Budgets / budget vs actual | — | **Missing** | **Rebuild** (new) |
| Period close | — | **Missing** | **Rebuild** (new) |

---

# 11. Payroll

**Existing screens:** `SalaryStructureController`, `PayrollPeriodController`, `PayrollRecordController`, `PayslipController`, `StaffAdvanceController`, `DeductionTypeController`, `CustomDeductionController`.
**Existing workflows:** Generate (month) → review records → process → lock → payslips; advances repaid via deductions.
**Existing actions:** Configure structures/deductions; generate/process/lock payroll; download payslips; advances.
**Existing reports:** Payroll period/records; payslips (PDF).
**Existing permissions:** Admin/Secretary/Senior Teacher/Finance Officer/Accountant.
**Pain points:** **No GL posting** of payroll expense/statutory liabilities; period lock ≠ financial close; remittance not tracked as accounting.
**Recommended redesign:** Keep payroll engine; add **GL auto-posting** (salary expense + PAYE/NSSF/NHIF liabilities), statutory remittance tracking, richer payslips (YTD/forms). Payslip viewing → Staff App.

| Screen | Purpose | Current issues | Verdict |
|--------|---------|----------------|---------|
| Salary structures / deductions | Config | Fine | **Keep** |
| Payroll period (generate/process/lock) | Run payroll | No GL posting | **Rebuild** (+GL) |
| Payroll records | Review | OK | **Keep** |
| Payslips | Output | Admin generates; staff views in app | **Keep** (admin) / **Remove** (staff view → Staff App) |
| Advances | Loans | OK | **Keep** |

---

# 12. Transport

**Existing screens:** `TransportController`, `VehicleController`, `TripController`, `DropOffPointController`, `StudentAssignmentController`, `TripAttendanceController`, `DriverChangeRequestController`, `TransportSpecialAssignmentController`, `TransportImportController`, `DailyTransportListController`.
**Existing workflows:** Assign students to routes/drop-points; special assignments (pending→active/cancelled); driver-change (pending→approved/rejected); trip attendance; daily lists.
**Existing actions:** CRUD routes/vehicles/drop-points/trips; assign students; approve changes; print daily lists; import.
**Existing reports:** Daily transport list (Excel/PDF); transport dashboard.
**Existing permissions:** Admin/Secretary/Driver/Senior Teacher.
**Pain points:** **No live GPS/ETA**; pickup verification basic; legacy `trip`/`transport` tables; no Transport Manager role; no utilization analytics.
**Recommended redesign:** Admin App = **fleet/route management + live fleet map + assignments + approvals + utilization**; driver/pickup capture lives in Staff App; add **live tracking + QR/OTP verification** (see [`../prd/02-MASTER-PRODUCT-BACKLOG.md`](../prd/02-MASTER-PRODUCT-BACKLOG.md) E21).

| Screen | Purpose | Current issues | Verdict |
|--------|---------|----------------|---------|
| Routes / vehicles / drop-points | Fleet config | Solid; add map editor | **Keep** (enhance) |
| Student assignments | Allocate learners | OK | **Keep** |
| Special assignment / driver-change approvals | Exceptions | Fragmented approvals | **Merge** into approvals inbox |
| Trip attendance | Boarding | Belongs in Staff App (driver) | **Remove** (from Admin App) |
| Daily transport list | Roster print | Useful | **Keep** |
| Live fleet map | — | **Missing** | **Rebuild** (new) |

---

# 13. Library

**Existing screens:** `BookController`, `LibraryCardController`, `BookBorrowingController`.
**Existing workflows:** Catalog → cards → borrow/return/renew/mark-lost.
**Existing actions:** CRUD books/cards; circulation.
**Existing reports:** None (lists only).
**Existing permissions:** Admin/Secretary/Teacher (no Librarian role).
**Pain points:** No Librarian role; no overdue automation/notifications; minimal self-service; no analytics.
**Recommended redesign:** **Librarian-role circulation desk** with overdue automation + fines, catalog management, and utilization analytics; student/parent browse + "my borrowings" in their apps.

| Screen | Purpose | Current issues | Verdict |
|--------|---------|----------------|---------|
| Books catalog | Manage titles/copies | OK | **Keep** |
| Library cards | Membership | OK | **Keep** |
| Borrowings | Circulation | No overdue automation | **Rebuild** (+automation) |
| Library analytics | — | **Missing** | **Rebuild** (later) |

---

# 14. Inventory

**Existing screens:** `InventoryItemController`, `RequirementTypeController`, `RequirementTemplateController`, `RequirementTemplateAssignmentController`, `StudentRequirementController`; POS (`ProductController`, `OrderController`, `UniformController`, etc.).
**Existing workflows:** Items + stock adjustments; requirement templates → student collection; POS sales.
**Existing actions:** CRUD items; adjust stock; manage requirement templates; POS orders.
**Existing reports:** None (no valuation/consumption).
**Existing permissions:** Admin/Secretary/Teacher/Senior Teacher.
**Pain points:** No stock valuation/consumption; no Store Keeper role; requirement collection capture better on mobile.
**Recommended redesign:** **Store-keeper stock control** (items, adjustments, movements, low-stock alerts, valuation/consumption reports); requirement-collection capture → Staff App; POS integrated to finance/GL.

| Screen | Purpose | Current issues | Verdict |
|--------|---------|----------------|---------|
| Inventory items + adjustments | Stock | No valuation | **Rebuild** (+valuation) |
| Requirement templates/assignments | Config | OK | **Keep** |
| Student requirement collection | Capture | Better on mobile | **Merge/Remove** (→ Staff App) |
| POS products/orders/uniforms | Shop | Integrate to GL | **Keep** (+GL) |

---

# 15. Procurement

**Existing screens:** `RequisitionController` (raise/approve/fulfill/reject) under Inventory.
**Existing workflows:** Requisition pending → approved → fulfilled / rejected (stock out on fulfill).
**Existing actions:** Raise, approve, fulfill, reject.
**Existing reports:** None.
**Existing permissions:** Admin/Secretary/Teacher.
**Pain points:** **No PO/vendor procurement, no GRN, no three-way match, no budget/encumbrance**; requisitions only.
**Recommended redesign:** Full **procurement workflow**: requisition → approval → **PO → goods receipt (GRN) → three-way match → payment**, vendor management, budget/encumbrance integration. (See [`../prd/02-MASTER-PRODUCT-BACKLOG.md`](../prd/02-MASTER-PRODUCT-BACKLOG.md) E16.)

| Screen | Purpose | Current issues | Verdict |
|--------|---------|----------------|---------|
| Requisitions | Raise/approve/fulfill | Stock-only; no PO | **Keep** (extend) |
| Purchase orders | — | **Missing** | **Rebuild** (new) |
| Goods receipt (GRN) | — | **Missing** | **Rebuild** (new) |
| Vendors | — | Exists in finance; unify | **Merge** (with finance vendors) |
| Three-way match | — | **Missing** | **Rebuild** (new) |

---

# 16. HR

**Existing screens:** `StaffController`, `LeaveTypeController`, `LeaveRequestController`, `StaffLeaveBalanceController`, `StaffAttendanceController`, `StaffDocumentController`, `RolePermissionController`, `LookupController`, `StaffReportController`, `HRAnalyticsController`, `ProfileChangeController`, `Admin\SeniorTeacherAssignmentController`; performance/training tables (limited UI).
**Existing workflows:** Staff lifecycle; leave (pending→approved/rejected) + balances; staff attendance/clock oversight; profile-change approval; role assignment.
**Existing actions:** Staff CRUD/bulk/archive; leave approve/reject; attendance report; role/permission management; lookups.
**Existing reports:** Staff directory/department/category, new hires/terminations, turnover, HR analytics, staff attendance report.
**Existing permissions:** Admin/Secretary (+Senior Teacher on HR); Super Admin all.
**Pain points:** Performance/training thin; no recruitment/onboarding; `staff_meta`/`staff_metas` duplication; no HR Officer role; clock-in capture on web.
**Recommended redesign:** Admin App **HR cockpit**: staff lifecycle, leave (balances/calendar/cover), attendance oversight, role/permission management, performance (TPAD), onboarding; clock-in/leave-apply → Staff App.

| Screen | Purpose | Current issues | Verdict |
|--------|---------|----------------|---------|
| Staff directory/detail/edit | HR records | Solid | **Keep** |
| Leave management | Approvals | Single-level; no calendar | **Rebuild** (+balances/calendar/cover) |
| Staff attendance / clock oversight | Oversight | Capture should be Staff App | **Keep** (oversight) |
| Role & permission management | RBAC admin | Coarse today | **Rebuild** (permission-first) |
| Lookups (depts/titles/fields) | Config | Fine | **Keep** |
| Profile-change approvals | Self-service review | Fragmented | **Merge** into approvals inbox |
| Senior-teacher assignments | Supervision config | Niche | **Keep** |
| HR reports/analytics | Workforce | Strong | **Keep** |
| Performance / onboarding | TPAD/recruitment | Thin/missing | **Rebuild** (new) |

---

# 17. Communication

**Existing screens:** `CommunicationController`, `CommunicationAnnouncementController`, `CommunicationTemplateController`, `CommunicationDocumentController`, `CommunicationNoteController`, `WasenderSessionController`, `ParentNotificationBlockController`, `FeeReminderAutomationController`, `PlaceholderController`.
**Existing workflows:** Compose → queue → send (SMS/WhatsApp/email/push); scheduled & automated fee comms; DLR reconciliation; opt-out blocks.
**Existing actions:** Send/retry; manage templates/placeholders; announcements; WhatsApp sessions; notification blocks; automation settings.
**Existing reports:** Delivery reports; logs/queues/conversations.
**Existing permissions:** Admin/Secretary.
**Pain points:** **No two-way chat**; push lacks deep-link routing; `sms_logs` vs `communication_logs` duplication; webhooks unauthenticated.
**Recommended redesign:** A **communication hub**: one composer → all channels with delivery tracking, templates, targeting/scheduling, acknowledgment; add **real-time chat** + circulars; consolidate logs; event-driven automation. (See E26.)

| Screen | Purpose | Current issues | Verdict |
|--------|---------|----------------|---------|
| Send (SMS/email/WhatsApp) | Broadcast | Multi-screen; consolidate | **Merge** → one composer |
| Announcements | Notices | No targeting/ack | **Rebuild** (+targeting/ack) |
| Templates / placeholders | Config | OK | **Keep** |
| WhatsApp sessions | Provider mgmt | Provider-specific | **Keep** |
| Delivery reports / logs / queues | Monitoring | Useful; logs duplicated | **Merge** logs |
| Parent notification blocks | Opt-outs | OK | **Keep** |
| Fee-reminder automation | Scheduling | Overlaps finance | **Merge** into automation engine |
| Chat / circulars | — | **Missing** | **Rebuild** (new) |

---

# 18. Clinic

**Existing screens:** Per-student `MedicalRecordController` (under Students) — **no standalone clinic module**.
**Existing workflows:** Medical record CRUD on a student; no visit/treatment/medication workflow.
**Existing actions:** Add/edit medical records.
**Existing reports:** None.
**Existing permissions:** Admin/Secretary/Teacher (no Nurse role).
**Pain points:** **No clinic module, no Nurse role, no visit log, no medication schedule, no parent notification**; welfare/duty-of-care gap.
**Recommended redesign:** Build a **Clinic module**: visit log (symptoms/treatment/medication), per-student medical profiles (allergies/immunization/emergency contacts), medication schedules, parent notifications, Nurse role. (See E24.)

| Screen | Purpose | Current issues | Verdict |
|--------|---------|----------------|---------|
| Student medical record | Health data | Record-only; under Students | **Rebuild** → Clinic module |
| Clinic visit log | — | **Missing** | **Rebuild** (new) |
| Medical profile | — | Partial (records) | **Rebuild** (new) |
| Medication schedule | — | **Missing** | **Rebuild** (new) |

---

# 19. Visitors

**Existing screens:** **None.**
**Existing workflows:** None.
**Existing actions:** None.
**Existing reports:** None.
**Existing permissions:** N/A (Security is a demo role only).
**Pain points:** **Entirely missing** — no front-desk/security visitor management.
**Recommended redesign:** New **Visitor Management module**: pre-registration, check-in/out, photo/ID, QR badge, host notification, blacklist, gate pass, incident reporting; Receptionist/Security Officer roles. (See E25.)

| Screen | Purpose | Current issues | Verdict |
|--------|---------|----------------|---------|
| Visitor check-in/out | Front desk | **Missing** | **Rebuild** (new) |
| Gate pass | Exit control | **Missing** | **Rebuild** (new) |
| Visitor log/blacklist | Records | **Missing** | **Rebuild** (new) |

---

# 20. Security

**Existing screens:** `ActivityLogController`, `SystemLogController`, `BackupRestoreController` (Super Admin); demo `security` role.
**Existing workflows:** View audit/activity logs; create/restore/schedule backups.
**Existing actions:** View/clear/download logs; backup/restore/purge.
**Existing reports:** Activity log, system log.
**Existing permissions:** Super Admin/Admin.
**Pain points:** **Unauthenticated webhooks**, broad Gate bypasses, no real Security Officer role, fragmented audit, no security dashboard; incident reporting absent.
**Recommended redesign:** A **Security & Audit center**: consolidated audit trail (who/what/before-after), access/permission review, webhook/integration health, backup status, plus a **campus Security Officer** surface (visitor/incident) — overlaps Visitors module. RBAC/webhook hardening per [`../system-audit/04-role-audit.md`](../system-audit/04-role-audit.md) & [`../system-audit/08-integrations.md`](../system-audit/08-integrations.md).

| Screen | Purpose | Current issues | Verdict |
|--------|---------|----------------|---------|
| Activity logs | Audit trail | Fragmented | **Merge** → audit center |
| System logs | App logs | Ops-only | **Keep** |
| Backup & restore | DR | Super-admin only | **Keep** |
| Security dashboard | Posture | **Missing** | **Rebuild** (new) |
| Incident reporting | Campus safety | **Missing** | **Rebuild** (→ Visitors/Security) |

---

# 21. Settings

**Existing screens:** `SettingController` (branding, general, regional, system, IDs, modules/features, academic-reports toggles), `SchoolDayController`, `AcademicConfigController`, `PlaceholderController`, `GalleryController`, finance settings (payment methods/thresholds/doc settings) scattered in Finance.
**Existing workflows:** Configure school identity, calendar, modules, branding.
**Existing actions:** Update settings; manage gallery; configure placeholders.
**Existing reports:** None.
**Existing permissions:** Admin/Secretary; some Super Admin.
**Pain points:** Settings scattered (academic in Academics, finance in Finance, geofence in HR); not tenant/branch-aware; no feature-flag/role admin home; single-school assumptions.
**Recommended redesign:** A **unified Settings hub** (tenant/branch-aware): identity & branding, academic config, finance config, communication config, integrations, feature flags, roles & permissions, backup — with clear sections and search.

| Screen | Purpose | Current issues | Verdict |
|--------|---------|----------------|---------|
| General / regional / branding | School identity | Single-school assumptions | **Rebuild** (tenant/branch-aware) |
| Module/feature toggles | Enable modules | Useful for tiers | **Keep** (extend) |
| School days / academic config | Calendar | Lives in Academics | **Merge** into Settings hub |
| Placeholders | Comms tokens | OK | **Keep** |
| Gallery | Media | Minor | **Keep** |
| Finance config (methods/thresholds/doc) | Finance setup | Scattered in Finance | **Merge** into Settings hub |
| Integrations config | Gateways/providers | Scattered/env-only | **Rebuild** (new) |

---

# 22. Complete Admin App Screen Inventory

> Consolidated target screen set for the **Admin App**, grouped by priority. "New" = doesn't exist today. Capture/self-service screens are intentionally **excluded** (they belong to Staff/Parent apps).

## P0 — Core (must-have for a usable Admin App)

| # | Screen | Module | Origin |
|---|--------|--------|--------|
| 1 | Role-aware dashboard (branch-aware) | Dashboard | Rebuild |
| 2 | Unified approvals inbox | Cross-cutting | New |
| 3 | Students list + Student 360 profile | Students | Rebuild |
| 4 | Add/Edit student (registry) | Students | Keep |
| 5 | Admissions pipeline | Admissions | Rebuild |
| 6 | Class/stream/subject structure manager | Academics | Keep/Merge |
| 7 | Teacher assignment | Academics | Rebuild |
| 8 | Exams setup + scheduling | Examinations | Keep |
| 9 | Grading scheme config + publishing (+moderation) | Examinations | Rebuild |
| 10 | CBC report card (CBC-format) | Report Cards/CBC | Rebuild |
| 11 | Fee catalog (voteheads/structures) | Finance | Keep |
| 12 | Fee posting (preview/commit) | Finance | Keep |
| 13 | Payments + reconciliation queue (maker-checker) | Finance | Rebuild |
| 14 | Student/family statement + balances/clearance | Finance | Keep |
| 15 | Chart of accounts + journal entries (GL) | Accounting | New |
| 16 | Financial statements (TB/P&L/BS/cash flow) | Accounting | New |
| 17 | Role & permission management (permission-first) | HR/Security | Rebuild |
| 18 | Settings hub (tenant/branch-aware) | Settings | Rebuild |
| 19 | Communication composer (multi-channel) | Communication | Merge |
| 20 | Audit & security center | Security | Merge/New |

## P1 — Important (high value, after core)

| # | Screen | Module | Origin |
|---|--------|--------|--------|
| 21 | CBC config hub (curriculum library, performance levels, rubrics) | CBC | Rebuild |
| 22 | Curriculum coverage tracking | CBC/Academics | New |
| 23 | Timetable studio (+live substitutions) | Academics | Rebuild |
| 24 | Attendance oversight + analytics | Attendance | Merge |
| 25 | Exam analytics/reports (+trends) | Examinations | Keep/Merge |
| 26 | Budgets + budget vs actual | Accounting/Budgeting | New |
| 27 | Expenses + vouchers + vendors (+GL posting) | Accounting | Keep |
| 28 | Payroll (period/process/lock +GL) | Payroll | Rebuild |
| 29 | Leave management (balances/calendar/cover) | HR | Rebuild |
| 30 | Staff directory + lifecycle | HR | Keep |
| 31 | Transport: routes/vehicles/assignments + live fleet map | Transport | Keep/New |
| 32 | Announcements + circulars (targeting/ack) | Communication | Rebuild |
| 33 | Real-time chat (admin/moderation) | Communication | New |
| 34 | HR analytics + executive/board pack | Dashboard/Analytics | Keep/New |
| 35 | Online admissions funnel + offer letters | Admissions | New |
| 36 | Promotions + cohort management | Academics | Keep |
| 37 | KNEC reporting/export | CBC/Exams | New |
| 38 | Fee plans / concessions / discounts | Finance | Keep |
| 39 | Document templates + generated documents | Documents | Keep |

## P2 — Future (differentiators / specialized)

| # | Screen | Module | Origin |
|---|--------|--------|--------|
| 40 | Procurement: PO → GRN → 3-way match | Procurement | New |
| 41 | Fixed-asset register + depreciation | Assets | New |
| 42 | Library circulation (+overdue automation) | Library | Rebuild |
| 43 | Library/inventory analytics + valuation | Library/Inventory | New |
| 44 | Inventory stock control + alerts | Inventory | Rebuild |
| 45 | POS management (+GL) | Inventory/POS | Keep |
| 46 | Clinic: visits + medical profiles + medication | Clinic | New |
| 47 | Visitor management + gate pass | Visitors | New |
| 48 | Incident reporting | Security/Visitors | New |
| 49 | Performance/appraisals (TPAD) + onboarding | HR | New |
| 50 | Branch-comparative analytics | Analytics | New |
| 51 | Fee collection forecasting | Analytics/Finance | New |
| 52 | Petty cash + multi-currency | Accounting | New |
| 53 | Hostel/mess management (+invoiced fees) | Operations | Keep/Rebuild |
| 54 | Self-service report builder | Analytics | New |
| 55 | Integrations config + marketplace | Settings | New |

---

## Cross-cutting redesign principles (apply to all Admin App screens)

1. **Approvals → one inbox.** Replace every bespoke approve/reject screen (leave, expenses, advances, requisitions, lesson plans, concessions, admissions, driver/transport changes, profile changes) with a unified, configurable approvals inbox.
2. **Capture leaves the Admin App.** Mark attendance, enter marks, clock-in, pickup verification, fee payment → Staff/Parent apps. Admin App keeps **configure / approve / oversee / report**.
3. **Permission-first, branch-scoped.** Every screen respects the new RBAC and branch/tenant scope; Super Admin sees all, others see least-privilege.
4. **Settings consolidated.** One Settings hub, not config scattered across Academics/Finance/HR.
5. **Reports promoted.** Surface API-only reports (trends/mastery) and add the missing leadership/board reports.
6. **CBC & Accounting are rebuilds, not ports.** These two modules carry the biggest gaps and define the product; design them fresh.
7. **Mobile-first, glanceable.** Dense web tables become prioritized cards + filters + search; heavy exports run async.

> Next artifacts: `02-admin-information-architecture.md` (navigation/IA), `03-admin-ui-specs.md` (per-screen UI specs for Stitch/Figma — mirroring [`../app-split/06-ui-specifications.md`](../app-split/06-ui-specifications.md)).
