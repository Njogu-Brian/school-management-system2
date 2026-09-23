# Redundancy and dead-code findings (verified, 2026-09-22)

Static analysis of the live tree. **Unused** means no `view()`, `@include`, `@extends`, `loadView`, or `<x-…>` string matched the view name. Dynamic/variable includes can hide real use — treat as candidates, not delete-without-review.

## Headline numbers

- Blade files: 861
- Controllers / public methods: 371 / 1986
- Models: 323
- Services / commands / jobs: 193 / 96 / 15
- Policies / FormRequests: 8 / 17  (RBAC and validation are thin vs controller count)
- Markdown docs: 169
- Full CRUD view sets (index+create+edit+show): 31
- Partial CRUD view sets: 89
- Create+edit **without** a shared form partial: 46
- Create+edit **with** a shared form partial: 6

## 1. Likely-dead Blade pages (not framework, not unused design-system components)

These did not match any static view reference. Highest-value cleanup candidates.

| View | Lines | Why it looks dead |
|---|---:|---|
| `academics.assign_class_teacher` | 47 | Superseded by dedicated assignment screens / AssignTeachersController views |
| `academics.class` | 27 | Legacy singular class page; classrooms now live under academics.classrooms.* |
| `academics.class_timetable` | 27 | Superseded by academics.timetable.* |
| `academics.curriculum_assistant.index` | 139 | Check CurriculumAssistantController — may have been renamed |
| `academics.exam_results.publish_summary` | 61 | No static reference found |
| `academics.promote_students` | 47 | Superseded by academics.promotions.* |
| `academics.report_cards.edit` | 54 | No static reference found |
| `academics.report_cards.partials.publish-modal` | 80 | No static reference found |
| `academics.sections` | 27 | No matching controller view() in Academics |
| `academics.teacher_timetable` | 27 | Superseded by academics.timetable.teacher_* |
| `communication.templates.send_sms` | 52 | Likely inlined into communication send flow |
| `dashboard.partials.absence_table` | 34 | No static reference found |
| `dashboard.partials.activity` | 34 | No static reference found |
| `dashboard.partials.enrolment_chart` | 7 | No static reference found |
| `dashboard.partials.exam_performance` | 11 | No static reference found |
| `dashboard.partials.overview` | 32 | No static reference found |
| `dashboard.partials.recent_admissions` | 47 | No static reference found |
| `dashboard.partials.students` | 41 | No static reference found |
| `dashboard.partials.summary` | 24 | No static reference found |
| `dashboard.partials.system_health` | 15 | No static reference found |
| `dashboard.partials.transport_widget` | 34 | No static reference found |
| `exports.directory_table` | 68 | No static reference found |
| `exports.enrollment_by_class` | 79 | No static reference found |
| `finance.credit_debit_adjustments.create` | 97 | Adjustments may be created from invoice screens only |
| `finance.credit_debit_adjustments.show` | 2 | Detail page unused if index is enough |
| `finance.discounts.allocate` | 225 | No static reference found |
| `finance.mpesa.c2b-allocate` | 984 | No static reference found |
| `finance.transport_fees.partials.import_tabs` | 95 | No static reference found |
| `partials.academic_term_options` | 12 | No static reference found |
| `partials.sms-form` | 27 | No static reference found |
| `settings.access_lookups` | 205 | No static reference found |
| `settings.partials.features` | 41 | No static reference found |
| `settings.term_days.index` | 205 | No static reference found |
| `staff.partials.staff_bank_statutory` | 37 | No static reference found |
| `student_assignments.bulk_assign` | 178 | Check StudentAssignmentController for renamed views |
| `student_assignments.create` | 78 | May be unused if assignment is modal/elsewhere |
| `student_assignments.edit` | 78 | May be unused if assignment is modal/elsewhere |
| `students.bulk-parse` | 78 | No static reference found |
| `transport.create` | 54 | No static reference found |
| `transport.edit` | 57 | No static reference found |
| `transport.show` | 33 | No static reference found |
| `transport.student_dropoffs.index` | 247 | No static reference found |

Count: **42** after removing dynamic nav includes (see below). The scanner originally listed 45.

### False positives (used dynamically — do not delete)

| View | How it is actually used |
|---|---|
| `layouts.partials.nav-admin` (1105 lines) | `App\Support\NavAccess` returns this view name by role |
| `layouts.partials.nav-senior-teacher` (706 lines) | Same |
| `layouts.partials.nav-teacher` (392 lines) | Same |

### Dead controller + view stacks (unrouted)

These controllers are imported or present but have **no route**. Their views exist only because the dead controller `view()`s them.

| Controller | Views it still points at | Replaced by |
|---|---|---|
| `Finance\FeeStatementController` | `finance.fee_statements.index`, `finance.fee_statements.show` | `StudentStatementController` |
| `Finance\ReceiptController` | `finance.receipts.index`, `finance.receipts.show` | `PaymentController` receipt actions |
| `HomeController` | `home` | `DashboardController` |
| Filename typo `resources/views/finance/invoices/adjust,blade.php` | unreachable (comma instead of `.blade.php`) | `finance.invoices` flows |

## 2. Design-system Blade components with no `<x-…>` hits

These sit in `resources/views/components` (Sprint UI kit). They may be unused because screens still use AdminLTE/Bootstrap markup, or they are referenced in a way the scanner missed.

- `components.data.actions` (5 lines)
- `components.data.filter-bar` (10 lines)
- `components.data.pagination` (5 lines)
- `components.divider` (2 lines)
- `components.feedback.empty-state` (8 lines)
- `components.feedback.loading` (6 lines)
- `components.form.checkbox` (8 lines)
- `components.form.input` (8 lines)
- `components.form.radio` (8 lines)
- `components.form.select` (13 lines)
- `components.form.textarea` (7 lines)
- `components.icon-button` (15 lines)
- `components.nav.page-actions` (3 lines)
- `components.nav.tabs` (12 lines)
- `components.section` (11 lines)
- `components.student-search` (210 lines)

## 3. PDF / print / public / email views with no static reference

Often loaded dynamically (`Pdf::loadView($template)`). Confirm before deleting.

- `academics.lesson_plans.pdf` (200 lines)
- `academics.report_cards.public` (26 lines)
- `academics.report_cards.skills.pdf` (118 lines)
- `academics.schemes_of_work.pdf` (108 lines)
- `communication.templates.send_email` (52 lines)
- `emails.system-alert` (16 lines)
- `finance.fee_balances.pdf` (231 lines)
- `finance.fee_clearance.pdf_by_class` (112 lines)
- `finance.receipts.pdf.basic` (70 lines)
- `finance.receipts.print` (285 lines)
- `partials.email-form` (27 lines)

## 4. Framework-owned unused-looking views (do not delete casually)

- `auth.passwords.confirm`
- `auth.register`
- `auth.verify`
- `errors.401`
- `errors.402`
- `errors.403`
- `errors.404`
- `errors.419`
- `errors.429`
- `errors.500`
- `errors.503`
- `errors.layout`
- `errors.minimal`
- `welcome`

## 5. Create/edit duplication (no shared `_form` / `form` partial)

Each pair likely copies the same fields twice. Highest-ROI Blade DRY.

- `academics.behaviours.create` + `academics.behaviours.edit`
- `academics.cbc_strands.create` + `academics.cbc_strands.edit`
- `academics.cbc_substrands.create` + `academics.cbc_substrands.edit`
- `academics.classrooms.create` + `academics.classrooms.edit`
- `academics.competencies.create` + `academics.competencies.edit`
- `academics.curriculum_designs.create` + `academics.curriculum_designs.edit`
- `academics.exams.create` + `academics.exams.edit`
- `academics.extra_curricular_activities.create` + `academics.extra_curricular_activities.edit`
- `academics.homework.create` + `academics.homework.edit`
- `academics.learning_areas.create` + `academics.learning_areas.edit`
- `academics.lesson_plans.create` + `academics.lesson_plans.edit`
- `academics.portfolio_assessments.create` + `academics.portfolio_assessments.edit`
- `academics.report_cards.skills.create` + `academics.report_cards.skills.edit`
- `academics.schemes_of_work.create` + `academics.schemes_of_work.edit`
- `academics.streams.create` + `academics.streams.edit`
- `academics.subjects.create` + `academics.subjects.edit`
- `attendance.reason_codes.create` + `attendance.reason_codes.edit`
- `attendance_notifications.create` + `attendance_notifications.edit`
- `communication.announcements.create` + `communication.announcements.edit`
- `communication.templates.create` + `communication.templates.edit`
- `dropoffpoints.create` + `dropoffpoints.edit`
- `events.create` + `events.edit`
- `finance.bank-statements.create` + `finance.bank-statements.edit`
- `finance.bank_accounts.create` + `finance.bank_accounts.edit`
- `finance.expenses.create` + `finance.expenses.edit`
- `finance.payment_methods.create` + `finance.payment_methods.edit`
- `finance.vendors.create` + `finance.vendors.edit`
- `hr.payroll.advances.create` + `hr.payroll.advances.edit`
- `hr.payroll.custom-deductions.create` + `hr.payroll.custom-deductions.edit`
- `hr.payroll.deduction-types.create` + `hr.payroll.deduction-types.edit`
- `hr.payroll.salary-structures.create` + `hr.payroll.salary-structures.edit`
- `inventory.items.create` + `inventory.items.edit`
- `inventory.requirement-template-assignments.create` + `inventory.requirement-template-assignments.edit`
- `inventory.requirement-templates.create` + `inventory.requirement-templates.edit`
- `staff.create` + `staff.edit`
- `staff.leave_types.create` + `staff.leave_types.edit`
- `student_assignments.create` + `student_assignments.edit`
- `student_categories.create` + `student_categories.edit`
- `students.create` + `students.edit`
- `students.records.academic.create` + `students.records.academic.edit`
- `students.records.activities.create` + `students.records.activities.edit`
- `students.records.disciplinary.create` + `students.records.disciplinary.edit`
- `students.records.medical.create` + `students.records.medical.edit`
- `transport.create` + `transport.edit`
- `trips.create` + `trips.edit`
- `vehicles.create` + `vehicles.edit`

## 6. Create/edit pairs that already share a form (good pattern)

- `finance.payment_thresholds`
- `finance.voteheads`
- `pos.products`
- `website.blogs`
- `website.events`
- `website.pages`

## 7. Parallel / overlapping modules (structural redundancy)

These are not unused — they are **duplicate domains** that grew side by side.

| Cluster | Locations | What it means |
|---|---|---|
| Staff vs HR | `staff/*` views + `StaffController` (root/Hr) vs `hr/*` | Staff CRUD/leave/profile lives beside payroll HR. Two IA trees for one people domain. |
| Transport vs vehicles vs trips vs dropoffpoints | `transport/`, `vehicles/`, `trips/`, `dropoffpoints/` | Vehicle/trip/drop-off were built as separate CRUD apps instead of one transport bounded context. |
| Family vs families vs family_update vs parent vs parents | five Blade trees | Family hub, parent diary, family update portal, and leftover `parents`/`family` folders overlap. |
| Activity logs vs system-logs vs sms_logs | three one-page modules | Three log UIs instead of one observability area. |
| Setting vs SystemSetting | `Setting` used; `SystemSetting` unused | Dead twin model. |
| Hostel / mess / kitchen models | models with 0 refs | Boarding/mess was modelled then abandoned. |
| HR performance/training models | Performance*, Training*, StaffSkill/Certification/Qualification | Staff 360 schema without web controllers. |
| Extra-curricular double routes | `extra-curricular-activities` resource AND `activities` aliases | Same controller bound twice in `routes/web.php`. |
| Website vs core school | 31 Website controllers + 48 Website models | A second product (public CMS) inside the ERP. |
| Web Blade vs mobile API | 109 Api controllers vs 262 web | Same domains implemented twice (web+JSON) — expected, but method-level duplication is high. |

## 8. Models with zero references outside themselves

Genuinely orphaned Eloquent classes (no controller/service/route mention).

- `ExamItem` — `app/Models/Academics/ExamItem.php` (table `exam_items`)
- `HostelAttendance` — `app/Models/HostelAttendance.php` (table `hostel_attendance`)
- `KitchenRecipient` — `app/Models/KitchenRecipient.php` (table `kitchen_recipients`)
- `MessMenu` — `app/Models/MessMenu.php` (table `mess_menus`)
- `MessSubscription` — `app/Models/MessSubscription.php` (table `mess_subscriptions`)
- `PerformanceFeedback` — `app/Models/PerformanceFeedback.php` (table `performance_feedbacks`)
- `PerformanceGoal` — `app/Models/PerformanceGoal.php` (table `performance_goals`)
- `StaffCertification` — `app/Models/StaffCertification.php` (table `staff_certifications`)
- `StaffQualification` — `app/Models/StaffQualification.php` (table `staff_qualifications`)
- `StaffSkill` — `app/Models/StaffSkill.php` (table `staff_skills`)
- `SystemSetting` — `app/Models/SystemSetting.php` (table `system_settings`)
- `TrainingCourse` — `app/Models/TrainingCourse.php` (table `training_courses`)
- `TrainingRequest` — `app/Models/TrainingRequest.php` (table `training_requests`)
- `SeoMeta` — `app/Models/Website/SeoMeta.php` (table `seo_meta`)

## 9. Models used in only 1–2 files (thin / unfinished features)

Not dead, but often a stub feature or a model only wired to its own controller.

| Model | Files | Sample |
|---|---:|---|
| `AcademicReportAssignment` | 2 | `app/Http/Controllers/Api/ApiAcademicReportsController.php`, `app/Models/AcademicReports/AcademicReportTemplate.php` |
| `ExamGroup` | 2 | `app/Http/Controllers/Academics/ExamGroupController.php`, `app/Models/Academics/ExamType.php` |
| `GradingSchemeMapping` | 2 | `app/Http/Controllers/Academics/ExamClassroomGradingController.php`, `app/Services/Academics/ClassroomGradingService.php` |
| `StudentSkillGrade` | 2 | `app/Http/Controllers/Academics/StudentSkillGradeController.php`, `routes/web.php` |
| `TimePeriod` | 1 | `app/Services/TimetableOptimizationService.php` |
| `TimetableLayoutTemplate` | 2 | `app/Models/Academics/TimetableLayoutPeriod.php`, `app/Models/Academics/TimetableStreamLayout.php` |
| `TimetableSlotLock` | 2 | `app/Http/Controllers/Academics/TimetableController.php`, `app/Services/Timetable/WholeSchoolGenerator.php` |
| `TimetableSlotOverride` | 1 | `app/Http/Controllers/Academics/TimetableController.php` |
| `AppClientIssue` | 2 | `app/Http/Controllers/Api/ApiAppIssuesController.php`, `app/Http/Controllers/AppOpsController.php` |
| `AttendanceRecipient` | 2 | `app/Http/Controllers/Attendance/AttendanceNotificationController.php`, `database/seeders/Comprehensive2025Seeder.php` |
| `BalanceBroughtForwardImport` | 1 | `app/Http/Controllers/Finance/BalanceBroughtForwardController.php` |
| `BioTimePunch` | 2 | `app/Http/Controllers/Hr/StaffAttendanceController.php`, `app/Services/BioTime/BioTimeSyncService.php` |
| `BookReservation` | 2 | `app/Models/Book.php`, `app/Services/LibraryService.php` |
| `CommunicationJobRecipient` | 2 | `app/Models/CommunicationJob.php`, `app/Services/CommunicationJobService.php` |
| `CommunicationPlaceholder` | 2 | `app/helpers.php`, `app/Http/Controllers/PlaceholderController.php` |
| `CreditDebitNoteImport` | 2 | `app/Http/Controllers/Finance/CreditDebitNoteImportController.php`, `routes/web.php` |
| `CurriculumEmbedding` | 2 | `app/Models/CurriculumDesign.php`, `app/Services/EmbeddingService.php` |
| `CurriculumExtractionAudit` | 1 | `app/Models/CurriculumDesign.php` |
| `DiscountTemplate` | 2 | `app/Http/Controllers/Finance/DiscountController.php`, `app/Models/FeeConcession.php` |
| `DriverChangeRequest` | 2 | `app/Http/Controllers/Transport/DriverChangeRequestController.php`, `routes/web.php` |
| `ExpenseAttachment` | 2 | `app/Http/Controllers/Api/ApiExpensesController.php`, `app/Models/Expense.php` |
| `FamilyUpdateAudit` | 1 | `app/Http/Controllers/Students/FamilyUpdateController.php` |
| `FeesComparisonPreview` | 1 | `app/Http/Controllers/Finance/FeesComparisonImportController.php` |
| `FeeStructureVersion` | 1 | `app/Models/FeeStructure.php` |
| `GalleryImage` | 2 | `app/Http/Controllers/GalleryController.php`, `app/Http/Controllers/Settings/SettingController.php` |
| `HostelFee` | 1 | `app/Models/Hostel.php` |
| `InventoryType` | 1 | `app/Models/InventoryItem.php` |
| `ItemReceipt` | 2 | `app/Http/Controllers/Inventory/StudentRequirementController.php`, `app/Models/StudentRequirement.php` |
| `LedgerPosting` | 2 | `app/Http/Controllers/Api/ApiLedgerController.php`, `app/Services/Finance/JournalPostingService.php` |
| `LibraryFine` | 1 | `app/Services/LibraryService.php` |
| `OptionalFeeImport` | 2 | `app/Http/Controllers/Finance/OptionalFeeImportController.php`, `routes/web.php` |
| `OtpVerification` | 1 | `app/Services/OtpService.php` |
| `ParentWalletLedger` | 2 | `app/Models/ParentWallet.php`, `app/Services/ParentWalletService.php` |
| `PaymentWebhook` | 2 | `app/Http/Controllers/PaymentWebhookController.php`, `routes/web.php` |
| `PerformanceReview` | 1 | `app/Http/Controllers/Api/ApiStaffPerformanceController.php` |
| `PostingDiff` | 2 | `app/Models/FeePostingRun.php`, `app/Services/FeePostingService.php` |
| `StaffMeta` | 2 | `app/Http/Controllers/Hr/StaffController.php`, `app/Models/Staff.php` |
| `StudentConcern` | 2 | `app/Http/Controllers/Api/ApiConcernController.php`, `app/Http/Controllers/Operations/ConcernController.php` |
| `StudentDailyPickup` | 1 | `app/Http/Controllers/Api/ApiTeacherTransportController.php` |
| `StudentSibling` | 1 | `database/migrations/2025_03_27_075614_create_student_siblings_table.php` |
| `TrainingRecord` | 1 | `app/Http/Controllers/Api/ApiStaffTrainingController.php` |
| `TransactionFixAudit` | 2 | `app/Http/Controllers/Finance/TransactionFixAuditController.php`, `routes/web.php` |
| `TransportFeeImport` | 2 | `app/Http/Controllers/Finance/TransportFeeController.php`, `app/Services/TransportFeeService.php` |
| `TransportFeeRevision` | 2 | `app/Models/TransportFee.php`, `app/Services/TransportFeeService.php` |
| `TransportImportLog` | 1 | `app/Http/Controllers/Transport/TransportImportController.php` |
| `TripRunLocation` | 2 | `app/Http/Controllers/Api/ApiDriverTransportController.php`, `app/Models/TripRun.php` |
| `TripStop` | 1 | `app/Models/Trip.php` |
| `UserBiometricUnlock` | 2 | `app/Http/Controllers/Api/ApiAccountController.php`, `app/Http/Controllers/Api/AuthApiController.php` |
| `AiChatMessage` | 2 | `app/Models/Website/AiChatSession.php`, `app/Services/Website/SchoolAssistantService.php` |
| `AlumniStory` | 2 | `app/Http/Controllers/Api/Website/CommunityApiController.php`, `app/Http/Controllers/Website/CommunityAdminController.php` |
| `AssistantKnowledgeArticle` | 2 | `app/Http/Controllers/Website/AssistantKnowledgeController.php`, `app/Services/Website/SchoolAssistantService.php` |
| `BlogTag` | 1 | `app/Models/Website/Blog.php` |
| `ContentCalendarItem` | 2 | `app/Http/Controllers/Website/ContentCalendarController.php`, `app/Services/Website/MediaCmsService.php` |
| `ExitIntentCampaign` | 2 | `app/Http/Controllers/Website/ConversionManagerController.php`, `app/Services/Website/ConversionEngineService.php` |
| `MediaQualityFlag` | 2 | `app/Http/Controllers/Website/MediaLibraryController.php`, `app/Models/Website/MediaLibraryItem.php` |
| `MediaTag` | 2 | `app/Models/Website/MediaLibraryItem.php`, `app/Services/Website/MediaCmsService.php` |
| `PageBuilderDraft` | 1 | `app/Services/Website/PageBuilderService.php` |
| `PageRevision` | 1 | `app/Http/Controllers/Website/PageController.php` |
| `PrayerRequest` | 2 | `app/Http/Controllers/Api/Website/CommunityApiController.php`, `app/Http/Controllers/Website/CommunityAdminController.php` |
| `ReusableBlock` | 2 | `app/Http/Controllers/Website/ReusableBlockController.php`, `routes/web.php` |
| `TestimonialCategory` | 1 | `database/seeders/WebsiteSprints2130Seeder.php` |
| `VirtualTourStop` | 2 | `app/Http/Controllers/Api/Website/WebsiteMediaApiController.php`, `app/Http/Controllers/Website/VirtualTourController.php` |
| `WebsiteCompetition` | 2 | `app/Http/Controllers/Api/Website/StudentShowcaseApiController.php`, `app/Http/Controllers/Website/StudentSpotlightController.php` |
| `WebsiteEventRegistration` | 2 | `app/Http/Controllers/Api/Website/EventRegistrationApiController.php`, `app/Services/Website/BrandIntelligenceService.php` |
| `WebsiteMenu` | 2 | `app/Http/Controllers/Website/MenuController.php`, `app/Models/Website/WebsiteMenuItem.php` |
| `WebsiteMenuItem` | 1 | `app/Models/Website/WebsiteMenu.php` |

## 10. Controllers the scanner did not see as `FooController::class` in routes

Several are **false positives** (`use` + alias, base class, Concerns). Verified notes:

| Class | Verdict |
|---|---|
| `Controller` | Base class — ignore |
| `ResolvesDocumentStorage` | Trait/Concern — ignore |
| `ExtraCurricularActivityController` | **Routed** via alias `AcademicsExtraCurricularActivityController` |
| `FeeStatementController` / `ReceiptController` | Imported in `web.php` — confirm route bodies |
| `DiaryController` (ParentPortal) | **Routed** as `ParentDiaryController` |
| `ExamGroupController` / `ExamPaperController` | Check nested academics exam routes |
| `HomeController` | Possibly replaced by `DashboardController` |
| `SmsLogController` | Has `sms_logs/index` view — confirm route |
| `WebAuthnLoginController` / `WebAuthnRegisterController` | May be registered by Laragear package |
| `ApiParentRequirementsController` (scanner label `requirements`) | False parse of a method/class fragment |

## 11. Tiny controllers (merge candidates)

Two or fewer public methods — often a one-off endpoint that could live on a parent controller.

- `CurriculumAssistantController` (Academics) — generate, chat — `app/Http/Controllers/Academics/CurriculumAssistantController.php`
- `ExamAnalyticsController` (Academics) — index, classroomPerformance — `app/Http/Controllers/Academics/ExamAnalyticsController.php`
- `ExamPublishingController` (Academics) — publish — `app/Http/Controllers/Academics/ExamPublishingController.php`
- `ExamResultController` (Academics) — index, bulkStore — `app/Http/Controllers/Academics/ExamResultController.php`
- `StudentSkillGradeController` (Academics) — index, store — `app/Http/Controllers/Academics/StudentSkillGradeController.php`
- `ApiAnalyticsController` (Api) — executive — `app/Http/Controllers/Api/ApiAnalyticsController.php`
- `ApiAppAdoptionController` (Api) — index — `app/Http/Controllers/Api/ApiAppAdoptionController.php`
- `ApiAppBrandingController` (Api) — show — `app/Http/Controllers/Api/ApiAppBrandingController.php`
- `ApiAppIssuesController` (Api) — store, index — `app/Http/Controllers/Api/ApiAppIssuesController.php`
- `ApiAuditTrailController` (Api) — index, show — `app/Http/Controllers/Api/ApiAuditTrailController.php`
- `ApiBoardPackController` (Api) — show — `app/Http/Controllers/Api/ApiBoardPackController.php`
- `ApiDashboardController` (Api) — stats — `app/Http/Controllers/Api/ApiDashboardController.php`
- `ApiDeviceTokenController` (Api) — store, destroy — `app/Http/Controllers/Api/ApiDeviceTokenController.php`
- `ApiExpenseReportsController` (Api) — incomeStatement, summary — `app/Http/Controllers/Api/ApiExpenseReportsController.php`
- `ApiFeeStructureController` (Api) — index — `app/Http/Controllers/Api/ApiFeeStructureController.php`
- `ApiFinanceSummaryController` (Api) — show — `app/Http/Controllers/Api/ApiFinanceSummaryController.php`
- `ApiFinanceTransactionsController` (Api) — index, show — `app/Http/Controllers/Api/ApiFinanceTransactionsController.php`
- `ApiForcePasswordChangeController` (Api) — targets, requireChange — `app/Http/Controllers/Api/ApiForcePasswordChangeController.php`
- `ApiInventoryReportsController` (Api) — requirements, receipts — `app/Http/Controllers/Api/ApiInventoryReportsController.php`
- `ApiInvoiceController` (Api) — index, show — `app/Http/Controllers/Api/ApiInvoiceController.php`
- `ApiKemisController` (Api) — options — `app/Http/Controllers/Api/ApiKemisController.php`
- `ApiMpesaPaymentController` (Api) — prompt, paymentLinkUrl — `app/Http/Controllers/Api/ApiMpesaPaymentController.php`
- `ApiNotificationPreferencesController` (Api) — show, update — `app/Http/Controllers/Api/ApiNotificationPreferencesController.php`
- `ApiOperationsSummaryController` (Api) — show — `app/Http/Controllers/Api/ApiOperationsSummaryController.php`
- `ApiParentIdentityGateController` (Api) — show, update — `app/Http/Controllers/Api/ApiParentIdentityGateController.php`
- `ApiParentTransportController` (Api) — options — `app/Http/Controllers/Api/ApiParentTransportController.php`
- `ApiPayrollRecordsController` (Api) — index, show — `app/Http/Controllers/Api/ApiPayrollRecordsController.php`
- `ApiPayslipController` (Api) — download — `app/Http/Controllers/Api/ApiPayslipController.php`
- `ApiReportCardController` (Api) — index, show — `app/Http/Controllers/Api/ApiReportCardController.php`
- `ApiSchoolResolveController` (Api) — resolve — `app/Http/Controllers/Api/ApiSchoolResolveController.php`
- `ApiSearchController` (Api) — index, suggest — `app/Http/Controllers/Api/ApiSearchController.php`
- `ApiStaffPerformanceController` (Api) — index, show — `app/Http/Controllers/Api/ApiStaffPerformanceController.php`
- `ApiStaffTrainingController` (Api) — index, show — `app/Http/Controllers/Api/ApiStaffTrainingController.php`
- `ApiStudentAssessmentController` (Api) — assessmentHistory, academicSummary — `app/Http/Controllers/Api/ApiStudentAssessmentController.php`
- `ApiStudentStatementController` (Api) — show — `app/Http/Controllers/Api/ApiStudentStatementController.php`
- `ApiWeeklyReportsController` (Api) — index, show — `app/Http/Controllers/Api/ApiWeeklyReportsController.php`
- `requirements` (Api) — show — `app/Http/Controllers/Api/ApiParentRequirementsController.php`
- `ResolvesDocumentStorage` (Api/Concerns) — no public methods — `app/Http/Controllers/Api/Concerns/ResolvesDocumentStorage.php`
- `EventRegistrationApiController` (Api/Website) — register — `app/Http/Controllers/Api/Website/EventRegistrationApiController.php`
- `NewsletterApiController` (Api/Website) — subscribe — `app/Http/Controllers/Api/Website/NewsletterApiController.php`
- `SchoolAssistantApiController` (Api/Website) — chat — `app/Http/Controllers/Api/Website/SchoolAssistantApiController.php`
- `StudentShowcaseApiController` (Api/Website) — index — `app/Http/Controllers/Api/Website/StudentShowcaseApiController.php`
- `WebsiteAnalyticsApiController` (Api/Website) — trackView, trackEvent — `app/Http/Controllers/Api/Website/WebsiteAnalyticsApiController.php`
- `WebsiteBrandApiController` (Api/Website) — index — `app/Http/Controllers/Api/Website/WebsiteBrandApiController.php`
- `WebsiteMediaApiController` (Api/Website) — albums, virtualTour — `app/Http/Controllers/Api/Website/WebsiteMediaApiController.php`
- `WebsiteSeoController` (Api/Website) — sitemap, robots — `app/Http/Controllers/Api/Website/WebsiteSeoController.php`
- `ChangePasswordController` (Auth) — show, update — `app/Http/Controllers/Auth/ChangePasswordController.php`
- `DocumentSettingsController` (Finance) — index, update — `app/Http/Controllers/Finance/DocumentSettingsController.php`
- `ExpenseApprovalController` (Finance) — store — `app/Http/Controllers/Finance/ExpenseApprovalController.php`
- `InvoiceAdjustmentController` (Finance) — importForm, import — `app/Http/Controllers/Finance/InvoiceAdjustmentController.php`
- `ReceiptController` (Finance) — index, show — `app/Http/Controllers/Finance/ReceiptController.php`
- `SiblingBalanceTransferController` (Finance) — store — `app/Http/Controllers/Finance/SiblingBalanceTransferController.php`
- `HRAnalyticsController` (Hr) — index — `app/Http/Controllers/Hr/HRAnalyticsController.php`
- `PublicStaffRegistrationController` (Hr) — show, store — `app/Http/Controllers/Hr/PublicStaffRegistrationController.php`
- `StaffProfileController` (Hr) — show, update — `app/Http/Controllers/Hr/StaffProfileController.php`
- `AcademicYearTermsController` (Inventory) — index — `app/Http/Controllers/Inventory/AcademicYearTermsController.php`
- `InventoryReceiptsReportController` (Inventory) — index, csv — `app/Http/Controllers/Inventory/InventoryReceiptsReportController.php`
- `RequirementsReportController` (Inventory) — index, csv — `app/Http/Controllers/Inventory/RequirementsReportController.php`
- `HeatmapController` (Reports) — show — `app/Http/Controllers/Reports/HeatmapController.php`
- `PhoneNormalizationReportController` (Reports) — index — `app/Http/Controllers/Reports/PhoneNormalizationReportController.php`
- `ParentCredentialsController` (Students) — reset, requirePasswordChange — `app/Http/Controllers/Students/ParentCredentialsController.php`
- `StudentDuplicateReportController` (Students) — index, check — `app/Http/Controllers/Students/StudentDuplicateReportController.php`
- `SwimmingSettingsController` (Swimming) — index, update — `app/Http/Controllers/Swimming/SwimmingSettingsController.php`
- `FeeClearanceController` (Teacher) — index — `app/Http/Controllers/Teacher/FeeClearanceController.php`
- `StudentsController` (Teacher) — index, show — `app/Http/Controllers/Teacher/StudentsController.php`
- `ForcePasswordChangeController` (Users) — index, store — `app/Http/Controllers/Users/ForcePasswordChangeController.php`
- `WebAuthnLoginController` (WebAuthn) — options, login — `app/Http/Controllers/WebAuthn/WebAuthnLoginController.php`
- `WebAuthnRegisterController` (WebAuthn) — options, register — `app/Http/Controllers/WebAuthn/WebAuthnRegisterController.php`
- `AiContentController` (Website) — index, generate — `app/Http/Controllers/Website/AiContentController.php`
- `BlogCategoryController` (Website) — index, store — `app/Http/Controllers/Website/BlogCategoryController.php`
- `BrandIntelligenceController` (Website) — index — `app/Http/Controllers/Website/BrandIntelligenceController.php`
- `CampaignController` (Website) — index, store — `app/Http/Controllers/Website/CampaignController.php`
- `ContentAssistantController` (Website) — prompt — `app/Http/Controllers/Website/ContentAssistantController.php`
- `MediaAlbumController` (Website) — index, store — `app/Http/Controllers/Website/MediaAlbumController.php`
- `MenuController` (Website) — index, store — `app/Http/Controllers/Website/MenuController.php`
- `NewsletterController` (Website) — index — `app/Http/Controllers/Website/NewsletterController.php`
- `ReusableBlockController` (Website) — index, store — `app/Http/Controllers/Website/ReusableBlockController.php`
- `SchoolMealController` (Website) — index, store — `app/Http/Controllers/Website/SchoolMealController.php`
- `VirtualTourController` (Website) — index, store — `app/Http/Controllers/Website/VirtualTourController.php`
- `WebsiteAnalyticsController` (Website) — index — `app/Http/Controllers/Website/WebsiteAnalyticsController.php`
- `WebsiteSettingController` (Website) — edit, update — `app/Http/Controllers/Website/WebsiteSettingController.php`
- `ActivityLogController` (root) — index, show — `app/Http/Controllers/ActivityLogController.php`
- `AppDownloadController` (root) — playStore, apk — `app/Http/Controllers/AppDownloadController.php`
- `AppOpsController` (root) — adoption, issues — `app/Http/Controllers/AppOpsController.php`
- `CommunicationDocumentController` (root) — send — `app/Http/Controllers/CommunicationDocumentController.php`
- `CommunicationNoteController` (root) — create, printNotes — `app/Http/Controllers/CommunicationNoteController.php`
- `DirectoryExportController` (root) — exportStudents, exportStaff — `app/Http/Controllers/DirectoryExportController.php`
- `FeeReminderAutomationController` (root) — edit, update — `app/Http/Controllers/FeeReminderAutomationController.php`
- `FileDownloadController` (root) — show — `app/Http/Controllers/FileDownloadController.php`
- `HomeController` (root) — index — `app/Http/Controllers/HomeController.php`
- `MediaController` (root) — signedRedirect — `app/Http/Controllers/MediaController.php`
- `SearchController` (root) — suggest — `app/Http/Controllers/SearchController.php`
- `SmsLogController` (root) — index, store — `app/Http/Controllers/SmsLogController.php`
- `SocialAuthController` (root) — redirectToGoogle, handleGoogleCallback — `app/Http/Controllers/SocialAuthController.php`
- `StudentDropOffController` (root) — index, update — `app/Http/Controllers/StudentDropOffController.php`
- `TransportController` (root) — index, assignDriver — `app/Http/Controllers/TransportController.php`
- `WhatsAppWebhookController` (root) — handleMeta, handle — `app/Http/Controllers/WhatsAppWebhookController.php`

## 12. Largest controllers (god-class risk)

| Controller | Methods | Lines | Folder |
|---|---:|---:|---|
| `BankStatementController` | 42 | 6766 | Finance |
| `PaymentController` | 33 | 3740 | Finance |
| `MpesaPaymentController` | 31 | 2955 | Finance |
| `StudentController` | 30 | 2894 | Students |
| `CommunicationController` | 28 | 2153 | root |
| `TimetableController` | 22 | 1074 | Academics |
| `InvoiceController` | 20 | 1024 | Finance |
| `DiscountController` | 18 | 702 | Finance |
| `StaffController` | 17 | 1319 | Hr |
| `LessonPlanController` | 16 | 725 | Academics |
| `ReportCardController` | 15 | 608 | Academics |
| `AcademicConfigController` | 14 | 661 | Academics |
| `ApiJengaController` | 14 | 322 | Api |
| `AuthApiController` | 14 | 715 | Api |
| `AttendanceController` | 14 | 1017 | Attendance |
| `ExpenseStatementController` | 14 | 508 | Finance |
| `TransportFeeController` | 14 | 966 | Finance |
| `ExamController` | 13 | 944 | Academics |
| `SchemeOfWorkController` | 13 | 544 | Academics |
| `WebsiteApiController` | 13 | 234 | Api/Website |
| `SettingController` | 13 | 382 | Settings |
| `TripController` | 13 | 867 | root |
| `ExamMarkController` | 12 | 843 | Academics |
| `SubjectController` | 12 | 552 | Academics |
| `ApiCommunicationController` | 12 | 513 | Api |

## 13. Duplicate controller names across web vs API vs role portals

Same domain, multiple controllers. This is the main **method-level repetition** pattern.

- **Payment**: `ApiPaymentController` (Api, 3 methods), `PaymentController` (Finance, 33 methods), `PaymentController` (Pos, 3 methods)
- **Classroom**: `ClassroomController` (Academics, 6 methods), `ApiClassroomController` (Api, 3 methods)
- **ExamReports**: `ExamReportsController` (Academics, 7 methods), `ApiExamReportsController` (Api, 9 methods)
- **Homework**: `HomeworkController` (Academics, 7 methods), `ApiHomeworkController` (Api, 8 methods)
- **ReportCard**: `ReportCardController` (Academics, 15 methods), `ApiReportCardController` (Api, 2 methods)
- **TeacherAssignment**: `TeacherAssignmentController` (Academics, 3 methods), `ApiTeacherAssignmentController` (Api, 3 methods)
- **Timetable**: `TimetableController` (Academics, 22 methods), `ApiTimetableController` (Api, 4 methods)
- **Attendance**: `ApiAttendanceController` (Api, 8 methods), `AttendanceController` (Attendance, 14 methods)
- **Communication**: `ApiCommunicationController` (Api, 12 methods), `CommunicationController` (root, 28 methods)
- **Concern**: `ApiConcernController` (Api, 5 methods), `ConcernController` (Operations, 5 methods)
- **Dashboard**: `ApiDashboardController` (Api, 1 methods), `DashboardController` (root, 7 methods)
- **Diary**: `ApiDiaryController` (Api, 3 methods), `DiaryController` (ParentPortal, 3 methods)
- **FeeClearance**: `ApiFeeClearanceController` (Api, 3 methods), `FeeClearanceController` (Teacher, 1 methods)
- **FeeStructure**: `ApiFeeStructureController` (Api, 1 methods), `FeeStructureController` (Finance, 9 methods)
- **ForcePasswordChange**: `ApiForcePasswordChangeController` (Api, 2 methods), `ForcePasswordChangeController` (Users, 2 methods)
- **Invoice**: `ApiInvoiceController` (Api, 2 methods), `InvoiceController` (Finance, 20 methods)
- **LeaveRequest**: `ApiLeaveRequestController` (Api, 8 methods), `LeaveRequestController` (Hr, 7 methods)
- **MpesaPayment**: `ApiMpesaPaymentController` (Api, 2 methods), `MpesaPaymentController` (Finance, 31 methods)
- **ParentCredentials**: `ApiParentCredentialsController` (Api, 3 methods), `ParentCredentialsController` (Students, 2 methods)
- **Payslip**: `ApiPayslipController` (Api, 1 methods), `PayslipController` (Hr, 4 methods)
- **Requisition**: `ApiRequisitionController` (Api, 5 methods), `RequisitionController` (Inventory, 7 methods)
- **Search**: `ApiSearchController` (Api, 2 methods), `SearchController` (root, 1 methods)
- **SeniorTeacher**: `ApiSeniorTeacherController` (Api, 5 methods), `SeniorTeacherController` (SeniorTeacher, 6 methods)
- **StaffAdvance**: `ApiStaffAdvanceController` (Api, 5 methods), `StaffAdvanceController` (Hr, 9 methods)
- **Staff**: `ApiStaffController` (Api, 12 methods), `StaffController` (Hr, 17 methods)
- **StudentAssignment**: `ApiStudentAssignmentController` (Api, 6 methods), `StudentAssignmentController` (root, 11 methods)
- **Student**: `ApiStudentController` (Api, 7 methods), `StudentController` (Students, 30 methods)
- **StudentStatement**: `ApiStudentStatementController` (Api, 1 methods), `StudentStatementController` (Finance, 10 methods)
- **TeacherRequirements**: `ApiTeacherRequirementsController` (Api, 3 methods), `TeacherRequirementsController` (Pos, 3 methods)
- **TransportSpecialAssignment**: `ApiTransportSpecialAssignmentController` (Api, 4 methods), `TransportSpecialAssignmentController` (Transport, 6 methods)
- **Vehicle**: `ApiVehicleController` (Api, 5 methods), `VehicleController` (root, 6 methods)
- **Discount**: `DiscountController` (Finance, 18 methods), `DiscountController` (Pos, 6 methods)
- **Transport**: `TransportController` (Teacher, 3 methods), `TransportController` (root, 2 methods)

## 14. Policies vs controllers (authorization gap)

Only **8** policies exist for **371** controllers. Authorization is mostly middleware/Spatie checks in controllers, not model policies. Form requests: **17** — most controllers validate inline.

## 15. Document redundancy

169 markdown files. Heavy duplication across:

- `docs/system-audit/*` (previous ERP audit, counts already stale vs this live pass)
- `docs/execution/*` sprint reports
- `docs/ui/*` + `docs/design-system-v3/*` overlapping UI specs
- `docs/mobile-app-*` + `mobile-app/README.md` + `docs/USERS_APP_*` overlapping mobile audits
- Root-level `docs/SYSTEM_DOCUMENTATION.md` (Jan 2026) vs this live catalog

Keep one live inventory (this folder’s 11–15) and treat sprint reports as historical.
