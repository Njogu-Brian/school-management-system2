# School Management System — Application Functions

This document describes what the application **does** at the level of **HTTP routes**, **JSON API endpoints**, **Artisan commands**, **domain services**, and the **Expo mobile app**. It is generated from the Laravel routes and code layout in this repository.

**Scope note.** The PHP codebase contains hundreds of controller classes and many thousands of methods (including helpers and framework lifecycle methods). Listing *every* PHP function line-by-line would be longer than the source itself. Below, **every user-facing route action** is accounted for (either explicitly or via the standard Laravel `Route::resource` meanings in §3). For internal helpers, see the cited controller and service files.

**Convention — `Route::resource`:** Laravel expands a resource to these actions where registered:

| HTTP / method   | Typical purpose        |
|-----------------|------------------------|
| `GET index`     | List records           |
| `GET create`    | Show create form       |
| `POST store`    | Save new record        |
| `GET show`      | View one record        |
| `GET edit`      | Show edit form         |
| `PUT/PATCH update` | Save changes       |
| `DELETE destroy`| Delete record          |

If a resource uses `->except([...])` or `->only([...])`, only those actions exist.

---

## 1. Authentication and session (web)

| Route / area | Controller action | Purpose |
|--------------|-------------------|---------|
| `GET /` | Closure | Redirect to login |
| `GET/POST /login` | `AuthController::showLoginForm`, `login` | Staff login |
| Password reset (email link) | `AuthController::showLinkRequestForm`, `sendResetLinkEmail`, `showResetForm`, `reset` | Classic reset flow |
| `GET/POST /password/reset-otp` | `AuthController::showOTPResetForm`, `resetWithOTP` | OTP-based password reset |
| `POST /logout` | `AuthController::logout` | End session |

---

## 2. Public and token-based web access (no staff session)

| Path pattern | Action | Purpose |
|--------------|--------|---------|
| `GET /students/search` | `StudentController::search` | Public student lookup |
| `GET receipt/{token}` | `Finance\PaymentController::publicViewReceipt` | View receipt by token |
| `GET receipt/{token}/pay-now` | `Finance\PaymentController::createPayNowFromReceiptToken` | Start payment from receipt |
| `GET my-receipts/{token}` | `Finance\PaymentController::myReceipts` | Family receipt list by token |
| `GET invoice/{hash}` | `Finance\InvoiceController::publicView` | Public invoice |
| `GET statement/{hash}` | `Finance\StudentStatementController::publicView` | Public fee statement |
| `GET payment-plan/{hash}` | `Finance\FeePaymentPlanController::publicView` | Public payment plan |
| `POST /webhooks/sms/dlr` | `CommunicationController::smsDeliveryReport` | SMS delivery report webhook |
| `POST /webhooks/whatsapp/wasender` | `WhatsAppWebhookController::handle` | WhatsApp (Wasender) webhook |
| `GET/POST /webhooks/payment/mpesa` | `PaymentWebhookController::handleMpesa` | M-Pesa STK / generic webhook |
| `POST .../mpesa/c2b` and `POST .../c2b` | `Finance\MpesaPaymentController::handleC2BCallback` | M-Pesa C2B callback |
| `POST .../stripe`, `.../paypal` | `PaymentWebhookController::handleStripe`, `handlePaypal` | Stripe / PayPal webhooks |
| `GET/POST /pay/{identifier}` | `MpesaPaymentController::showPaymentPage`, `processLinkPayment` | Pay-by-link page |
| `GET /pay/waiting/{transaction}` | `MpesaPaymentController::showPublicWaiting` | Wait for STK result |
| `GET/POST /pay/transaction/...` | `getTransactionStatus`, `cancelTransaction` | Poll / cancel transaction |
| `GET/POST /invoice/.../pay` | `showInvoicePayment`, `processInvoicePayment` | Invoice M-Pesa pay |
| `GET/POST /family-update/{token}` | `FamilyUpdateController::publicForm`, `submit` | Guardian self-service update |
| `GET/POST /online-admissions/apply` | `OnlineAdmissionController::showPublicForm`, `storePublicApplication` | Public admission application |
| `GET/POST /shop/{token}/...` | `Pos\PublicShopController`, `Pos\PaymentController` | Public POS shop: browse cart, checkout, pay, verify |

---

## 3. Authenticated web application (`routes/web.php`)

Role gates are applied per group (e.g. `Super Admin`, `Admin`, `Secretary`, `Teacher`, `Finance Officer`, `Driver`, `Parent`, etc.). Below summarizes **functional areas** and **non-resource** routes. Resource routes use the standard CRUD table in the introduction unless noted.

### 3.1 Files, profile, home routing

- **`GET /admin/files/{model}/{id}/{field}`** — `FileDownloadController::show` — Secure download for uploaded fields (restricted roles).
- **`GET/POST /my/profile`** — `StaffProfileController::show`, `update` — Staff self-service profile.
- **`GET /profile`** — Redirect alias to `/my/profile`.
- **`GET /home`** — Closure — Role-based redirect to the correct dashboard.
- **Dashboards** — `DashboardController`: `adminDashboard`, `teacherDashboard`, `supervisorDashboard`, `parentDashboard`, `studentDashboard`, `financeDashboard`, `transportDashboard`.
- **`GET /gallery`** — `GalleryController::index` — View gallery.

### 3.2 Attendance

- **Marking & records** — `AttendanceController`: `markForm`, `mark`, `records`, `atRiskStudents`, `consecutiveAbsences`, `studentAnalytics`, `updateConsecutiveCounts`, `notifyConsecutiveAbsences`, `edit`, `update`, `unmark`.
- **Notifications** — `AttendanceNotificationController`: full CRUD plus `notifyForm`, `sendNotify`.
- **Reason codes** — `AttendanceReasonCodeController`: full CRUD.

### 3.3 Swimming module

- **Attendance** — `SwimmingAttendanceController`: `create`, `store`, `index`, `retryPayment`, `bulkRetryPayments`, `sendPaymentReminders`.
- **Wallets** — `SwimmingWalletController`: `index`, `show`, `adjust`, `fixOrphanedCredits`, `creditFromOptionalFees`, `processUnpaidAttendance`, `unallocateSwimmingPayments`.
- **Payments** — `SwimmingPaymentController`: `create`, `store`, `getSiblings`, `sendBalanceCommunication`, `bulkSendBalanceCommunications`, `getBulkSendProgress`.
- **Reports** — `SwimmingReportController`: `unpaidSessions`, `walletBalances`, `revenueVsSessions`.
- **Settings** (admin) — `SwimmingSettingsController`: `index`, `update`.

### 3.4 Academics (`prefix academics`, role-gated)

**Resources (standard CRUD unless `except`/`only`):**

- `classrooms` — except `show`.
- `streams` — except `show`; plus `StreamController::assignTeachers`.
- `subject_groups` — except `show`.
- `subjects` — full; plus `teacherAssignments`, `saveTeacherAssignments`, `generateCBCSubjects`, `assignToClassrooms`, `updateLessonsPerWeek`.
- `AssignTeachersController` — `index`, `assignToClassroom`.
- **Promotions** — `StudentPromotionController`: `index`, `alumni`, `show`, `promote`, `promoteAll`, `demote`.
- **Exams** — `ExamResultController::index`; `ExamPublishingController::publish`; `ExamController::timetable` + resource `exams`; resource `exam-grades`.
- **Exam schedules** — nested under `exams/{exam}/schedules`: `index`, `store`, `update`, `destroy`.
- **Schemes of work** — resource + `approve`, `generate`, `exportPdf`, `exportExcel`, `bulkExport`, `getStrands`.
- **Lesson plans** — resource + `approve`, `exportPdf`, `exportExcel`, `assignHomeworkForm`, `assignHomework`, `getSubstrands`.
- **Curriculum designs** — resource + `review`, `reprocess`, `progress`.
- **Curriculum assistant (AI)** — `CurriculumAssistantController::generate`, `chat`.
- **Homework diary** — resource + `submitForm`, `submit`, `markForm`, `mark`, `updateSubmission`.
- **Learning areas** — resource + `getStrands`.
- **Competencies** — resource + `getBySubstrand`, `getByStrand`.
- **CBC strands / substrands** — resources (admin-only group) + `CBCStrandController::substrands`.
- **Portfolio assessments** — resource.
- **Timetable** — `TimetableController`: `index`, `classroom`, `edit`, `teacher`, `generate`, `save`, `duplicate`, `updatePeriod`, `checkConflicts`.
- **Extra-curricular (academics)** — resource + `assignStudents`; alias routes under `activities/*`.
- **Homework** — resource `homework`.
- **Student diaries** — `StudentDiaryController`: `index`, `show`, `storeEntry`, `bulkStore`.
- **Parent diaries** — `ParentPortal\DiaryController`: `index`, `show`, `storeEntry` (Parent role).
- **Term assessment** — `ReportCardController::termAssessment`.
- **Report cards** — `generateForm`, `generate`; resource; `destroy`; `publish`; `exportPdf`; `publicView` (token URL `r/{token}`).
- **Report card skills** — nested CRUD under `report_cards/{report_card}/skills`.
- **Behaviours** — resources `behaviours`, `student-behaviours`.
- **Skills grading** — `StudentSkillGradeController`: `index`, `store`.

**Exam types** (separate group): `ExamTypeController` — `index`, `store`, `update`, `destroy` only.

**Exam analytics & assessments** (later group): `ExamAnalyticsController` — `index`, `classroomPerformance`; `AssessmentController` — `index`, `create`, `store`.

### 3.5 Transport

- **Core** — `TransportController::index`, `assignDriver`.
- **Resources** — `vehicles` (no `show`), `trips`, `dropoffpoints`, `student-assignments` (+ `bulkAssign`, `bulkAssignStore`).
- **Drop-off import** — `DropOffPointController`: `importForm`, `import`, `template`.
- **Trip attendance** — `Transport\TripAttendanceController`: `create`, `store`, `index`.
- **Driver change requests** — resource + `approve`, `reject`.
- **Special assignments** — resource + `approve`, `reject`, `cancel`.
- **Import** — `TransportImportController`: `importForm`, `preview`, `import`, `showLog`, `downloadTemplate`.
- **Daily list** — `DailyTransportListController`: `index`, `downloadExcel`, `printList`, `printVehicle`.
- **Driver portal** — `Driver\DriverController`: `index`, `showTrip`, `transportSheet` (and per-trip).

### 3.6 Staff / HR (`prefix staff` — admin/secretary)

- **Staff CRUD** — `StaffController`: `index`, `create`, `store`, `show`, `edit`, `update`, `archive`, `restore`, `bulkAssignSupervisor`, `resendCredentials`, `resetPassword`.
- **Bulk upload** — `showUploadForm`, `uploadParse`, `uploadCommit`, `handleUpload`, `template`.
- **Leave types** — `LeaveTypeController` CRUD.
- **Leave requests** — `LeaveRequestController`: `index`, `create`, `store`, `show`, `approve`, `reject`, `cancel`.
- **Leave balances** — `StaffLeaveBalanceController`: `index`, `create`, `store`, `show`, `update`.
- **Staff attendance** — `StaffAttendanceController`: `index`, `mark`, `bulkMark`, `report`.
- **Staff documents** — `StaffDocumentController`: `index`, `create`, `store`, `show`, `download`, `destroy`.

### 3.7 HR portal (`prefix hr`)

- Redirect and **roles/permissions** — `RolePermissionController`: `accessAndLookups`, `listRoles`, `index`, `update`.
- **Reports** — `StaffReportController`: `index`, `exportDirectory`, `departmentReport`, `categoryReport`, `newHiresReport`, `terminationsReport`, `turnoverAnalysis`.
- **Analytics** — `HRAnalyticsController::index`.
- **Payroll** — `SalaryStructureController` resource; `PayrollPeriodController` resource + `process`, `lock`; `PayrollRecordController` resource; `PayslipController::show`, `download`; `StaffAdvanceController` resource + `approve`, `recordRepayment`; `DeductionTypeController` resource; `CustomDeductionController` resource + `suspend`, `activate`.

### 3.8 Supervisor

- **Leave** — `LeaveRequestController`: `index`, `show`, `approve`, `reject` (subordinates).
- **Attendance** — `StaffAttendanceController`: `index`, `report`.

### 3.9 Lookups and profile change requests

- **`GET /lookups`** — `LookupController::index`.
- **AJAX lookups** — `store`/`delete` for category, department, job title, custom field.
- **`hr/profile-requests`** — `ProfileChangeController`: `index`, `approveAll`, `show`, `approve`, `reject`.

### 3.10 Settings and academic calendar

- **`settings/`** — `SettingController::index`; gallery `upload`, `destroy`, `reorder`; `updateBranding`, `updateSettings`, `updateRegional`, `updateSystem`, `updateIdSettings`, `updateFeatures`, `updateModules`.
- **School days** — `SchoolDayController`: `index`, `generateHolidays`, `store`, `destroy`.
- **Academic years/terms/holidays** — `AcademicConfigController`: year and term CRUD; `termHolidays`, `storeTermHoliday`, `updateTermHoliday`.
- **Placeholders** — `PlaceholderController` CRUD for communication placeholders.

### 3.11 Senior teacher assignments (admin)

- `SeniorTeacherAssignmentController`: `index`, `edit`, `updateCampus`.

### 3.12 Reports (system / weekly / heatmaps)

- **Phone normalization** — `PhoneNormalizationReportController::index`.
- **Heatmaps** — `HeatmapController::show` per campus.
- **Weekly reports** — `ClassReportController`, `SubjectReportController`, `StaffWeeklyController`, `StudentFollowupController`, `OperationsFacilityController` — each: `index`, `create`, `store`.

### 3.13 Students, families, admissions (authenticated)

- **Bulk operations** — `StudentController`: `bulkForm`, `bulkParse`, `bulkImport`, `bulkTemplate`, `updateImportForm`, `updateImportTemplate`, `updateImportPreview`, `updateImportProcess`, `bulkAssignCategories`, `processBulkCategoryAssignment`, `bulkAssignStreams`, `processBulkStreamAssignment`, `archived`, `alumniAndArchived`, `parentsContact`, `detailsAjax`, `export`, `bulkAssign`, `bulkArchive`, `bulkRestore`, `getStreams`, `search` (under `/api/students/search`), resource (no `destroy` in resource — use archive), `archive`, `restore`.
- **Family update admin** — `FamilyUpdateController`: `adminIndex`, `resetAll`, `reset`, `showLink`.
- **Student categories** — `StudentCategoryController` resource.
- **Per-student records** — `MedicalRecordController`, `DisciplinaryRecordController`, `ExtracurricularActivityController`, `AcademicHistoryController` — full CRUD under `students/{student}/...`.
- **Parent info** — `ParentInfoController` resource (except `show`).
- **Families** — `FamilyController`: `index`, `populatePreview`, `populateFromPreview`, `bulkDestroy`, `link`, `linkStudents`, `manage`, `update`, `attachMember`, `detachMember`, `destroy`.
- **Online admissions (admin)** — `OnlineAdmissionController`: `index`, `show`, `approve`, `reject`, `addToWaitlist`, `transferFromWaitlist`, `updateStatus`, `destroy`.

### 3.14 Communication (`prefix communication`)

- **Send** — `CommunicationController`: email/SMS/WhatsApp create + submit; `preview`; bulk progress endpoints; `retryFailedWhatsApp`; `CommunicationDocumentController::send`.
- **Printed notes** — `CommunicationNoteController`: `create`, `printNotes`.
- **Wasender** — `WasenderSessionController` (also under communication): list, create, connect, restart, settings, delete.
- **Delivery** — `deliveryReportsIndex`, `deliveryReport`; `smsDlrUpload`, `smsDlrProcess`.
- **Logs** — `logs`, `logsScheduled`, `conversations`.
- **Pending jobs** — `pendingJobs`, `cancelJob`, `sendJobImmediately`.
- **Announcements** — `CommunicationAnnouncementController` resource (except `show`).
- **Templates** — `CommunicationTemplateController` resource (except `show`).

### 3.15 Finance (`prefix finance`)

High surface area; main buckets:

- **Voteheads** — CRUD (no show) + import/template/download.
- **Fee structures** — `manage`, `save`, `replicateTo`, `replicateTerms`, import + template.
- **Legacy imports** — `LegacyFinanceImportController`: list, upload, show, edit history, revert, search student, rerun, destroy, update line.
- **Invoices** — index, generate, show, edit, update, reverse, print bulk/single, import, history, line updates, uniform line add/remove; **`InvoiceAdjustmentController`** import.
- **Optional fees** — class/student views, save, duplicate flows; **`OptionalFeeImportController`** preview/commit/history/reverse/template.
- **Transport fees** — index, bulk update, duplicate flows, import history, reverse, template.
- **Payments** — create, store, show, history, allocate, reverse, transfer, receipts (print/view/pdf), bulk operations, student info, failed communications, online initiate/verify, shared allocation updates, communication resend.
- **Bank accounts / payment methods** — resources.
- **Transaction fix audit** — index, export, show, reverse, bulk reverse.
- **Bank statements** — `BankStatementController`: listing, upload pipeline, show/edit/update/destroy, confirm/reject/split/share, PDF view/serve/download, bulk confirm/archive, auto-assign, reparse, archive/unarchive, history, allocate unallocated, swimming reclassify flows, link to existing payments, student balance JSON, etc.
- **Document settings** — `DocumentSettingsController`: index, update.
- **Student statements** — family and per-student views, print, export, legacy line update, manual entries.
- **Fee balances** — `FeeBalanceController`: index, export, PDF export, print.
- **Balance brought forward** — import preview/commit/reverse, per-student update/destroy/add, template.
- **Fees comparison import** — compare-only preview (no commit).
- **M-Pesa** — dashboard, STK prompt, links CRUD/send/cancel, transactions, waiting, C2B dashboard/list, register URLs; JSON helpers for status/cancel/latest C2B.
- **Credit/debit notes** — create/store/reverse + batch import.
- **Fee payment plans** — resource + student invoice helper + status update.
- **Fee concessions** — resource + approve/deactivate.
- **Fee reminders** — resource + send + automated send + scheduled fee communications (create/store/destroy/preview helpers).
- **Accountant dashboard** — `AccountantDashboardController`: index, settings, student history.
- **Posting** — `PostingController`: index, preview, commit, show, reverse, reverse-student.
- **Journals** — `JournalController`: index, create, store, get invoice voteheads, bulk form/import/template.
- **Discounts** — extensive: templates, allocate, allocations list, approve/reject, bulk sibling, bulk approve/reject, reverse, apply sibling, replicate, `show`.

### 3.16 Events, documents, backup, inventory, POS, library, hostel, logs

- **Events** — `EventCalendarController` resource + `api` (JSON for calendar).
- **Documents** — `DocumentManagementController` resource-like: index, create, store, show, download, preview, email, updateVersion, destroy.
- **Document templates** — `DocumentTemplateController` CRUD + preview + generate for student/staff.
- **Generated documents** — `GeneratedDocumentController`: index, show, download, destroy.
- **Backup & restore** — `BackupRestoreController`: index, create backup, download, restore, schedule update, purge all.
- **Inventory** — items resource + stock adjust; requirement types CRUD; requirement templates resource; student requirements collect workflow + AJAX loaders; requisitions workflow (create, approve, fulfill, reject).
- **POS** — products + bulk import/template; variants; orders (show, status, cancel, fulfill); discounts resource; public links + regenerate token; teacher requirements; uniforms and backorders; fulfill backorder on order item.
- **Library** — books resource; library cards (create, renew); borrowings (borrow, return, renew).
- **Hostel** — hostels resource; allocations (create, show, deallocate).
- **Activity logs** — `ActivityLogController`: index, show.
- **System logs** — `SystemLogController`: index, clear, download.

### 3.17 Authenticated JSON helpers (web middleware, not Sanctum)

- **`GET /api/finance/students/{student}`** — `MpesaPaymentController::getStudentData`
- **`GET /api/finance/students/{student}/invoices`** — `MpesaPaymentController::getStudentInvoices`

---

## 4. Teacher routes (`routes/teacher.php`)

Middleware: `auth` + teacher-capable roles.

- **Dashboard** — `DashboardController::teacherDashboard` (duplicate path allowed).
- **Attendance** — `markForm`, `mark`, `records`.
- **Exam marks** — `ExamMarkController`: index, bulk form/edit/view/store, single edit/update.
- **Report cards** — index, show, skills CRUD, remarks save.
- **Homework** — resource.
- **Student behaviours** — index, create, store, destroy.
- **My students** — `Teacher\StudentsController`: index, show.
- **Teacher transport** — `Teacher\TransportController`: index, show, transport sheet print.
- **Salary** — `Teacher\SalaryController`: index, payslip view/download.
- **Advances** — `AdvanceRequestController`: index, create, store.
- **Leaves** — `LeaveController`: index, create, store, show, cancel.
- **Timetable** — index, classroom view, redirect to personal timetable.
- **Announcements** — `CommunicationAnnouncementController::index`.

---

## 5. Senior teacher routes (`routes/senior_teacher.php`)

Middleware: `auth` + `Super Admin|Admin|Senior Teacher`.

- **Dashboard** — `SeniorTeacherController::dashboard`.
- **Supervision** — `supervisedClassrooms`, `supervisedStaff`, `students`, `studentShow`, `feeBalances`.
- **Attendance, exam marks, report cards** — same pattern as teacher file (shared controllers).
- **Timetable, salary, advances, leaves, announcements** — same as teacher naming with `senior_teacher.*` route names.

---

## 6. JSON API (`routes/api.php`, Sanctum)

Base URL prefix `/api` (Laravel default). **`POST /api/login`** is public; the rest use `auth:sanctum`.

| Method / path | Controller::method | Purpose |
|---------------|---------------------|---------|
| `POST /login` | `AuthApiController::login` | Issue token |
| `GET /user` | `AuthApiController::user` | Current user payload |
| `POST /logout` | `AuthApiController::logout` | Revoke token |
| `POST /device-tokens` | `ApiDeviceTokenController::store` | Register push device |
| `POST /device-tokens/revoke` | `ApiDeviceTokenController::destroy` | Remove device token |
| `GET /dashboard/stats` | `ApiDashboardController::stats` | Dashboard KPIs |
| `GET /student-categories` | `ApiStudentWriteController::categories` | Categories list |
| `GET/POST /students` | `ApiStudentController::index`, `ApiStudentWriteController::store` | List / create students |
| `GET /students/{id}/stats` | `ApiStudentController::stats` | Student stats |
| `GET /students/{id}/attendance-calendar` | `ApiStudentController::attendanceCalendar` | Calendar data |
| `GET /students/{id}/statement` | `ApiStudentStatementController::show` | Fee statement JSON |
| `GET /students/{id}/profile-update-link` | `ApiStudentWriteController::profileUpdateLink` | Magic link for profile |
| `POST /students/{id}/update` | `ApiStudentWriteController::update` | Update student |
| `POST /students/{id}/mpesa/prompt` | `ApiMpesaPaymentController::prompt` | STK push |
| `GET /students/{id}/mpesa/payment-link` | `ApiMpesaPaymentController::paymentLinkUrl` | Payment URL |
| `GET /students/{id}` | `ApiStudentController::show` | Student detail |
| `GET /invoices`, `GET /invoices/{id}` | `ApiInvoiceController` | Invoices |
| `GET/POST /payments`, `GET /payments/{id}` | `ApiPaymentController` | Payments |
| `GET /finance/transactions` | `ApiFinanceTransactionsController::index` | Transaction list |
| `POST /finance/transactions/mark-swimming` | `BankStatementController::bulkMarkAsSwimming` | Tag swimming |
| `POST /finance/transactions/{id}/confirm` | `BankStatementController::confirm` | Confirm allocation |
| `POST .../reject` | `BankStatementController::reject` | Reject line |
| `POST .../share` | `BankStatementController::share` | Split/share payment |
| `GET /finance/transactions/{id}` | `ApiFinanceTransactionsController::show` | Transaction detail |
| `GET /classes`, `GET /classes/{id}/streams` | `ApiClassroomController` | Classes / streams |
| `GET /staff`, `GET /staff/{id}`, `PUT /staff/{id}`, `POST /staff/{id}/photo` | `ApiStaffController` | Staff directory & update |
| `GET /payroll-records` | `ApiPayrollRecordsController::index` | Payroll list |
| `GET /routes`, `GET /routes/{id}` | `ApiRouteController` | Transport routes |
| `GET /leave-types`, `GET/POST /leave-requests`, approve/reject | `ApiLeaveRequestController` | Leave workflow |
| `GET /library/books` | `ApiLibraryController::index` | Books |
| `GET /announcements` | `ApiAnnouncementController::index` | Announcements |
| `GET /attendance/class`, `POST /attendance/mark` | `ApiAttendanceController` | Class attendance |
| `GET /exams`, `GET /exams/{id}`, marking options | `ApiAcademicsController` | Exams |
| `GET /marks`, `POST /exam-marks/batch` | `ApiAcademicsController` | Marks read/write |

---

## 7. Domain services (`app/Services`)

Services encapsulate business rules. Key public APIs (non-exhaustive; see each file for full signatures):

| Service | Main responsibilities |
|---------|------------------------|
| `AttendanceAnalyticsService` | Attendance %, at-risk, consecutive absences, trends |
| `AttendanceReportService` | Grouped records, summaries, exports |
| `ActivityBillingService` | Bill/unbill extracurricular activities |
| `ArchiveStudentService` / `RestoreStudentService` | Student archive/restore |
| `BankStatementParser` | PDF parse, match, create payments, splits, phone match |
| `CBCCurriculumGeneratorService` / `CurriculumParsingService` | CBC content and design parsing |
| `CommunicationService` | Orchestrate SMS/email/WhatsApp to recipients |
| `CommunicationHelperService` | Static recipient collection for campaigns (by class, balance, plans, etc.) |
| `DatabaseBackupService` | List/prune/paths for DB backups |
| `DiscountService` | Apply discounts to invoices, sibling rules |
| `DocumentGeneratorService` / `PDFExportService` | Generated documents and PDFs |
| `EmailService` | Send mail |
| `EmbeddingService` / `LLMService` / `PromptTemplateService` | AI / embeddings for curriculum assistant |
| `ExpoPushService` | Push notifications for announcements |
| `FamilyArchiveService` | Family consistency when students archived |
| `FeePostingService` | Fee posting preview/commit/reverse |
| `FeeStructureImportService` / `VoteheadImportService` | Imports |
| `FinancialAuditService` | Static audit log writers for reversals, shared allocation edits, archive/reject, transfers |
| `GoogleSheetsFeeSyncService` | Optional Google Sheets sync |
| `HostelService` | Allocate/deallocate/list rooms |
| `InvoiceService` | Static helpers: ensure invoice, BBF on invoice, recalc, allocate unallocated, update line amounts, apply discount |
| `JournalService` | Static `createAndApply` for manual journals |
| `LegacyFinanceImportService` / `LegacyPdfParser` / `LegacyStatementRecalcService` | Legacy statement import/recalc |
| `LibraryService` | Cards, borrow, return, renew, fines |
| `MpesaSmartMatchingService` | C2B matching and sibling splits |
| `NumberSeries` / `DocumentNumberService` / `ReceiptNumberService` | Numbering |
| `OtpService` | OTP generation/verification |
| `PaymentAllocationService` | Allocate payments to invoices/plans/siblings |
| `PaymentPlanComplianceService` / `PaymentPlanNotificationService` | Plan rules and parent notify |
| `PaymentService` | Online payment initiate/verify |
| `PhoneNumberService` / `PhoneNumberNormalizationLogger` | Phone normalization |
| `PosService` | Public shop cart and checkout |
| `PostingService` | Static fee posting `preview` / `commit` |
| `ReceiptService` | Receipt HTML/PDF data |
| `ReportCardBatchService` / `ReportGenerationService` | Batch report cards |
| `SchemeOfWorkAutoGenerationService` | Auto schemes and lesson plans |
| `SMSService` | SMS gateway, balance, OTP |
| `StudentAttendanceCalendarService` | Valid school days vs enrolment |
| `StudentBalanceService` | Balance calculations |
| `SwimmingAttendanceService` / `SwimmingWalletService` / `SwimmingTransactionService` | Swimming billing |
| `TermAssessmentService` | Static term assessment grid + exam averages |
| `TimetableService` / `TimetableOptimizationService` | Timetable build/optimize |
| `TransportAssignmentService` / `TransportFeeService` | Routes and transport fees |
| `UnifiedTransactionService` | Cross-type duplicate detection, unified listing |
| `UniformFeeService` | Uniform fee linkage |
| `WhatsAppService` | Wasender API session and messaging |

---

## 8. Artisan commands (`app/Console/Commands`)

Each command implements `handle()`; the **signature** is the CLI name:

| Signature (primary name) | Typical purpose |
|--------------------------|-----------------|
| `reports:generate` | Batch report cards |
| `finance:move-auto-assigned-to-collected` | Bank statement workflow |
| `finance:audit` | Financial audit |
| `finance:review-unallocated-payments` | Find unallocated cash |
| `phones:normalize` | Normalize phone numbers |
| `finance:review-duplicates` | Duplicate transaction report |
| `finance:mark-bank-duplicates` | Mark duplicates |
| `mpesa:test-credentials` | Test M-Pesa API |
| `payment-plans:update-statuses` | Refresh plan statuses |
| `finance:fix-equity-refs-by-match` / `fix-equity-reference-numbers` / `fix-equity-refs-from-reparse` | Equity ref repairs |
| `families:ensure-receipt-links` | Data fix for receipt links |
| `students:consolidate-sibling-parent-records` | Parent record consolidation |
| `students:unassign-streams-from-classes-without-streams` | Data cleanup |
| `finance:reset-links` | Reset finance links |
| `mpesa:register-c2b-urls` | Register C2B URLs |
| `finance:statement-balance-audit` | Statement balance check |
| `finance:unlink-bank-transaction` | Unlink a bank line |
| `finance:resend-payment-sms` | Resend SMS from logs |
| `families:cleanup-single-child` | Family cleanup |
| `finance:set-bank-transaction-reference` | Set reference on transaction |
| `finance:reconcile-optional-fees` | Optional fee reconciliation |
| `finance:fix-eliana-allocation-order` | Specific allocation fix |
| `communications:send-scheduled` | Send queued communications |
| `fee-communications:process-scheduled` | Scheduled fee comms |
| `finance:fix-bank-statement-linkages` | Repair linkages |
| `students:check-parent-contact` | Validate parent contacts |
| `classrooms:backfill-campus` | Backfill campus on classrooms |
| `storage:migrate-to-s3` | File migration |
| `finance:sync-payment-allocation-c2b` | Sync allocation with C2B |
| `finance:diagnose-student-payments` | Debug student payments |
| `finance:import-legacy` | Import legacy PDF statements |
| `families:reset-profile-update-links` | Reset profile links |
| `finance:reallocate-student-bbf` | Reallocate with BBF priority |
| `diaries:backfill` | Diary backfill |
| `curriculum:process` | Process curriculum design job |
| `families:ensure-profile-update-links` | Ensure links exist |
| `finance:diagnose-swimming-excluded` | Swimming payment diagnostics |
| `attendance:audit-prior-to-enrolment` | Attendance vs enrolment audit |
| `students:ensure-payment-links` | Payment link integrity |
| `backup:prune` | Prune old DB backups |

---

## 9. Mobile app (`mobile-app/` — Expo / React Native)

The app consumes the Sanctum API and adds local UI/navigation. **Screens** (functional surfaces) include, among others:

- **Auth** — `LoginScreen`, `OTPVerificationScreen`, `ResetPasswordScreen`
- **Dashboards** — `TeacherDashboard`, `ParentDashboard` / `ParentDashboardScreen`, `FinanceDashboard`, `StudentHomeScreen`
- **Students** — `StudentsListScreen`, `StudentDetailScreen`, `BulkUploadScreen`, `FamilyManagementScreen`
- **Attendance** — `MarkAttendanceScreen`, `AttendanceRecordsScreen`
- **Academics** — `ExamsListScreen`, `MarksEntryScreen`, `ExamMarksSetupScreen`, `TimetableScreen`, `LessonPlansScreen`, `AssignmentsScreen`
- **Finance** — `FinanceHomeScreen`, `FeeStructuresScreen`, `RecordPaymentScreen`, `StudentStatementScreen`, `InvoiceDetailScreen`, `MpesaWaitingWebViewScreen`, `PaymentsHubScreen`, `TransactionsListScreen`, `TransactionDetailScreen`, `ParentPaymentsScreen`
- **HR** — `StaffDirectoryScreen`, `StaffDetailScreen`, `StaffEditScreen`, `LeaveManagementScreen`, `ApplyLeaveScreen`, `PayrollRecordsScreen`, `MyProfileScreen`, `MySalaryScreen`
- **Transport** — `RoutesListScreen`
- **Senior teacher** — `SupervisedClassroomsScreen`, `SupervisedStaffScreen`, `FeeBalancesScreen`
- **Communication** — `SendSMSScreen`, `AnnouncementsScreen`
- **More** — `MoreScreen`

**API modules** under `mobile-app/src/api/` (e.g. `auth.api`, `students.api`, `finance.api`, `hr.api`, `academics.api`, `communication.api`, `dashboard.api`, `client.ts`) wrap HTTP calls to the backend.

---

## 10. Maintaining this document

Regenerate or verify route coverage with:

- `routes/web.php`, `routes/teacher.php`, `routes/senior_teacher.php`, `routes/api.php`
- `php artisan route:list` (in a configured environment)

---

*Generated from repository structure and routes as of the documentation commit.*
