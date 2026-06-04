# 02 — Module Inventory

> Every module discovered in the Laravel codebase. For each: **Purpose · Key features · Screens (web) · APIs (mobile) · Database tables · Roles · Dependencies · Current gaps.**
> "Screens" = web portal controllers/views. "APIs" = `routes/api.php` endpoints (mobile). CRUD summarized where repetitive.

---

## A. Academics domain

### A1. Admissions
- **Purpose:** Bring new learners into the school (online applications + manual entry).
- **Features:** Public application form; review queue (pending → under_review → enrolled/rejected/waitlisted); auto-create `Student`, link `ParentInfo`/family, KNEC assessment number, welcome comms, initial fee posting.
- **Screens:** `Students\OnlineAdmissionController` (apply, review, approve/reject/waitlist/transfer/status); `StudentController::store`.
- **APIs:** (admissions handled on portal; mobile creates students via `/students`).
- **Tables:** `online_admissions`, `students`, `parent_info`, `families`.
- **Roles:** Public (apply); Admin/Secretary (review).
- **Dependencies:** Finance (`FeePostingService`), Communication, Family linking.
- **Gaps:** No intake/cohort entity; no application fee handling; no document checklist; no offer-letter generation.

### A2. Academic calendar
- **Purpose:** Define years, terms, school days, holidays.
- **Features:** Academic years; terms (open/close, midterm, expected school days); school-day calendar with auto Kenyan holiday generation; events.
- **Screens:** `Academics\AcademicConfigController`, `Settings\SchoolDayController`, `EventCalendarController`.
- **Tables:** `academic_years`, `terms`, `term_days`, `school_days`, `events`.
- **Roles:** Admin/Secretary.
- **Gaps:** No published parent-facing calendar/ICS export.

### A3. Classes, Streams & Campus
- **Purpose:** Organizational structure of learners.
- **Features:** Classrooms (campus `lower`/`upper`, level, `next_class_id` for promotion, class teacher); streams (many-to-many to classrooms); teacher assignment.
- **Screens:** `ClassroomController`, `StreamController`, `AssignTeachersController`.
- **APIs:** `/classes`, `/classes/{id}/streams`, `/classes/{id}/subjects`.
- **Tables:** `classrooms`, `streams`, `classroom_stream`, `class_teacher_assignments`.
- **Gaps:** Legacy `classes` table possibly dead; campus is an enum (not a real branch/tenant).

### A4. Subjects & Learning Areas
- **Purpose:** Curriculum subject catalog (CBC-rationalized).
- **Features:** Subjects (`code`, `learning_area`, `level`); learning areas; KICD rationalized subject sync; class-subject assignment with lessons/week + teacher.
- **Screens:** `SubjectController`, `LearningAreaController`.
- **Tables:** `subjects`, `learning_areas`, `classroom_subjects` (+ legacy `classroom_subject`, `subject_teacher`).
- **Gaps:** `subject_groups` dropped (no optional-pathway grouping); two class-subject sources of truth.

### A5. Timetable
- **Purpose:** Generate and publish school timetables.
- **Features:** Legacy per-class generator; optimization engine; **whole-school generator** with feasibility validation, generation runs, layout templates, slot locks & dated overrides, substitutions (draft).
- **Screens:** `TimetableController` (+ `Services/Timetable/WholeSchoolGenerator`, `FeasibilityValidator`).
- **APIs:** `/timetables/teacher/{id}`, `/timetables/student/{id}`.
- **Tables:** `timetables`, `time_periods`, `timetable_layout_*`, `timetable_stream_*`, `timetable_generation_runs`, `timetable_generated_slots`, `timetable_slot_locks`, `timetable_slot_overrides`.
- **Gaps:** Substitutions not applied to live published timetables; no cover/relief notifications.

### A6. Attendance (student)
- **Purpose:** Daily learner attendance.
- **Features:** Per-class/stream daily bulk marking; reason codes; analytics (at-risk, consecutive absences, trends); parent notification rules.
- **Screens:** `Attendance\AttendanceController`, `AttendanceNotificationController`, `AttendanceReasonCodeController`.
- **APIs:** `/attendance/class`, `/attendance/mark`, `/students/{id}/attendance-calendar`.
- **Tables:** `attendance`, `attendance_reason_codes`, `attendance_recipients`.
- **Gaps:** Period/subject-level attendance schema exists but practice is once-per-day; no biometric/QR.

### A7. Homework, Diary & Assignments
- **Purpose:** Classwork and home communication.
- **Features:** Homework CRUD; homework diary (submit/mark, links to lesson plan); student & parent diaries.
- **Screens:** `HomeworkController`, `HomeworkDiaryController`, `StudentDiaryController`, `ParentPortal\DiaryController`.
- **APIs:** `/assignments` (CRUD), `/assignments/{id}`.
- **Tables:** `homework`, `homework_student`, `homework_diary`/`homework_diaries`, `student_diaries`, `diary_entries`.
- **Gaps:** No real student submission/grading LMS; diary subsystem churned (dropped tables).

### A8. Lesson Plans
- **Purpose:** Teacher lesson planning with supervisory review.
- **Features:** CRUD; CBC fields (substrand, core competencies, learning outcomes); submission workflow (draft→submitted→approved/rejected); review queue; AI assist; PDF/Excel; pace analytics.
- **Screens:** `LessonPlanController`.
- **APIs:** `/lesson-plans` (CRUD, submit, review-queue, approve, reject).
- **Tables:** `lesson_plans`.
- **Roles:** Teacher (author); Senior Teacher/Academic Admin (review).
- **Gaps:** Web has no `submit` route (submit is mobile-only); no curriculum-coverage rollup.

### A9. Schemes of Work
- **Purpose:** Term-level subject plans aligned to CBC.
- **Features:** CRUD; auto-generation from learning areas/strands/substrands; approve/export/generate.
- **Screens:** `SchemeOfWorkController` (+ `SchemeOfWorkAutoGenerationService`).
- **Tables:** `schemes_of_work`.
- **Gaps:** Coverage stored as JSON arrays, not week-by-week child rows; no automatic coverage-tracking vs delivery.

### A10. Exams & Assessment
- **Purpose:** Summative (and some formative) assessment.
- **Features:** Exam types/groups/sessions/schedules; marks entry (bulk + matrix); grading schemes/bands; publishing; analytics (class sheet, teacher/subject performance, trends, mastery).
- **Screens:** `ExamController`, `ExamScheduleController`, `ExamMarkController`, `ExamGradeController`, `ExamPublishingController`, `ExamAnalyticsController`, `ExamReportsController`, `AssessmentController`.
- **APIs:** `/exams`, `/exams/{id}`, `/exams/{id}/marking-options`, `/marks`, `/exam-marks/batch`, `/marks/matrix*`, `/reports/exams/*`.
- **Tables:** `exams`, `exam_types`, `exam_groups`, `exam_sessions`, `exam_schedules`, `exam_marks`, `exam_items`, `grading_schemes`, `grading_bands`, `assessments`, `exam_grades` (legacy).
- **Gaps:** Two parallel assessment systems (`Assessment` vs `Exam`); CBC competency capture depends on manual JSON.

### A11. CBC / CBE
- **Purpose:** Competency-based curriculum support.
- **Features:** Learning areas, strands, sub-strands, core competencies, performance levels; CBC report-card blocks; portfolio assessments; rubrics; **curriculum design PDF ingestion** (parse → pages → embeddings) + **AI assistant** (RAG generation of schemes/lessons/assessments).
- **Screens:** `CBCStrandController`, `CBCSubstrandController`, `LearningAreaController`, `CompetencyController`, `PortfolioAssessmentController`, `CurriculumDesignController`, `CurriculumAssistantController`.
- **Tables:** `cbc_strands`, `cbc_substrands`, `cbc_core_competencies`, `cbc_performance_levels`, `learning_areas`, `competencies`, `portfolio_assessments`, `assessment_rubrics`, `curriculum_designs`, `curriculum_pages`, `curriculum_embeddings`.
- **Gaps:** Performance levels mapped from %, not per-outcome rubric (E.E/M.E/A.E/B.E); regex (not LLM) PDF extraction; no KNEC reporting; portfolio optional. Full analysis in [`06-academic-audit.md`](./06-academic-audit.md).

### A12. Report Cards
- **Purpose:** Termly learner reports.
- **Features:** Skills, behaviours, remarks; CBC JSON blocks; batch generation; publish + public token; fee-balance access gate; PDF.
- **Screens:** `ReportCardController`, `ReportCardSkillController`, `BehaviourController`.
- **APIs:** `/report-cards`, `/report-cards/{id}`.
- **Tables:** `report_cards`, `report_card_skills`, `student_skill_grades`, `behaviours`, `student_behaviours`.
- **Gaps:** Body still exam-mark-driven; not official MoE/KICD layout.

---

## B. Finance domain
> Deep analysis in [`07-finance-audit.md`](./07-finance-audit.md).

### B1. Voteheads & Fee Structures
- **Purpose:** Fee catalog and per-class/term charges.
- **Tables:** `voteheads`, `votehead_categories`, `fee_structures`, `fee_charges`, `fee_structure_versions`.
- **Screens:** `VoteheadController`, `FeeStructureController`.

### B2. Specialized fees
- Optional (`OptionalFeeController`, `optional_fees`), Transport (`TransportFeeController`, `transport_fees`/`transport_fee_revisions`), Uniform (`UniformFeeService`), Activity (`ActivityBillingService`, `activity_fee_attendances`), Swimming wallet (`SwimmingWalletController`, `swimming_wallets`/`swimming_ledger`), Hostel rate cards (`hostel_fees` — **not wired to posting**).

### B3. Invoicing & Posting
- **Purpose:** Generate per-student/per-term invoices from charges.
- **Features:** `FeePostingService` with `FeePostingRun` + `PostingDiff` (dry-run/commit/reverse); one invoice per (student, year, term).
- **Screens:** `InvoiceController`, `PostingController`, `InvoiceAdjustmentController`.
- **APIs:** `/invoices`, `/invoices/{id}`, `/fee-structures`.
- **Tables:** `invoices`, `invoice_items`, `fee_posting_runs`, `posting_diffs`.

### B4. Payments, Allocation & Balances
- **Purpose:** Collect and apply money.
- **Features:** Cash/bank/M-Pesa/Jenga; `PaymentAllocationService` (FIFO, oldest-invoice-first, votehead-level, sibling sharing); receipts + numbering; reversals.
- **Screens:** `PaymentController`, `PaymentMethodController`, `BankAccountController`.
- **APIs:** `/payments` (CRUD), `/students/{id}/mpesa/prompt`, `/students/{id}/statement`, `/students/{id}/fee-clearance`, `/finance/transactions/*`.
- **Tables:** `payments`, `payment_allocations`, `receipts`, `payment_transactions`, `payment_links`, `payment_methods`, `bank_accounts`.

### B5. Bank statement & M-Pesa reconciliation
- **Features:** C2B inbox + bank import + smart matching (admission/invoice/phone/name + learned); confirm/reject/share/swimming reclass.
- **Screens:** `BankStatementController`, `MpesaPaymentController`, `TransactionFixAuditController`.
- **Tables:** `mpesa_c2b_transactions`, `bank_statement_transactions`, `manual_match_learnings`, `transaction_fix_audit`.

### B6. Adjustments, concessions, plans, reminders
- Credit/debit notes (`credit_notes`, `debit_notes`), fee journals (`journals` — adjust invoice lines), concessions/discounts (`fee_concessions`, `discount_templates`), payment plans (`fee_payment_plans` + installments, cron status), reminders (`fee_reminders`, `scheduled_fee_communications`).
- **Screens:** `CreditNoteController`, `DebitNoteController`, `JournalController`, `FeeConcessionController`, `DiscountController`, `FeePaymentPlanController`, `FeeReminderController`, `ScheduledFeeCommunicationController`.

### B7. Expenses & Vouchers
- **Purpose:** Outgoing money with approval.
- **Features:** Expense draft→submit→approve→voucher→pay; categories; vendors; basic GL stub (`ledger_postings` EXPENSE/CASH_BANK).
- **Screens:** `ExpenseController`, `ExpenseApprovalController`, `ExpenseReportController`, `ExpenseCategoryController`, `PaymentVoucherController`, `VendorController`.
- **Tables:** `expenses`, `expense_lines`, `expense_categories`, `expense_approvals`, `payment_vouchers`, `expense_payments`, `vendors`, `ledger_postings`.

### B8. Payroll
- **Purpose:** Staff salaries.
- **Features:** Salary structures, periods (process/lock), records, payslips (PDF), advances, deductions (Kenya PAYE/NSSF/NHIF via `PayrollCalculationService`).
- **Screens:** `Hr\SalaryStructureController`, `PayrollPeriodController`, `PayrollRecordController`, `PayslipController`, `StaffAdvanceController`, `DeductionTypeController`, `CustomDeductionController`.
- **APIs:** `/payroll-records`.
- **Tables:** `salary_structures`, `payroll_periods`, `payroll_records`, `salary_history`, `staff_advances`, `deduction_types`, `custom_deductions`, `staff_statutory_exemptions`.

### B9. Legacy finance import
- **Tables:** `legacy_finance_import_batches`, `legacy_statement_terms`, `legacy_statement_lines`, `balance_brought_forward_imports`, `fees_comparison_previews`.
- **Screens:** `LegacyFinanceImportController`, `BalanceBroughtForwardController`, `FeesComparisonImportController`.

**Finance gaps (summary):** No chart of accounts / double-entry GL / trial balance / P&L / balance sheet / cash flow / budgeting / period close / petty cash / fixed assets; M-Pesa refunds unimplemented; hostel fees not invoiced.

---

## C. People & HR domain

### C1. Students & Families
- **Screens:** `StudentController`, `StudentCategoryController`, `FamilyController`, `ParentInfoController`, `FamilyUpdateController`, `FamilyIntegrityReportController`; per-student `MedicalRecordController`, `DisciplinaryRecordController`, `ExtracurricularActivityController`, `AcademicHistoryController`.
- **APIs:** `/students` (CRUD + stats + statement + profile-update-link), `/student-categories`.
- **Tables:** `students`, `families`, `parent_info`, `student_categories`, `student_siblings`, `student_medical_records`, `student_disciplinary_records`, `student_academic_history`, `family_update_links`.
- **Gaps:** Parent data triad (`users`/`parent_info`/`families`) can diverge; medical/disciplinary are records, not workflow modules.

### C2. Staff / HR
- **Screens:** `Hr\StaffController`, `StaffProfileController`, `ProfileChangeController`, `LeaveTypeController`, `LeaveRequestController`, `StaffLeaveBalanceController`, `StaffAttendanceController`, `StaffDocumentController`, `RolePermissionController`, `LookupController`, `StaffReportController`, `HRAnalyticsController`.
- **APIs:** `/staff` (index/show/update/photo), `/leave-types`, `/leave-requests` (+approve/reject), `/staff-attendance/*` (geofence clock-in/out, roster, history).
- **Tables:** `staff`, `departments`, `job_titles`, `staff_categories`, `leave_types`, `leave_requests`, `staff_leave_balances`, `staff_attendance`, `staff_documents`, `staff_profile_changes`, `staff_supervisor`, plus performance/training tables (`performance_reviews`, `training_records`, `staff_skills`, `staff_certifications`).
- **Gaps:** Performance/training tables exist but limited UI; no recruitment/onboarding; no HR Officer role.

### C3. Roles & Permissions
- **Screens:** `Hr\RolePermissionController`, `Admin\SeniorTeacherAssignmentController`.
- **Tables:** Spatie (`roles`, `permissions`, `role_has_permissions`, `model_has_roles`, `model_has_permissions`).
- **Gaps:** See [`04-role-audit.md`](./04-role-audit.md) — fragmented seeders, missing roles, broad bypasses.

---

## D. Operations domain

### D1. Transport
- **Screens:** `TransportController`, `VehicleController`, `TripController`, `DropOffPointController`, `StudentAssignmentController`, `Transport\TripAttendanceController`, `DriverChangeRequestController`, `TransportSpecialAssignmentController`, `TransportImportController`, `DailyTransportListController`, `Driver\DriverController`, `Teacher\TransportController`.
- **APIs:** `/routes`, `/routes/{id}`, `/driver/trips`, `/driver/trips/{id}`, `/teacher/transport/*` (roster, pickups, reassign).
- **Tables:** `vehicles`, `routes`, `route_vehicle`, `drop_off_points`, `trips`, `trip_stops`, `trip_attendances`, `student_assignments`, `transport_special_assignments`, `driver_change_requests`, `student_daily_pickups`.
- **Gaps:** No live GPS tracking; legacy `trip`/`transport` tables; pickup verification basic (no QR/OTP); no Transport Manager role.

### D2. Library
- **Screens:** `Library\BookController`, `LibraryCardController`, `BookBorrowingController`.
- **APIs:** `/library/books`.
- **Tables:** `books`, `book_copies`, `book_borrowings`, `book_reservations`, `library_cards`, `library_fines`.
- **Gaps:** No utilization/overdue analytics; no Librarian role; minimal student self-service.

### D3. Inventory, Requirements & Requisitions
- **Screens:** `Inventory\InventoryItemController`, `RequirementTypeController`, `RequirementTemplateController`, `RequirementTemplateAssignmentController`, `StudentRequirementController`, `RequisitionController`.
- **APIs:** `/teacher/requirements/*`.
- **Tables:** `inventory_types`, `inventory_items`, `inventory_transactions`, `requisitions`, `requisition_items`, `requirement_types`, `requirement_templates`, `student_requirements`, `item_receipts`.
- **Gaps:** No stock valuation/consumption reports; no procurement/PO; no Store Keeper role.

### D4. POS & Public Shop
- **Screens:** `Pos\ProductController`, `ProductVariantController`, `OrderController`, `DiscountController`, `PublicShopLinkController`, `UniformController`, `PublicShopController`, `Pos\PaymentController`.
- **APIs:** `/public/shop/*` (token).
- **Tables:** `pos_products`, `pos_product_variants`, `pos_orders`, `pos_order_items`, `pos_discounts`, `pos_public_shop_links`.

### D5. Hostel / Mess
- **Screens:** `Hostel\HostelController`, `HostelAllocationController`.
- **Tables:** `hostels`, `hostel_rooms`, `hostel_allocations`, `hostel_fees`, `hostel_attendance`, `mess_menus`, `mess_subscriptions`.
- **Gaps:** Hostel fees not invoiced; mess subscription billing unclear.

### D6. Swimming & Activities
- **Screens:** `Swimming\SwimmingAttendanceController`, `SwimmingWalletController`, `SwimmingPaymentController`, `SwimmingReportController`, `SwimmingSettingsController`, `Activities\ActivityFeeController`, `ExtracurricularActivityController`.
- **Tables:** `swimming_wallets`, `swimming_ledger`, `swimming_attendance`, `swimming_transaction_allocations`, `extra_curricular_activities`, `student_extracurricular_activities`, `activity_fee_attendances`.

---

## E. Communication domain
- **Purpose:** Multi-channel messaging to parents/staff/students.
- **Features:** Email/SMS/WhatsApp send with progress/retry; announcements; templates & placeholders; scheduled & automated fee comms; delivery reports + SMS DLR; WhatsApp Wasender sessions; parent notification blocks; printed notes.
- **Screens:** `CommunicationController`, `CommunicationAnnouncementController`, `CommunicationTemplateController`, `CommunicationDocumentController`, `CommunicationNoteController`, `WasenderSessionController`, `ParentNotificationBlockController`, `FeeReminderAutomationController`, `PlaceholderController`.
- **APIs:** `/announcements`, `/notifications` (+ read/mark-all/delete), `/notification-preferences`, `/device-tokens`.
- **Tables:** `communication_templates`, `communication_logs`, `communication_placeholders`, `custom_placeholders`, `scheduled_communications`, `scheduled_fee_communications`, `sms_logs`, `announcements`, `user_device_tokens`.
- **Gaps:** No two-way chat UI; no in-app deep-link routing; SMS `sms_logs` vs `communication_logs` duplication.

---

## F. Documents domain
- **Screens:** `DocumentManagementController`, `DocumentTemplateController`, `GeneratedDocumentController`, `MediaController`, `FileDownloadController`.
- **Tables:** `documents` (polymorphic `documentable`), `document_templates`, `generated_documents`.
- **Dependencies:** DomPDF, S3.

---

## G. Platform / System domain
- **Settings:** `Settings\SettingController`, `SchoolDayController`, `GalleryController` → `settings`, `general_settings`, `branding_settings`, `regional_settings`, `module_settings`, `gallery_images`. Branding feeds mobile `/app-branding`.
- **Backup & Restore:** `BackupRestoreController` (create/download/restore/schedule/purge) → `backup_settings`.
- **Audit & Logs:** `ActivityLogController`, `SystemLogController` → `activity_logs`, `audit_logs`.
- **Dashboards:** `DashboardController` (admin/teacher/supervisor/parent/student/finance/transport) + `AccountantDashboardController`, `HRAnalyticsController`.
- **Reports:** see [`09-reporting.md`](./09-reporting.md).

---

## H. Module coverage matrix (vs the requested module list)

| Requested module | Status | Notes |
|------------------|--------|-------|
| Admissions | ✅ | Online + manual |
| Students | ✅ | Rich |
| Academics | ✅ | Broad |
| CBC/CBE | ⚠️ Partial | Schema yes; practice exam-centric |
| Attendance | ✅ | Daily; period-level unused |
| Examinations | ✅ | Mature |
| Report Cards | ✅ | Exam-driven |
| Finance | ✅ | Receivables-strong |
| Accounting | ❌ Missing | No GL/double-entry |
| Payroll | ✅ | Kenya statutory |
| Transport | ✅ | No live tracking |
| Library | ✅ | CRUD; no analytics |
| Inventory | ✅ | No valuation |
| Procurement | ⚠️ Partial | Requisitions only |
| POS | ✅ | + public shop |
| Hostel | ✅ | Fees not invoiced |
| HR | ✅ | Perf/training thin |
| Communication | ✅ | Multi-channel |
| Documents | ✅ | Templates + generation |
| Clinic | ⚠️ Partial | Student medical records only |
| Visitor Management | ❌ Missing | — |
| Security | ❌ Missing | Demo role only |
| Assets | ❌ Missing | Inventory ≠ fixed assets |
| Events | ⚠️ Partial | Calendar only |
| Timetable | ✅ | Whole-school engine |
| Assignments | ✅ | Homework/diary |
| Lesson Plans | ✅ | + review |
| Schemes of Work | ✅ | + auto-gen |
| Discipline | ⚠️ Partial | Records, not workflow |
| Approvals | ⚠️ Fragmented | Per-module, no unified inbox |
| Settings | ✅ | Branding + modules |
