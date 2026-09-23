# Controller catalog (live inventory, 2026-09-22)

Total controllers: **371**. Public methods: **1986**.
The base `Controller` class is listed but is not a route target.

## Counts by folder

| Folder | Controllers | Role |
|---|---:|---|
| `Api` | 92 | Sanctum JSON API for mobile apps + website |
| `Finance` | 51 | Fees, payments, accounting, M-Pesa, expenses |
| `Academics` | 39 | CBC, exams, timetable, lesson plans |
| `root` | 39 | Cross-cutting web (dashboard, comms, transport leftovers) |
| `Website` | 31 | Public school website CMS |
| `Hr` | 23 | Payroll, leave, staff records, BioTime |
| `Api/Website` | 16 |  |
| `Students` | 15 | Registry, families, credentials, imports |
| `Inventory` | 9 | Stock and student requirements |
| `Pos` | 9 | School shop |
| `Reports` | 7 | Operational/academic report writers |
| `Teacher` | 6 | Teacher portal |
| `Swimming` | 5 | Swimming module |
| `Transport` | 5 | Trips, assignments, import |
| `Attendance` | 3 | Attendance + notifications |
| `Library` | 3 | Library cards/borrowing |
| `Operations` | 3 | Assets, visitors, concerns |
| `Activities` | 2 | Activity fees / parent requests |
| `Hostel` | 2 | Boarding (thin / possibly stale) |
| `Settings` | 2 | School settings |
| `WebAuthn` | 2 | Passkey login/register |
| `Admin` | 1 | Admin extras |
| `Api/Concerns` | 1 |  |
| `Auth` | 1 | Password change |
| `Driver` | 1 | Driver portal |
| `ParentPortal` | 1 | Parent diary web |
| `SeniorTeacher` | 1 | Senior teacher workspace |
| `Users` | 1 | Force password change |

## Every controller and its public methods

### `Api` (92)

#### `ApiAcademicReportsController`

- Path: `app/Http/Controllers/Api/ApiAcademicReportsController.php`
- Api: Api Academic Reports — JSON API controller
- Public methods (9): `templates`, `showTemplate`, `storeTemplate`, `updateTemplate`, `publish`, `assigned`, `submit`, `uploadFile`, `submissions`

#### `ApiAcademicsController`

- Path: `app/Http/Controllers/Api/ApiAcademicsController.php`
- Api: Api Academics — JSON API controller
- Public methods (11): `exams`, `examSessions`, `showExam`, `examMarkingOptions`, `marks`, `batchMarks`, `submitExamMarks`, `examMarkEntryAudit`, `marksMatrixContext`, `marksMatrix`, `batchMarksMatrix`

#### `ApiAccountController`

- Path: `app/Http/Controllers/Api/ApiAccountController.php`
- Api: Api Account — JSON API controller
- Public methods (5): `changePassword`, `setUnlockPin`, `clearUnlockPin`, `registerBiometricUnlock`, `revokeBiometricUnlock`

#### `ApiActivityController`

- Path: `app/Http/Controllers/Api/ApiActivityController.php`
- Api: Api Activity — resource-style controller
- Public methods (4): `index`, `students`, `attendance`, `storeAttendance`

#### `ApiAdmissionsController`

- Path: `app/Http/Controllers/Api/ApiAdmissionsController.php`
- Api: Api Admissions — resource-style controller
- Public methods (8): `stats`, `index`, `show`, `updateStatus`, `waitlist`, `reject`, `enroll`, `downloadFile`

#### `ApiAnalyticsController`

- Path: `app/Http/Controllers/Api/ApiAnalyticsController.php`
- Api: Api Analytics — JSON API controller
- Public methods (1): `executive`

#### `ApiAnnouncementController`

- Path: `app/Http/Controllers/Api/ApiAnnouncementController.php`
- Api: Api Announcement — resource-style controller
- Public methods (6): `index`, `publicIndex`, `show`, `store`, `update`, `destroy`

#### `ApiAppAdoptionController`

- Path: `app/Http/Controllers/Api/ApiAppAdoptionController.php`
- Api: Api App Adoption — resource-style controller
- Public methods (1): `index`

#### `ApiAppBrandingController`

- Path: `app/Http/Controllers/Api/ApiAppBrandingController.php`
- Api: Api App Branding — read-heavy controller
- Public methods (1): `show`

#### `ApiAppIssuesController`

- Path: `app/Http/Controllers/Api/ApiAppIssuesController.php`
- Api: Api App Issues — resource-style controller
- Public methods (2): `store`, `index`

#### `ApiApprovalsController`

- Path: `app/Http/Controllers/Api/ApiApprovalsController.php`
- Api: Api Approvals — resource-style controller
- Public methods (4): `index`, `show`, `approve`, `reject`

#### `ApiAttendanceController`

- Path: `app/Http/Controllers/Api/ApiAttendanceController.php`
- Api: Api Attendance — JSON API controller
- Public methods (8): `classAttendance`, `schoolDay`, `reasonCodes`, `report`, `consecutive`, `markStudents`, `mark`, `markAbsent`

#### `ApiAuditTrailController`

- Path: `app/Http/Controllers/Api/ApiAuditTrailController.php`
- Api: Api Audit Trail — resource-style controller
- Public methods (2): `index`, `show`

#### `ApiBioTimeIngestController`

- Path: `app/Http/Controllers/Api/ApiBioTimeIngestController.php`
- Api: Api Bio Time Ingest — resource-style controller
- Public methods (3): `store`, `health`, `employees`

#### `ApiBoardPackController`

- Path: `app/Http/Controllers/Api/ApiBoardPackController.php`
- Api: Api Board Pack — read-heavy controller
- Public methods (1): `show`

#### `ApiCbcController`

- Path: `app/Http/Controllers/Api/ApiCbcController.php`
- Api: Api Cbc — JSON API controller
- Public methods (4): `learningAreas`, `strands`, `substrands`, `substrandShow`

#### `ApiClassroomController`

- Path: `app/Http/Controllers/Api/ApiClassroomController.php`
- Api: Api Classroom — resource-style controller
- Public methods (3): `index`, `streams`, `subjects`

#### `ApiCommunicationController`

- Path: `app/Http/Controllers/Api/ApiCommunicationController.php`
- Api: Api Communication — JSON API controller
- Public methods (12): `templates`, `templateStore`, `templateUpdate`, `templateDestroy`, `templateShow`, `logShow`, `recipients`, `logs`, `sendSms`, `sendWhatsApp`, `sendEmail`, `sendApp`

#### `ApiConcernController`

- Path: `app/Http/Controllers/Api/ApiConcernController.php`
- Api: Api Concern — resource-style controller
- Public methods (5): `index`, `show`, `staffOptions`, `store`, `update`

#### `ApiDashboardController`

- Path: `app/Http/Controllers/Api/ApiDashboardController.php`
- Api: Api Dashboard — JSON API controller
- Public methods (1): `stats`

#### `ApiDeviceTokenController`

- Path: `app/Http/Controllers/Api/ApiDeviceTokenController.php`
- Api: Api Device Token — resource-style controller
- Public methods (2): `store`, `destroy`

#### `ApiDiaryController`

- Path: `app/Http/Controllers/Api/ApiDiaryController.php`
- Api: Api Diary — resource-style controller
- Public methods (3): `index`, `showForStudent`, `storeEntry`

#### `ApiDriverTransportController`

- Path: `app/Http/Controllers/Api/ApiDriverTransportController.php`
- Api: Api Driver Transport — resource-style controller
- Public methods (8): `index`, `show`, `start`, `stop`, `boarding`, `markBoarding`, `pingLocation`, `vehicle`

#### `ApiExamReportsController`

- Path: `app/Http/Controllers/Api/ApiExamReportsController.php`
- Api: Api Exam Reports — JSON API controller
- Public methods (9): `classSheet`, `teacherPerformance`, `subjectPerformance`, `studentInsights`, `exportClassSheet`, `exportTermWorkbook`, `masteryProfile`, `trends`, `insights`

#### `ApiExpenseReportsController`

- Path: `app/Http/Controllers/Api/ApiExpenseReportsController.php`
- Api: Api Expense Reports — JSON API controller
- Public methods (2): `incomeStatement`, `summary`

#### `ApiExpensesController`

- Path: `app/Http/Controllers/Api/ApiExpensesController.php`
- Api: Api Expenses — resource-style controller
- Public methods (9): `index`, `show`, `store`, `storeAttachment`, `destroyAttachment`, `submit`, `approve`, `reject`, `pay`

#### `ApiFeeClearanceController`

- Path: `app/Http/Controllers/Api/ApiFeeClearanceController.php`
- Api: Api Fee Clearance — read-heavy controller
- Public methods (3): `show`, `classRoster`, `tripRoster`

#### `ApiFeeStructureController`

- Path: `app/Http/Controllers/Api/ApiFeeStructureController.php`
- Api: Api Fee Structure — resource-style controller
- Public methods (1): `index`

#### `ApiFeedbackController`

- Path: `app/Http/Controllers/Api/ApiFeedbackController.php`
- Api: Api Feedback — JSON API controller
- Public methods (3): `template`, `submit`, `uploadFile`

#### `ApiFinanceSummaryController`

- Path: `app/Http/Controllers/Api/ApiFinanceSummaryController.php`
- Api: Api Finance Summary — read-heavy controller
- Public methods (1): `show`

#### `ApiFinanceTransactionsController`

- Path: `app/Http/Controllers/Api/ApiFinanceTransactionsController.php`
- Api: Api Finance Transactions — resource-style controller
- Public methods (2): `index`, `show`

#### `ApiFixedAssetsController`

- Path: `app/Http/Controllers/Api/ApiFixedAssetsController.php`
- Api: Api Fixed Assets — resource-style controller
- Public methods (5): `index`, `show`, `store`, `update`, `updateStatus`

#### `ApiForcePasswordChangeController`

- Path: `app/Http/Controllers/Api/ApiForcePasswordChangeController.php`
- Api: Api Force Password Change — JSON API controller
- Public methods (2): `targets`, `requireChange`

#### `ApiHomeworkController`

- Path: `app/Http/Controllers/Api/ApiHomeworkController.php`
- Api: Api Homework — resource-style controller
- Public methods (8): `index`, `show`, `store`, `update`, `status`, `complete`, `uncomplete`, `diary`

#### `ApiInventoryController`

- Path: `app/Http/Controllers/Api/ApiInventoryController.php`
- Api: Api Inventory — resource-style controller
- Public methods (3): `index`, `show`, `adjust`

#### `ApiInventoryReportsController`

- Path: `app/Http/Controllers/Api/ApiInventoryReportsController.php`
- Api: Api Inventory Reports — JSON API controller
- Public methods (2): `requirements`, `receipts`

#### `ApiInvoiceController`

- Path: `app/Http/Controllers/Api/ApiInvoiceController.php`
- Api: Api Invoice — resource-style controller
- Public methods (2): `index`, `show`

#### `ApiJengaController`

- Path: `app/Http/Controllers/Api/ApiJengaController.php`
- Api: Api Jenga — JSON API controller
- Public methods (14): `token`, `accountBalance`, `accountInquiry`, `miniStatement`, `fullStatement`, `disburseMobile`, `disburseWithinEquity`, `disburseRtgs`, `rtgsPaymentPurposes`, `stkUssdPush`, `queryTransactionDetails`, `billers`, `merchants`, `signedProxy`

#### `ApiKemisController`

- Path: `app/Http/Controllers/Api/ApiKemisController.php`
- Api: Api Kemis — JSON API controller
- Public methods (1): `options`

#### `ApiLeaveRequestController`

- Path: `app/Http/Controllers/Api/ApiLeaveRequestController.php`
- Api: Api Leave Request — resource-style controller
- Public methods (8): `leaveTypes`, `storeLeaveType`, `updateLeaveType`, `assignLeaveType`, `index`, `store`, `approve`, `reject`

#### `ApiLedgerController`

- Path: `app/Http/Controllers/Api/ApiLedgerController.php`
- Api: Api Ledger — JSON API controller
- Public methods (3): `postings`, `trialBalance`, `balanceSheet`

#### `ApiLessonPlansController`

- Path: `app/Http/Controllers/Api/ApiLessonPlansController.php`
- Api: Api Lesson Plans — resource-style controller
- Public methods (8): `index`, `show`, `store`, `update`, `submit`, `reviewQueue`, `approve`, `reject`

#### `ApiLibraryController`

- Path: `app/Http/Controllers/Api/ApiLibraryController.php`
- Api: Api Library — resource-style controller
- Public methods (5): `index`, `borrowings`, `issue`, `returnBorrowing`, `renew`

#### `ApiMedicalRecordsController`

- Path: `app/Http/Controllers/Api/ApiMedicalRecordsController.php`
- Api: Api Medical Records — resource-style controller
- Public methods (4): `index`, `store`, `show`, `uploadCertificate`

#### `ApiMpesaPaymentController`

- Path: `app/Http/Controllers/Api/ApiMpesaPaymentController.php`
- Api: Api Mpesa Payment — JSON API controller
- Public methods (2): `prompt`, `paymentLinkUrl`

#### `ApiNotificationController`

- Path: `app/Http/Controllers/Api/ApiNotificationController.php`
- Api: Api Notification — resource-style controller
- Public methods (6): `index`, `markRead`, `markAllRead`, `unreadCount`, `acknowledge`, `destroy`

#### `ApiNotificationPreferencesController`

- Path: `app/Http/Controllers/Api/ApiNotificationPreferencesController.php`
- Api: Api Notification Preferences — resource-style controller
- Public methods (2): `show`, `update`

#### `ApiOperationsSummaryController`

- Path: `app/Http/Controllers/Api/ApiOperationsSummaryController.php`
- Api: Api Operations Summary — read-heavy controller
- Public methods (1): `show`

#### `ApiParentAttendanceController`

- Path: `app/Http/Controllers/Api/ApiParentAttendanceController.php`
- Api: Api Parent Attendance — resource-style controller
- Public methods (3): `reasonCodes`, `history`, `store`

#### `ApiParentClaimController`

- Path: `app/Http/Controllers/Api/ApiParentClaimController.php`
- Api: Api Parent Claim — JSON API controller
- Public methods (4): `requestOtp`, `verifyOtp`, `verifyAdmission`, `complete`

#### `ApiParentCoCurricularController`

- Path: `app/Http/Controllers/Api/ApiParentCoCurricularController.php`
- Api: Api Parent Co Curricular — resource-style controller
- Public methods (3): `show`, `store`, `cancel`

#### `ApiParentCredentialsController`

- Path: `app/Http/Controllers/Api/ApiParentCredentialsController.php`
- Api: Api Parent Credentials — read-heavy controller
- Public methods (3): `show`, `reset`, `requirePasswordChange`

#### `ApiParentForcedActionsController`

- Path: `app/Http/Controllers/Api/ApiParentForcedActionsController.php`
- Api: Api Parent Forced Actions — resource-style controller
- Public methods (3): `index`, `complete`, `storeForParent`

#### `ApiParentIdentityGateController`

- Path: `app/Http/Controllers/Api/ApiParentIdentityGateController.php`
- Api: Api Parent Identity Gate — resource-style controller
- Public methods (2): `show`, `update`

#### `ApiParentProfileReviewController`

- Path: `app/Http/Controllers/Api/ApiParentProfileReviewController.php`
- Api: Api Parent Profile Review — resource-style controller
- Public methods (3): `show`, `update`, `complete`

#### `ApiParentTransportController`

- Path: `app/Http/Controllers/Api/ApiParentTransportController.php`
- Api: Api Parent Transport — JSON API controller
- Public methods (1): `options`

#### `ApiParentWalletController`

- Path: `app/Http/Controllers/Api/ApiParentWalletController.php`
- Api: Api Parent Wallet — JSON API controller
- Public methods (8): `show`, `topUp`, `pay`, `listSavingPlans`, `storeSavingPlan`, `updateSavingPlan`, `destroySavingPlan`, `paySavingPlanNow`

#### `ApiPaymentController`

- Path: `app/Http/Controllers/Api/ApiPaymentController.php`
- Api: Api Payment — resource-style controller
- Public methods (3): `index`, `show`, `store`

#### `ApiPayrollRecordsController`

- Path: `app/Http/Controllers/Api/ApiPayrollRecordsController.php`
- Api: Api Payroll Records — resource-style controller
- Public methods (2): `index`, `show`

#### `ApiPayslipController`

- Path: `app/Http/Controllers/Api/ApiPayslipController.php`
- Api: Api Payslip — JSON API controller
- Public methods (1): `download`

#### `ApiReportCardController`

- Path: `app/Http/Controllers/Api/ApiReportCardController.php`
- Api: Api Report Card — resource-style controller
- Public methods (2): `index`, `show`

#### `ApiRequisitionController`

- Path: `app/Http/Controllers/Api/ApiRequisitionController.php`
- Api: Api Requisition — resource-style controller
- Public methods (5): `index`, `store`, `show`, `approve`, `reject`

#### `ApiRouteController`

- Path: `app/Http/Controllers/Api/ApiRouteController.php`
- Api: Api Route — resource-style controller
- Public methods (7): `index`, `show`, `students`, `assignStudent`, `store`, `update`, `destroy`

#### `ApiSchoolResolveController`

- Path: `app/Http/Controllers/Api/ApiSchoolResolveController.php`
- Api: Api School Resolve — JSON API controller
- Public methods (1): `resolve`

#### `ApiSearchController`

- Path: `app/Http/Controllers/Api/ApiSearchController.php`
- Api: Api Search — resource-style controller
- Public methods (2): `index`, `suggest`

#### `ApiSeniorTeacherController`

- Path: `app/Http/Controllers/Api/ApiSeniorTeacherController.php`
- Api: Api Senior Teacher — JSON API controller
- Public methods (5): `supervisedClassrooms`, `supervisedStaff`, `feeBalances`, `supervisedStudents`, `pendingFeeClearances`

#### `ApiSessionController`

- Path: `app/Http/Controllers/Api/ApiSessionController.php`
- Api: Api Session — resource-style controller
- Public methods (3): `index`, `revoke`, `refresh`

#### `ApiSettingsHubController`

- Path: `app/Http/Controllers/Api/ApiSettingsHubController.php`
- Api: Api Settings Hub — JSON API controller
- Public methods (8): `school`, `academicYears`, `terms`, `classes`, `streams`, `subjects`, `gradingSchemes`, `roles`

#### `ApiSpeedTestController`

- Path: `app/Http/Controllers/Api/ApiSpeedTestController.php`
- Api: Api Speed Test — resource-style controller
- Public methods (4): `index`, `store`, `show`, `saveMarks`

#### `ApiStaffAdvanceController`

- Path: `app/Http/Controllers/Api/ApiStaffAdvanceController.php`
- Api: Api Staff Advance — resource-style controller
- Public methods (5): `index`, `show`, `store`, `approve`, `reject`

#### `ApiStaffClockController`

- Path: `app/Http/Controllers/Api/ApiStaffClockController.php`
- Api: Api Staff Clock — JSON API controller
- Public methods (9): `geofence`, `updateGeofence`, `today`, `history`, `calendar`, `clockRoster`, `staffHistory`, `clockIn`, `clockOut`

#### `ApiStaffController`

- Path: `app/Http/Controllers/Api/ApiStaffController.php`
- Api: Api Staff — resource-style controller
- Public methods (12): `index`, `filterOptions`, `show`, `leaveBalances`, `attendanceHistory`, `update`, `uploadPhoto`, `archivePreview`, `archive`, `resetPassword`, `requirePasswordChange`, `resendCredentials`

#### `ApiStaffDocumentsController`

- Path: `app/Http/Controllers/Api/ApiStaffDocumentsController.php`
- Api: Api Staff Documents — resource-style controller
- Public methods (3): `index`, `store`, `download`

#### `ApiStaffPerformanceController`

- Path: `app/Http/Controllers/Api/ApiStaffPerformanceController.php`
- Api: Api Staff Performance — resource-style controller
- Public methods (2): `index`, `show`

#### `ApiStaffTrainingController`

- Path: `app/Http/Controllers/Api/ApiStaffTrainingController.php`
- Api: Api Staff Training — resource-style controller
- Public methods (2): `index`, `show`

#### `ApiStudentAssessmentController`

- Path: `app/Http/Controllers/Api/ApiStudentAssessmentController.php`
- Api: Api Student Assessment — JSON API controller
- Public methods (2): `assessmentHistory`, `academicSummary`

#### `ApiStudentAssignmentController`

- Path: `app/Http/Controllers/Api/ApiStudentAssignmentController.php`
- Api: Api Student Assignment — resource-style controller
- Public methods (6): `index`, `show`, `store`, `update`, `destroy`, `assignToTrip`

#### `ApiStudentController`

- Path: `app/Http/Controllers/Api/ApiStudentController.php`
- Api: Api Student — resource-style controller
- Public methods (7): `index`, `parentsContact`, `archived`, `show`, `serializeStudent`, `stats`, `attendanceCalendar`

#### `ApiStudentDocumentsController`

- Path: `app/Http/Controllers/Api/ApiStudentDocumentsController.php`
- Api: Api Student Documents — resource-style controller
- Public methods (6): `index`, `download`, `store`, `storeParentIdCard`, `listParentDocuments`, `downloadParentDocument`

#### `ApiStudentStatementController`

- Path: `app/Http/Controllers/Api/ApiStudentStatementController.php`
- Api: Api Student Statement — read-heavy controller
- Public methods (1): `show`

#### `ApiStudentWriteController`

- Path: `app/Http/Controllers/Api/ApiStudentWriteController.php`
- Api: Api Student Write — resource-style controller
- Public methods (4): `categories`, `profileUpdateLink`, `store`, `update`

#### `ApiTeacherAssignmentController`

- Path: `app/Http/Controllers/Api/ApiTeacherAssignmentController.php`
- Api: Api Teacher Assignment — resource-style controller
- Public methods (3): `streamSlots`, `show`, `update`

#### `ApiTeacherRequirementsController`

- Path: `app/Http/Controllers/Api/ApiTeacherRequirementsController.php`
- Api: Api Teacher Requirements — JSON API controller
- Public methods (3): `students`, `templatesForStudent`, `collect`

#### `ApiTeacherTransportController`

- Path: `app/Http/Controllers/Api/ApiTeacherTransportController.php`
- Api: Api Teacher Transport — JSON API controller
- Public methods (5): `students`, `markCollectedByParent`, `cancelPickup`, `temporaryReassignment`, `vehicles`

#### `ApiTimetableController`

- Path: `app/Http/Controllers/Api/ApiTimetableController.php`
- Api: Api Timetable — JSON API controller
- Public methods (4): `mine`, `classGrid`, `teacher`, `student`

#### `ApiTransportSpecialAssignmentController`

- Path: `app/Http/Controllers/Api/ApiTransportSpecialAssignmentController.php`
- Api: Api Transport Special Assignment — resource-style controller
- Public methods (4): `index`, `store`, `approve`, `cancel`

#### `ApiTransportTrackingController`

- Path: `app/Http/Controllers/Api/ApiTransportTrackingController.php`
- Api: Api Transport Tracking — JSON API controller
- Public methods (3): `liveForStudent`, `liveFleet`, `activeRunForTrip`

#### `ApiVehicleController`

- Path: `app/Http/Controllers/Api/ApiVehicleController.php`
- Api: Api Vehicle — resource-style controller
- Public methods (5): `index`, `show`, `store`, `update`, `destroy`

#### `ApiVisitorsController`

- Path: `app/Http/Controllers/Api/ApiVisitorsController.php`
- Api: Api Visitors — resource-style controller
- Public methods (4): `index`, `show`, `store`, `checkout`

#### `ApiWeeklyReportsController`

- Path: `app/Http/Controllers/Api/ApiWeeklyReportsController.php`
- Api: Api Weekly Reports — resource-style controller
- Public methods (2): `index`, `show`

#### `AuthApiController`

- Path: `app/Http/Controllers/Api/AuthApiController.php`
- Api: Auth Api — action controller
- Public methods (14): `login`, `loginWithPin`, `loginWithBiometric`, `loginWithGoogle`, `requestLoginOtp`, `verifyLoginOtp`, `requestPasswordResetEmailLink`, `requestPasswordResetSmsLink`, `requestPasswordResetOtp`, `verifyPasswordResetOtp`, `resetPassword`, `user`, `logout`, `formatUserForApiPublic`

#### `requirements`

- Path: `app/Http/Controllers/Api/ApiParentRequirementsController.php`
- Api: requirements — read-heavy controller
- Public methods (1): `show`

### `Finance` (51)

#### `AccountantDashboardController`

- Path: `app/Http/Controllers/Finance/AccountantDashboardController.php`
- Finance: Accountant Dashboard — resource-style controller
- Public methods (4): `index`, `settings`, `updateSettings`, `studentHistory`
- Views returned: `finance.accountant_dashboard.index`, `finance.accountant_dashboard.settings`, `finance.accountant_dashboard.student_history`

#### `AccountingReportController`

- Path: `app/Http/Controllers/Finance/AccountingReportController.php`
- Finance: Accounting Report — action controller
- Public methods (4): `trialBalance`, `profitAndLoss`, `balanceSheet`, `profitLossReconciliation`
- Views returned: `finance.accounting.reports.balance_sheet`, `finance.accounting.reports.profit_and_loss`, `finance.accounting.reports.profit_loss_reconciliation`, `finance.accounting.reports.trial_balance`

#### `BalanceBroughtForwardController`

- Path: `app/Http/Controllers/Finance/BalanceBroughtForwardController.php`
- Finance: Balance Brought Forward — resource-style controller
- Public methods (8): `index`, `importPreview`, `importCommit`, `reverse`, `add`, `update`, `destroy`, `template`
- Views returned: `finance.balance_brought_forward.import_preview`, `finance.balance_brought_forward.index`

#### `BankAccountController`

- Path: `app/Http/Controllers/Finance/BankAccountController.php`
- Finance: Bank Account — full resource CRUD
- Public methods (7): `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`
- Views returned: `finance.bank_accounts.create`, `finance.bank_accounts.edit`, `finance.bank_accounts.index`, `finance.bank_accounts.show`

#### `BankStatementController`

- Path: `app/Http/Controllers/Finance/BankStatementController.php`
- Finance: Bank Statement — full resource CRUD
- Public methods (42): `statements`, `index`, `create`, `store`, `show`, `searchPaymentsForLink`, `linkToExistingPayments`, `edit`, `update`, `assign`, `confirm`, `confirmAndCreatePaymentForC2B`, `createPayment`, `splitTransaction`, `reconcilePayments`, `reject`, `resolveConflictReverse`, `resolveConflictKeep`, `resolveConflictCreateNew`, `updateAllocations`, `share`, `viewPdf`, `servePdf`, `downloadPdf`, `bulkConfirm`, `bulkConfirmAndCreatePayments`, `bulkArchive`, `archive`, `unarchive`, `history`, `autoAssign`, `reparse`, `allocateUnallocatedPayments`, `bulkMarkAsSwimming`, `unmarkAsSwimming`, `bulkTransferToSwimming`, `bulkTransferFromSwimming`, `allocateSwimmingTransaction`, `reprocessSwimmingTransactions`, `getStudentBalance`, `destroy`, `forceReparseStatement`
- Views returned: `finance.bank-statements.create`, `finance.bank-statements.edit`, `finance.bank-statements.history`, `finance.bank-statements.index`, `finance.bank-statements.show`, `finance.bank-statements.statements`, `finance.bank-statements.view-pdf`

#### `BudgetController`

- Path: `app/Http/Controllers/Finance/BudgetController.php`
- Finance: Budget — resource-style controller
- Public methods (4): `index`, `store`, `show`, `storeLine`
- Views returned: `finance.accounting.budgets.index`, `finance.accounting.budgets.show`

#### `ChartOfAccountController`

- Path: `app/Http/Controllers/Finance/ChartOfAccountController.php`
- Finance: Chart Of Account — resource-style controller
- Public methods (3): `index`, `store`, `update`
- Views returned: `finance.accounting.chart_of_accounts.index`

#### `CreditDebitNoteImportController`

- Path: `app/Http/Controllers/Finance/CreditDebitNoteImportController.php`
- Finance: Credit Debit Note Import — action controller
- Public methods (4): `importPreview`, `importCommit`, `template`, `reverse`
- Views returned: `finance.credit_debit_notes.import_preview`

#### `CreditNoteController`

- Path: `app/Http/Controllers/Finance/CreditNoteController.php`
- Finance: Credit Note — resource-style controller
- Public methods (4): `index`, `create`, `store`, `reverse`
- Views returned: `finance.credit_notes.create`, `finance.credit_notes.index`

#### `DebitNoteController`

- Path: `app/Http/Controllers/Finance/DebitNoteController.php`
- Finance: Debit Note — resource-style controller
- Public methods (4): `index`, `create`, `store`, `reverse`
- Views returned: `finance.debit_notes.create`, `finance.debit_notes.index`

#### `DiscountController`

- Path: `app/Http/Controllers/Finance/DiscountController.php`
- Finance: Discount — resource-style controller
- Public methods (18): `index`, `create`, `store`, `show`, `applySiblingDiscount`, `templatesIndex`, `allocate`, `storeAllocation`, `allocationsIndex`, `approve`, `reject`, `bulkAllocateSibling`, `bulkAllocateSiblingForm`, `replicateForm`, `replicate`, `bulkApprove`, `bulkReject`, `reverse`
- Views returned: `finance.discounts.allocations.index`, `finance.discounts.bulk-allocate-sibling`, `finance.discounts.create`, `finance.discounts.index`, `finance.discounts.replicate`, `finance.discounts.show`, `finance.discounts.templates.index`

#### `DocumentSettingsController`

- Path: `app/Http/Controllers/Finance/DocumentSettingsController.php`
- Finance: Document Settings — resource-style controller
- Public methods (2): `index`, `update`
- Views returned: `finance.document_settings.index`

#### `ExpenseApprovalController`

- Path: `app/Http/Controllers/Finance/ExpenseApprovalController.php`
- Finance: Expense Approval — resource-style controller
- Public methods (1): `store`

#### `ExpenseCategoryController`

- Path: `app/Http/Controllers/Finance/ExpenseCategoryController.php`
- Finance: Expense Category — resource-style controller
- Public methods (4): `index`, `store`, `update`, `destroy`
- Views returned: `finance.expense_categories.index`

#### `ExpenseController`

- Path: `app/Http/Controllers/Finance/ExpenseController.php`
- Finance: Expense — resource-style controller
- Public methods (10): `index`, `cashBookExport`, `quickUpdate`, `bulkUpdate`, `create`, `store`, `show`, `edit`, `update`, `submit`
- Views returned: `finance.expenses.create`, `finance.expenses.edit`, `finance.expenses.index`, `finance.expenses.show`

#### `ExpenseReportController`

- Path: `app/Http/Controllers/Finance/ExpenseReportController.php`
- Finance: Expense Report — resource-style controller
- Public methods (3): `index`, `exportCsv`, `exportPdf`
- Views returned: `finance.reports.expenses`, `finance.reports.expenses_pdf`

#### `ExpenseStatementController`

- Path: `app/Http/Controllers/Finance/ExpenseStatementController.php`
- Finance: Expense Statement — resource-style controller
- Public methods (14): `index`, `create`, `store`, `parseProgress`, `show`, `updateGroup`, `bulkUpdateGroups`, `updateLine`, `submitExpenses`, `approveExpenses`, `rejectExpense`, `reverseExpense`, `editExpense`, `destroy`
- Views returned: `finance.expense-statements.create`, `finance.expense-statements.index`, `finance.expense-statements.processing`, `finance.expense-statements.show`

#### `FeeBalanceController`

- Path: `app/Http/Controllers/Finance/FeeBalanceController.php`
- Finance: Fee Balance — resource-style controller
- Public methods (4): `index`, `export`, `exportPdf`, `printPdf`
- Views returned: `finance.fee_balances.index`

#### `FeeClearanceReportController`

- Path: `app/Http/Controllers/Finance/FeeClearanceReportController.php`
- Finance: Fee Clearance Report — resource-style controller
- Public methods (3): `index`, `exportPdfByClass`, `recompute`
- Views returned: `finance.fee_clearance.index`

#### `FeeConcessionController`

- Path: `app/Http/Controllers/Finance/FeeConcessionController.php`
- Finance: Fee Concession — resource-style controller
- Public methods (6): `index`, `create`, `store`, `show`, `approve`, `deactivate`
- Views returned: `finance.fee_concessions.create`, `finance.fee_concessions.index`, `finance.fee_concessions.show`

#### `FeePaymentPlanController`

- Path: `app/Http/Controllers/Finance/FeePaymentPlanController.php`
- Finance: Fee Payment Plan — resource-style controller
- Public methods (9): `index`, `create`, `getStudentInvoicesAndSiblings`, `store`, `show`, `printAgreement`, `downloadAgreementPdf`, `updateStatus`, `publicView`
- Views returned: `finance.fee_payment_plans.create`, `finance.fee_payment_plans.index`, `finance.fee_payment_plans.pdf.agreement`, `finance.fee_payment_plans.public`, `finance.fee_payment_plans.show`

#### `FeeReminderController`

- Path: `app/Http/Controllers/Finance/FeeReminderController.php`
- Finance: Fee Reminder — resource-style controller
- Public methods (5): `index`, `create`, `store`, `send`, `sendAutomatedReminders`
- Views returned: `finance.fee_reminders.index`

#### `FeeStatementController`

- Path: `app/Http/Controllers/Finance/FeeStatementController.php`
- Finance: Fee Statement — resource-style controller
- Public methods (3): `index`, `show`, `generate`
- Views returned: `finance.fee_statements.index`, `finance.fee_statements.show`

#### `FeeStructureController`

- Path: `app/Http/Controllers/Finance/FeeStructureController.php`
- Finance: Fee Structure — resource-style controller
- Public methods (9): `index`, `show`, `manage`, `save`, `replicateTo`, `replicateTerms`, `import`, `processImport`, `downloadTemplate`
- Views returned: `finance.fee_structures.import`, `finance.fee_structures.index`, `finance.fee_structures.manage`, `finance.fee_structures.show`

#### `FeesComparisonImportController`

- Path: `app/Http/Controllers/Finance/FeesComparisonImportController.php`
- Finance: Fees Comparison Import — resource-style controller
- Public methods (4): `index`, `preview`, `show`, `template`
- Views returned: `finance.fees_comparison_import.index`, `finance.fees_comparison_import.preview`

#### `FiscalPeriodController`

- Path: `app/Http/Controllers/Finance/FiscalPeriodController.php`
- Finance: Fiscal Period — resource-style controller
- Public methods (3): `index`, `store`, `close`
- Views returned: `finance.accounting.fiscal_periods.index`

#### `InvoiceAdjustmentController`

- Path: `app/Http/Controllers/Finance/InvoiceAdjustmentController.php`
- Finance: Invoice Adjustment — action controller
- Public methods (2): `importForm`, `import`
- Views returned: `finance.invoices.adjustments.import`

#### `InvoiceController`

- Path: `app/Http/Controllers/Finance/InvoiceController.php`
- Finance: Invoice — resource-style controller
- Public methods (20): `index`, `bulkDelete`, `create`, `generate`, `show`, `reverse`, `importForm`, `import`, `updateItem`, `storeCustomItem`, `removeLegacyUniformItem`, `removeCustomItem`, `storeUniform`, `removeUniform`, `history`, `exportCsv`, `carryForwardPriorTermBalances`, `printBulk`, `printSingle`, `publicView`
- Views returned: `finance.invoices.create`, `finance.invoices.history`, `finance.invoices.import`, `finance.invoices.index`, `finance.invoices.pdf.bulk`, `finance.invoices.pdf.single`, `finance.invoices.public`, `finance.invoices.show`

#### `JournalController`

- Path: `app/Http/Controllers/Finance/JournalController.php`
- Finance: Journal — resource-style controller
- Public methods (7): `index`, `create`, `getInvoiceVoteheads`, `store`, `bulkForm`, `template`, `bulkImport`
- Views returned: `finance.credit_debit_adjustments.bulk`, `finance.credit_debit_adjustments.index`, `finance.journals.create`

#### `JournalEntryController`

- Path: `app/Http/Controllers/Finance/JournalEntryController.php`
- Finance: Journal Entry — resource-style controller
- Public methods (4): `index`, `create`, `store`, `show`
- Views returned: `finance.accounting.journal_entries.create`, `finance.accounting.journal_entries.index`, `finance.accounting.journal_entries.show`

#### `LegacyFinanceImportController`

- Path: `app/Http/Controllers/Finance/LegacyFinanceImportController.php`
- Finance: Legacy Finance Import — resource-style controller
- Public methods (9): `index`, `show`, `rerun`, `destroy`, `updateLine`, `store`, `editHistory`, `revertEdit`, `searchStudent`
- Views returned: `finance.legacy-imports.edit-history`, `finance.legacy-imports.index`, `finance.legacy-imports.show`

#### `MpesaPaymentController`

- Path: `app/Http/Controllers/Finance/MpesaPaymentController.php`
- Finance: Mpesa Payment — action controller
- Public methods (31): `dashboard`, `promptPaymentForm`, `promptPayment`, `createLinkForm`, `createLink`, `showLink`, `listLinks`, `showPublicSelfPayForm`, `lookupPublicSelfPayStudent`, `processPublicSelfPay`, `showPaymentPage`, `processLinkPayment`, `showInvoicePayment`, `processInvoicePayment`, `showTransaction`, `queryTransaction`, `waiting`, `showPublicWaiting`, `getTransactionStatus`, `cancelTransaction`, `cancelLink`, `sendLink`, `getStudentData`, `getStudentInvoices`, `handleC2BCallback`, `c2bDashboard`, `c2bTransactions`, `c2bTransactionShow`, `c2bAllocate`, `getLatestC2BTransactions`, `registerC2BUrls`
- Views returned: `finance.mpesa.c2b-dashboard`, `finance.mpesa.c2b-transactions`, `finance.mpesa.create-link`, `finance.mpesa.dashboard`, `finance.mpesa.invoice-payment`, `finance.mpesa.link-details`, `finance.mpesa.link-expired`, `finance.mpesa.links`, `finance.mpesa.payment-page`, `finance.mpesa.prompt-payment`, `finance.mpesa.public-pay`, `finance.mpesa.transaction`, `finance.mpesa.waiting`, `finance.mpesa.waiting-standalone`

#### `OptionalFeeController`

- Path: `app/Http/Controllers/Finance/OptionalFeeController.php`
- Finance: Optional Fee — resource-style controller
- Public methods (8): `index`, `classView`, `studentView`, `saveClassBilling`, `saveStudentBilling`, `duplicatePreview`, `duplicateCommit`, `duplicate`
- Views returned: `finance.optional_fees.duplicate_preview`, `finance.optional_fees.index`

#### `OptionalFeeImportController`

- Path: `app/Http/Controllers/Finance/OptionalFeeImportController.php`
- Finance: Optional Fee Import — action controller
- Public methods (6): `importPreview`, `importCommit`, `template`, `reverse`, `importHistory`, `showImport`
- Views returned: `finance.optional_fees.import_details`, `finance.optional_fees.import_history`, `finance.optional_fees.import_preview`

#### `PaymentController`

- Path: `app/Http/Controllers/Finance/PaymentController.php`
- Finance: Payment — full resource CRUD
- Public methods (33): `index`, `create`, `getStudentBalanceAndSiblings`, `store`, `sendPaymentNotifications`, `allocate`, `show`, `updateSharedAllocations`, `edit`, `update`, `destroy`, `reverse`, `history`, `transfer`, `printReceipt`, `downloadReceiptPdf`, `bulkPrintReceipts`, `viewReceipt`, `publicViewReceipt`, `createPayNowFromReceiptToken`, `myReceipts`, `initiateOnline`, `showTransaction`, `verifyTransaction`, `failedCommunications`, `bulkAllocateUnallocated`, `resendCommunication`, `resendMultipleCommunications`, `bulkSendPreview`, `bulkSend`, `bulkSendTracking`, `bulkSendProgressCheck`, `bulkSendSynchronous_OLD`
- Views returned: `finance.payments.bulk-send-preview`, `finance.payments.bulk-send-progress`, `finance.payments.create`, `finance.payments.failed-communications`, `finance.payments.history`, `finance.payments.index`, `finance.payments.show`, `finance.payments.transaction`, `finance.receipts.bulk-print`, `finance.receipts.bulk-print-pdf`, `finance.receipts.my-receipts`, `finance.receipts.pdf.template`, `finance.receipts.public`, `finance.receipts.view`

#### `PaymentMethodController`

- Path: `app/Http/Controllers/Finance/PaymentMethodController.php`
- Finance: Payment Method — full resource CRUD
- Public methods (7): `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`
- Views returned: `finance.payment_methods.create`, `finance.payment_methods.edit`, `finance.payment_methods.index`, `finance.payment_methods.show`

#### `PaymentThresholdController`

- Path: `app/Http/Controllers/Finance/PaymentThresholdController.php`
- Finance: Payment Threshold — resource-style controller
- Public methods (6): `index`, `create`, `store`, `edit`, `update`, `destroy`
- Views returned: `finance.payment_thresholds.create`, `finance.payment_thresholds.edit`, `finance.payment_thresholds.index`

#### `PaymentVoucherController`

- Path: `app/Http/Controllers/Finance/PaymentVoucherController.php`
- Finance: Payment Voucher — resource-style controller
- Public methods (4): `index`, `store`, `show`, `pay`
- Views returned: `finance.vouchers.index`, `finance.vouchers.show`

#### `PettyCashFundController`

- Path: `app/Http/Controllers/Finance/PettyCashFundController.php`
- Finance: Petty Cash Fund — resource-style controller
- Public methods (3): `index`, `create`, `store`
- Views returned: `finance.accounting.petty_cash.funds.create`, `finance.accounting.petty_cash.funds.index`

#### `PettyCashVoucherController`

- Path: `app/Http/Controllers/Finance/PettyCashVoucherController.php`
- Finance: Petty Cash Voucher — resource-style controller
- Public methods (6): `index`, `create`, `store`, `show`, `approve`, `post`
- Views returned: `finance.accounting.petty_cash.vouchers.create`, `finance.accounting.petty_cash.vouchers.index`, `finance.accounting.petty_cash.vouchers.show`

#### `PostingController`

- Path: `app/Http/Controllers/Finance/PostingController.php`
- Finance: Posting — resource-style controller
- Public methods (6): `index`, `preview`, `commit`, `show`, `reverse`, `reverseStudent`
- Views returned: `finance.posting.index`, `finance.posting.preview`, `finance.posting.show`

#### `ReceiptController`

- Path: `app/Http/Controllers/Finance/ReceiptController.php`
- Finance: Receipt — resource-style controller
- Public methods (2): `index`, `show`
- Views returned: `finance.receipts.index`, `finance.receipts.show`

#### `ScheduledFeeCommunicationController`

- Path: `app/Http/Controllers/Finance/ScheduledFeeCommunicationController.php`
- Finance: Scheduled Fee Communication — resource-style controller
- Public methods (6): `index`, `create`, `store`, `destroy`, `previewCount`, `previewRecipients`
- Views returned: `finance.fee_reminders.schedule.create`

#### `SiblingBalanceTransferController`

- Path: `app/Http/Controllers/Finance/SiblingBalanceTransferController.php`
- Finance: Sibling Balance Transfer — resource-style controller
- Public methods (1): `store`

#### `StatementTransactionController`

- Path: `app/Http/Controllers/Finance/StatementTransactionController.php`
- Finance: Statement Transaction — resource-style controller
- Public methods (5): `index`, `updateGroup`, `bulkUpdateGroups`, `updateLine`, `submitExpenses`
- Views returned: `finance.statement-transactions.index`

#### `StudentCreditDebitNoteController`

- Path: `app/Http/Controllers/Finance/StudentCreditDebitNoteController.php`
- Finance: Student Credit Debit Note — resource-style controller
- Public methods (4): `index`, `show`, `terms`, `exportSchool`
- Views returned: `finance.student_credit_debit_notes.index`, `finance.student_credit_debit_notes.show`

#### `StudentStatementController`

- Path: `app/Http/Controllers/Finance/StudentStatementController.php`
- Finance: Student Statement — resource-style controller
- Public methods (10): `index`, `show`, `updateLegacyLine`, `storeEntry`, `print`, `showFamily`, `familyPrint`, `familyExport`, `export`, `publicView`
- Views returned: `finance.student_statements.family`, `finance.student_statements.index`, `finance.student_statements.print`, `finance.student_statements.show`

#### `TransactionFixAuditController`

- Path: `app/Http/Controllers/Finance/TransactionFixAuditController.php`
- Finance: Transaction Fix Audit — resource-style controller
- Public methods (5): `index`, `show`, `reverse`, `bulkReverse`, `export`
- Views returned: `finance.transaction-fixes.index`, `finance.transaction-fixes.show`

#### `TransportFeeController`

- Path: `app/Http/Controllers/Finance/TransportFeeController.php`
- Finance: Transport Fee — resource-style controller
- Public methods (14): `index`, `bulkUpdate`, `recalculate`, `importPreview`, `importCommit`, `reverseImport`, `template`, `importHistory`, `showImport`, `importView`, `duplicatePreview`, `duplicateCommit`, `flatRate`, `duplicate`
- Views returned: `finance.transport_fees.duplicate_preview`, `finance.transport_fees.import`, `finance.transport_fees.import_details`, `finance.transport_fees.import_history`, `finance.transport_fees.import_preview`, `finance.transport_fees.index`

#### `VendorController`

- Path: `app/Http/Controllers/Finance/VendorController.php`
- Finance: Vendor — resource-style controller
- Public methods (5): `index`, `create`, `store`, `edit`, `update`
- Views returned: `finance.vendors.create`, `finance.vendors.edit`, `finance.vendors.index`

#### `VoteheadController`

- Path: `app/Http/Controllers/Finance/VoteheadController.php`
- Finance: Votehead — resource-style controller
- Public methods (9): `index`, `create`, `store`, `update`, `edit`, `destroy`, `import`, `processImport`, `downloadTemplate`
- Views returned: `finance.voteheads.create`, `finance.voteheads.edit`, `finance.voteheads.import`, `finance.voteheads.index`

### `Academics` (39)

#### `AcademicConfigController`

- Path: `app/Http/Controllers/Academics/AcademicConfigController.php`
- Academics: Academic Config — resource-style controller
- Public methods (14): `index`, `createYear`, `storeYear`, `updateYear`, `editYear`, `destroyYear`, `createTerm`, `editTerm`, `storeTerm`, `updateTerm`, `destroyTerm`, `termHolidays`, `storeTermHoliday`, `updateTermHoliday`
- Views returned: `settings.academic.create_term`, `settings.academic.create_year`, `settings.academic.edit_term`, `settings.academic.edit_year`, `settings.academic.index`, `settings.academic.term_holidays`

#### `AssessmentController`

- Path: `app/Http/Controllers/Academics/AssessmentController.php`
- Academics: Assessment — resource-style controller
- Public methods (3): `index`, `create`, `store`
- Views returned: `academics.assessments.create`, `academics.assessments.index`

#### `AssignTeachersController`

- Path: `app/Http/Controllers/Academics/AssignTeachersController.php`
- Academics: Assign Teachers — resource-style controller
- Public methods (4): `index`, `assignClassTeacher`, `assignAssistantClassTeacher`, `clearAllAssignments`
- Views returned: `academics.assign_teachers`

#### `BehaviourController`

- Path: `app/Http/Controllers/Academics/BehaviourController.php`
- Academics: Behaviour — resource-style controller
- Public methods (6): `index`, `create`, `store`, `edit`, `update`, `destroy`
- Views returned: `academics.behaviours.create`, `academics.behaviours.edit`, `academics.behaviours.index`

#### `CBCStrandController`

- Path: `app/Http/Controllers/Academics/CBCStrandController.php`
- Academics: C B C Strand — full resource CRUD
- Public methods (8): `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`, `substrands`
- Views returned: `academics.cbc_strands.create`, `academics.cbc_strands.edit`, `academics.cbc_strands.index`, `academics.cbc_strands.show`, `academics.cbc_strands.substrands`

#### `CBCSubstrandController`

- Path: `app/Http/Controllers/Academics/CBCSubstrandController.php`
- Academics: C B C Substrand — full resource CRUD
- Public methods (7): `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`
- Views returned: `academics.cbc_substrands.create`, `academics.cbc_substrands.edit`, `academics.cbc_substrands.index`, `academics.cbc_substrands.show`

#### `ClassroomController`

- Path: `app/Http/Controllers/Academics/ClassroomController.php`
- Academics: Classroom — resource-style controller
- Public methods (6): `index`, `create`, `store`, `edit`, `update`, `destroy`
- Views returned: `academics.classrooms.create`, `academics.classrooms.edit`, `academics.classrooms.index`

#### `CompetencyController`

- Path: `app/Http/Controllers/Academics/CompetencyController.php`
- Academics: Competency — full resource CRUD
- Public methods (9): `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`, `getBySubstrand`, `getByStrand`
- Views returned: `academics.competencies.create`, `academics.competencies.edit`, `academics.competencies.index`, `academics.competencies.show`

#### `CurriculumAssistantController`

- Path: `app/Http/Controllers/Academics/CurriculumAssistantController.php`
- Academics: Curriculum Assistant — action controller
- Public methods (2): `generate`, `chat`

#### `CurriculumDesignController`

- Path: `app/Http/Controllers/Academics/CurriculumDesignController.php`
- Academics: Curriculum Design — full resource CRUD
- Public methods (10): `index`, `create`, `store`, `show`, `review`, `edit`, `update`, `reprocess`, `destroy`, `progress`
- Views returned: `academics.curriculum_designs.create`, `academics.curriculum_designs.edit`, `academics.curriculum_designs.index`, `academics.curriculum_designs.review`, `academics.curriculum_designs.show`

#### `ExamAnalyticsController`

- Path: `app/Http/Controllers/Academics/ExamAnalyticsController.php`
- Academics: Exam Analytics — resource-style controller
- Public methods (2): `index`, `classroomPerformance`
- Views returned: `academics.exam_analytics.classroom`, `academics.exam_analytics.index`

#### `ExamClassroomGradingController`

- Path: `app/Http/Controllers/Academics/ExamClassroomGradingController.php`
- Academics: Exam Classroom Grading — resource-style controller
- Public methods (7): `index`, `edit`, `update`, `bulkForm`, `bulkApply`, `duplicateForm`, `duplicateScheme`
- Views returned: `academics.exams.grading.bulk`, `academics.exams.grading.duplicate`, `academics.exams.grading.edit`, `academics.exams.grading.index`

#### `ExamController`

- Path: `app/Http/Controllers/Academics/ExamController.php`
- Academics: Exam — full resource CRUD
- Public methods (13): `index`, `create`, `store`, `edit`, `update`, `destroy`, `bulkDestroy`, `timetable`, `show`, `createBulk`, `storeBulk`, `bulkUpdate`, `reopen`
- Views returned: `academics.exams.bulk_create`, `academics.exams.create`, `academics.exams.edit`, `academics.exams.index`, `academics.exams.show`, `academics.exams.timetable`

#### `ExamGradeController`

- Path: `app/Http/Controllers/Academics/ExamGradeController.php`
- Academics: Exam Grade — resource-style controller
- Public methods (6): `index`, `create`, `store`, `edit`, `update`, `destroy`
- Views returned: `academics.exam_grades.create`, `academics.exam_grades.edit`, `academics.exam_grades.index`

#### `ExamGroupController`

- Path: `app/Http/Controllers/Academics/ExamGroupController.php`
- Academics: Exam Group — resource-style controller
- Public methods (5): `index`, `store`, `edit`, `update`, `destroy`
- Views returned: `academics.exam_groups.edit`, `academics.exam_groups.index`

#### `ExamMarkController`

- Path: `app/Http/Controllers/Academics/ExamMarkController.php`
- Academics: Exam Mark — resource-style controller
- Public methods (12): `index`, `bulkForm`, `matrixEdit`, `matrixView`, `matrixStore`, `bulkEdit`, `bulkEditView`, `bulkStore`, `bulkDraftAutosave`, `submitExamMarks`, `edit`, `update`
- Views returned: `academics.exam_marks.bulk_edit`, `academics.exam_marks.bulk_form`, `academics.exam_marks.edit`, `academics.exam_marks.index`, `academics.exam_marks.matrix_edit`

#### `ExamPaperController`

- Path: `app/Http/Controllers/Academics/ExamPaperController.php`
- Academics: Exam Paper — resource-style controller
- Public methods (6): `index`, `create`, `store`, `edit`, `update`, `destroy`
- Views returned: `academics.exam_papers.create`, `academics.exam_papers.edit`, `academics.exam_papers.index`

#### `ExamPublishingController`

- Path: `app/Http/Controllers/Academics/ExamPublishingController.php`
- Academics: Exam Publishing — action controller
- Public methods (1): `publish`

#### `ExamReportsController`

- Path: `app/Http/Controllers/Academics/ExamReportsController.php`
- Academics: Exam Reports — action controller
- Public methods (7): `classSheet`, `exportClassSheet`, `exportClassSheetPdf`, `exportTermWorkbook`, `teacherPerformance`, `subjectPerformance`, `studentInsights`
- Views returned: `academics.exam_reports.class_sheet`, `academics.exam_reports.class_sheet_pdf`, `academics.exam_reports.student_insights`, `academics.exam_reports.subject_performance`, `academics.exam_reports.teacher_performance`

#### `ExamResultController`

- Path: `app/Http/Controllers/Academics/ExamResultController.php`
- Academics: Exam Result — resource-style controller
- Public methods (2): `index`, `bulkStore`
- Views returned: `academics.exam_results.index`

#### `ExamScheduleController`

- Path: `app/Http/Controllers/Academics/ExamScheduleController.php`
- Academics: Exam Schedule — resource-style controller
- Public methods (4): `index`, `store`, `update`, `destroy`
- Views returned: `academics.exam_schedules.index`

#### `ExamTypeController`

- Path: `app/Http/Controllers/Academics/ExamTypeController.php`
- Academics: Exam Type — resource-style controller
- Public methods (4): `index`, `store`, `update`, `destroy`
- Views returned: `academics.exam_types.index`

#### `ExtraCurricularActivityController`

- Path: `app/Http/Controllers/Academics/ExtraCurricularActivityController.php`
- Academics: Extra Curricular Activity — full resource CRUD
- Public methods (8): `index`, `create`, `store`, `show`, `edit`, `update`, `assignStudents`, `destroy`
- Views returned: `academics.extra_curricular_activities.create`, `academics.extra_curricular_activities.edit`, `academics.extra_curricular_activities.index`, `academics.extra_curricular_activities.show`

#### `HomeworkController`

- Path: `app/Http/Controllers/Academics/HomeworkController.php`
- Academics: Homework — full resource CRUD
- Public methods (7): `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`
- Views returned: `academics.homework.create`, `academics.homework.edit`, `academics.homework.index`, `academics.homework.show`

#### `HomeworkDiaryController`

- Path: `app/Http/Controllers/Academics/HomeworkDiaryController.php`
- Academics: Homework Diary — resource-style controller
- Public methods (7): `index`, `show`, `submitForm`, `submit`, `markForm`, `mark`, `updateSubmission`
- Views returned: `academics.homework_diary.index`, `academics.homework_diary.mark`, `academics.homework_diary.show`, `academics.homework_diary.submit`

#### `LearningAreaController`

- Path: `app/Http/Controllers/Academics/LearningAreaController.php`
- Academics: Learning Area — full resource CRUD
- Public methods (8): `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`, `getStrands`
- Views returned: `academics.learning_areas.create`, `academics.learning_areas.edit`, `academics.learning_areas.index`, `academics.learning_areas.show`

#### `LessonPlanController`

- Path: `app/Http/Controllers/Academics/LessonPlanController.php`
- Academics: Lesson Plan — full resource CRUD
- Public methods (16): `index`, `create`, `store`, `show`, `edit`, `update`, `approve`, `reject`, `reviewQueue`, `analytics`, `destroy`, `getSubstrands`, `assignHomeworkForm`, `assignHomework`, `exportPdf`, `exportExcel`
- Views returned: `academics.lesson_plans.analytics`, `academics.lesson_plans.assign_homework`, `academics.lesson_plans.create`, `academics.lesson_plans.edit`, `academics.lesson_plans.index`, `academics.lesson_plans.review_queue`, `academics.lesson_plans.show`

#### `PortfolioAssessmentController`

- Path: `app/Http/Controllers/Academics/PortfolioAssessmentController.php`
- Academics: Portfolio Assessment — full resource CRUD
- Public methods (7): `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`
- Views returned: `academics.portfolio_assessments.create`, `academics.portfolio_assessments.edit`, `academics.portfolio_assessments.index`, `academics.portfolio_assessments.show`

#### `ReportCardController`

- Path: `app/Http/Controllers/Academics/ReportCardController.php`
- Academics: Report Card — resource-style controller
- Public methods (15): `index`, `show`, `create`, `store`, `update`, `destroy`, `publish`, `bulkPublish`, `bulkPublishClass`, `bulkPublishFromFiltersNoNotify`, `termAssessment`, `exportPdf`, `bulkPrint`, `generateForm`, `generate`
- Views returned: `academics.assessments.term`, `academics.report_cards.bulk-print-pdf`, `academics.report_cards.create`, `academics.report_cards.generate`, `academics.report_cards.index`, `academics.report_cards.pdf`, `academics.report_cards.show`

#### `ReportCardSkillController`

- Path: `app/Http/Controllers/Academics/ReportCardSkillController.php`
- Academics: Report Card Skill — resource-style controller
- Public methods (6): `index`, `create`, `store`, `edit`, `update`, `destroy`
- Views returned: `academics.report_cards.skills.create`, `academics.report_cards.skills.edit`, `academics.report_cards.skills.index`

#### `SchemeOfWorkController`

- Path: `app/Http/Controllers/Academics/SchemeOfWorkController.php`
- Academics: Scheme Of Work — full resource CRUD
- Public methods (13): `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`, `approve`, `getStrands`, `generate`, `exportPdf`, `exportExcel`, `bulkExport`
- Views returned: `academics.schemes_of_work.create`, `academics.schemes_of_work.edit`, `academics.schemes_of_work.index`, `academics.schemes_of_work.show`

#### `StreamController`

- Path: `app/Http/Controllers/Academics/StreamController.php`
- Academics: Stream — resource-style controller
- Public methods (9): `index`, `create`, `store`, `edit`, `update`, `quickUpdate`, `quickUpdateClassroom`, `assignTeachers`, `destroy`
- Views returned: `academics.streams.create`, `academics.streams.edit`, `academics.streams.index`

#### `StudentBehaviourController`

- Path: `app/Http/Controllers/Academics/StudentBehaviourController.php`
- Academics: Student Behaviour — resource-style controller
- Public methods (4): `index`, `create`, `store`, `destroy`
- Views returned: `academics.student_behaviours.create`, `academics.student_behaviours.index`

#### `StudentDiaryController`

- Path: `app/Http/Controllers/Academics/StudentDiaryController.php`
- Academics: Student Diary — resource-style controller
- Public methods (5): `index`, `purgeOrphans`, `show`, `storeEntry`, `bulkStore`
- Views returned: `academics.diaries.index`, `academics.diaries.show`

#### `StudentPromotionController`

- Path: `app/Http/Controllers/Academics/StudentPromotionController.php`
- Academics: Student Promotion — resource-style controller
- Public methods (6): `index`, `show`, `promote`, `demote`, `alumni`, `promoteAll`
- Views returned: `academics.promotions.alumni`, `academics.promotions.index`, `academics.promotions.show`

#### `StudentSkillGradeController`

- Path: `app/Http/Controllers/Academics/StudentSkillGradeController.php`
- Academics: Student Skill Grade — resource-style controller
- Public methods (2): `index`, `store`
- Views returned: `academics.skills.grades.index`

#### `SubjectController`

- Path: `app/Http/Controllers/Academics/SubjectController.php`
- Academics: Subject — full resource CRUD
- Public methods (12): `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`, `generateCBCSubjects`, `teacherAssignments`, `saveTeacherAssignments`, `assignToClassrooms`, `updateLessonsPerWeek`
- Views returned: `academics.subjects.assign-teachers`, `academics.subjects.create`, `academics.subjects.edit`, `academics.subjects.index`, `academics.subjects.show`

#### `TeacherAssignmentController`

- Path: `app/Http/Controllers/Academics/TeacherAssignmentController.php`
- Academics: Teacher Assignment — resource-style controller
- Public methods (3): `index`, `edit`, `update`
- Views returned: `academics.teacher_assignments.edit`, `academics.teacher_assignments.index`

#### `TimetableController`

- Path: `app/Http/Controllers/Academics/TimetableController.php`
- Academics: Timetable — resource-style controller
- Public methods (22): `wholeSchool`, `wholeSchoolFeasibility`, `wholeSchoolGenerate`, `wholeSchoolPublish`, `wholeSchoolRunEditor`, `wholeSchoolRunUpdateSlot`, `wholeSchoolRunToggleLock`, `wholeSchoolRunRegenerateStream`, `wholeSchoolTeacherLoad`, `wholeSchoolSubstitutions`, `wholeSchoolSubstitutionsStore`, `wholeSchoolReplicate`, `wholeSchoolReplicateStore`, `index`, `classroom`, `edit`, `teacher`, `generate`, `save`, `duplicate`, `updatePeriod`, `checkConflicts`
- Views returned: `academics.timetable.classroom`, `academics.timetable.edit`, `academics.timetable.index`, `academics.timetable.preview`, `academics.timetable.replicate`, `academics.timetable.run_editor`, `academics.timetable.substitutions`, `academics.timetable.teacher`, `academics.timetable.teacher_load`, `academics.timetable.whole_school`

### `root` (39)

#### `ActivityLogController`

- Path: `app/Http/Controllers/ActivityLogController.php`
- core: Activity Log — resource-style controller
- Public methods (2): `index`, `show`
- Views returned: `activity-logs.index`, `activity-logs.show`

#### `AdminAlertController`

- Path: `app/Http/Controllers/AdminAlertController.php`
- core: Admin Alert — resource-style controller
- Public methods (3): `index`, `smsBalance`, `acknowledge`

#### `AppDownloadController`

- Path: `app/Http/Controllers/AppDownloadController.php`
- core: App Download — action controller
- Public methods (2): `playStore`, `apk`
- Views returned: `auth.open-play-store`

#### `AppOpsController`

- Path: `app/Http/Controllers/AppOpsController.php`
- core: App Ops — action controller
- Public methods (2): `adoption`, `issues`
- Views returned: `communication.app_adoption`, `communication.app_issues`

#### `AuthController`

- Path: `app/Http/Controllers/AuthController.php`
- core: Auth — action controller
- Public methods (9): `showLoginForm`, `login`, `logout`, `showLinkRequestForm`, `sendResetLinkEmail`, `showResetForm`, `showOTPResetForm`, `resetWithOTP`, `reset`
- Views returned: `auth.login`, `auth.passwords.email`, `auth.passwords.reset`, `auth.passwords.reset-otp`

#### `BackupRestoreController`

- Path: `app/Http/Controllers/BackupRestoreController.php`
- core: Backup Restore — resource-style controller
- Public methods (6): `index`, `create`, `purgeAll`, `download`, `restore`, `updateSchedule`
- Views returned: `backup_restore.index`

#### `CommunicationAnnouncementController`

- Path: `app/Http/Controllers/CommunicationAnnouncementController.php`
- core: Communication Announcement — resource-style controller
- Public methods (6): `index`, `store`, `updateAnnouncement`, `create`, `editAnnouncement`, `destroy`
- Views returned: `communication.announcements.create`, `communication.announcements.edit`, `communication.announcements.index`

#### `CommunicationController`

- Path: `app/Http/Controllers/CommunicationController.php`
- core: Communication — action controller
- Public methods (28): `compose`, `createEmail`, `sendEmail`, `createSMS`, `sendSMS`, `createWhatsApp`, `sendWhatsApp`, `smsProgress`, `emailProgress`, `whatsappProgress`, `retryFailedWhatsApp`, `preview`, `conversations`, `logs`, `logsScheduled`, `queues`, `resumePausedCommunications`, `deliveryReportsIndex`, `deliveryReport`, `smsDlrUpload`, `smsDlrProcess`, `smsDeliveryReport`, `pendingJobs`, `showCommunicationJob`, `pauseCommunicationJob`, `resumeCommunicationJob`, `cancelJob`, `sendJobImmediately`
- Views returned: `communication.bulk-progress`, `communication.compose`, `communication.conversations`, `communication.delivery-report`, `communication.delivery-reports-index`, `communication.job-show`, `communication.logs`, `communication.pending-jobs`, `communication.preview`, `communication.send_email`, `communication.send_sms`, `communication.send_whatsapp`, `communication.sms-dlr-report`, `communication.sms-dlr-upload`, `communication.whatsapp-progress`

#### `CommunicationDocumentController`

- Path: `app/Http/Controllers/CommunicationDocumentController.php`
- core: Communication Document — action controller
- Public methods (1): `send`

#### `CommunicationNoteController`

- Path: `app/Http/Controllers/CommunicationNoteController.php`
- core: Communication Note — action controller
- Public methods (2): `create`, `printNotes`
- Views returned: `communication.notes.create`, `communication.notes.print`

#### `CommunicationTemplateController`

- Path: `app/Http/Controllers/CommunicationTemplateController.php`
- core: Communication Template — resource-style controller
- Public methods (6): `index`, `create`, `store`, `edit`, `update`, `destroy`
- Views returned: `communication.templates.create`, `communication.templates.edit`, `communication.templates.index`

#### `Controller`

- Path: `app/Http/Controllers/Controller.php`
- core:  — action controller
- Public methods (0): _none_

#### `DashboardController`

- Path: `app/Http/Controllers/DashboardController.php`
- core: Dashboard — action controller
- Public methods (7): `adminDashboard`, `teacherDashboard`, `supervisorDashboard`, `parentDashboard`, `studentDashboard`, `financeDashboard`, `transportDashboard`
- Views returned: `dashboard.admin`, `dashboard.finance`, `dashboard.parent`, `dashboard.student`, `dashboard.supervisor`, `dashboard.teacher`, `dashboard.transport`

#### `DirectoryExportController`

- Path: `app/Http/Controllers/DirectoryExportController.php`
- core: Directory Export — action controller
- Public methods (2): `exportStudents`, `exportStaff`

#### `DocumentManagementController`

- Path: `app/Http/Controllers/DocumentManagementController.php`
- core: Document Management — resource-style controller
- Public methods (9): `index`, `create`, `store`, `show`, `download`, `preview`, `email`, `updateVersion`, `destroy`
- Views returned: `documents.create`, `documents.index`, `documents.show`

#### `DocumentTemplateController`

- Path: `app/Http/Controllers/DocumentTemplateController.php`
- core: Document Template — full resource CRUD
- Public methods (10): `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`, `preview`, `generateForStudent`, `generateForStaff`
- Views returned: `documents.templates.create`, `documents.templates.edit`, `documents.templates.index`, `documents.templates.show`

#### `DropOffPointController`

- Path: `app/Http/Controllers/DropOffPointController.php`
- core: Drop Off Point — full resource CRUD
- Public methods (11): `index`, `store`, `update`, `destroy`, `import`, `create`, `show`, `edit`, `importForm`, `resolve`, `template`
- Views returned: `dropoffpoints.create`, `dropoffpoints.edit`, `dropoffpoints.import`, `dropoffpoints.index`, `dropoffpoints.show`

#### `EventCalendarController`

- Path: `app/Http/Controllers/EventCalendarController.php`
- core: Event Calendar — full resource CRUD
- Public methods (8): `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`, `api`
- Views returned: `events.calendar`, `events.create`, `events.edit`, `events.show`

#### `FamilyReportPortalController`

- Path: `app/Http/Controllers/FamilyReportPortalController.php`
- core: Family Report Portal — action controller
- Public methods (4): `portal`, `show`, `pdf`, `legacyPublic`
- Views returned: `academics.report_cards.pdf`, `academics.report_cards.public_locked`, `family.reports.portal`, `family.reports.show`

#### `FeeReminderAutomationController`

- Path: `app/Http/Controllers/FeeReminderAutomationController.php`
- core: Fee Reminder Automation — resource-style controller
- Public methods (2): `edit`, `update`
- Views returned: `communication.fee_reminder_automation`

#### `FileDownloadController`

- Path: `app/Http/Controllers/FileDownloadController.php`
- core: File Download — read-heavy controller
- Public methods (1): `show`

#### `GalleryController`

- Path: `app/Http/Controllers/GalleryController.php`
- core: Gallery — resource-style controller
- Public methods (4): `index`, `upload`, `destroy`, `reorder`
- Views returned: `gallery.index`

#### `GeneratedDocumentController`

- Path: `app/Http/Controllers/GeneratedDocumentController.php`
- core: Generated Document — resource-style controller
- Public methods (4): `index`, `show`, `download`, `destroy`
- Views returned: `documents.generated.index`, `documents.generated.show`

#### `HomeController`

- Path: `app/Http/Controllers/HomeController.php`
- core: Home — resource-style controller
- Public methods (1): `index`
- Views returned: `home`

#### `MediaController`

- Path: `app/Http/Controllers/MediaController.php`
- core: Media — action controller
- Public methods (1): `signedRedirect`

#### `ParentNotificationBlockController`

- Path: `app/Http/Controllers/ParentNotificationBlockController.php`
- core: Parent Notification Block — resource-style controller
- Public methods (5): `index`, `create`, `store`, `edit`, `update`
- Views returned: `communication.parent_notification_blocks.form`, `communication.parent_notification_blocks.index`

#### `PaymentWebhookController`

- Path: `app/Http/Controllers/PaymentWebhookController.php`
- core: Payment Webhook — action controller
- Public methods (3): `handleMpesa`, `handleStripe`, `handlePaypal`

#### `PlaceholderController`

- Path: `app/Http/Controllers/PlaceholderController.php`
- core: Placeholder — resource-style controller
- Public methods (6): `index`, `create`, `store`, `edit`, `update`, `destroy`
- Views returned: `settings.placeholders.create`, `settings.placeholders.edit`, `settings.placeholders.index`

#### `SearchController`

- Path: `app/Http/Controllers/SearchController.php`
- core: Search — action controller
- Public methods (1): `suggest`

#### `SmsLogController`

- Path: `app/Http/Controllers/SmsLogController.php`
- core: Sms Log — resource-style controller
- Public methods (2): `index`, `store`
- Views returned: `sms_logs.index`

#### `SocialAuthController`

- Path: `app/Http/Controllers/SocialAuthController.php`
- core: Social Auth — action controller
- Public methods (2): `redirectToGoogle`, `handleGoogleCallback`

#### `StudentAssignmentController`

- Path: `app/Http/Controllers/StudentAssignmentController.php`
- core: Student Assignment — full resource CRUD
- Public methods (11): `index`, `search`, `quoteAmount`, `create`, `store`, `edit`, `update`, `show`, `destroy`, `bulkAssign`, `bulkAssignStore`
- Views returned: `student_assignments.index`, `student_assignments.show`

#### `StudentDropOffController`

- Path: `app/Http/Controllers/StudentDropOffController.php`
- core: Student Drop Off — resource-style controller
- Public methods (2): `index`, `update`

#### `SystemLogController`

- Path: `app/Http/Controllers/SystemLogController.php`
- core: System Log — resource-style controller
- Public methods (3): `index`, `clear`, `download`
- Views returned: `system-logs.index`

#### `TransportController`

- Path: `app/Http/Controllers/TransportController.php`
- core: Transport — resource-style controller
- Public methods (2): `index`, `assignDriver`
- Views returned: `transport.index`

#### `TripController`

- Path: `app/Http/Controllers/TripController.php`
- core: Trip — resource-style controller
- Public methods (13): `index`, `create`, `store`, `edit`, `update`, `destroy`, `assign`, `assignSearch`, `assignSuggest`, `assignStore`, `assignUpdatePoints`, `assignDuplicateLeg`, `unassign`
- Views returned: `trips.assign`, `trips.create`, `trips.edit`, `trips.index`

#### `VehicleController`

- Path: `app/Http/Controllers/VehicleController.php`
- core: Vehicle — resource-style controller
- Public methods (6): `index`, `create`, `store`, `edit`, `update`, `destroy`
- Views returned: `vehicles.create`, `vehicles.edit`, `vehicles.index`

#### `WasenderSessionController`

- Path: `app/Http/Controllers/WasenderSessionController.php`
- core: Wasender Session — resource-style controller
- Public methods (6): `index`, `store`, `connect`, `restart`, `destroy`, `updateSettings`
- Views returned: `communication.wasender_sessions`

#### `WhatsAppWebhookController`

- Path: `app/Http/Controllers/WhatsAppWebhookController.php`
- core: Whats App Webhook — action controller
- Public methods (2): `handleMeta`, `handle`

### `Website` (31)

#### `AdmissionApplicationController`

- Path: `app/Http/Controllers/Website/AdmissionApplicationController.php`
- Website: Admission Application — resource-style controller
- Public methods (5): `index`, `show`, `updateStatus`, `enroll`, `verifyDocument`
- Views returned: `website.admissions.index`, `website.admissions.show`

#### `AiContentController`

- Path: `app/Http/Controllers/Website/AiContentController.php`
- Website: Ai Content — resource-style controller
- Public methods (2): `index`, `generate`
- Views returned: `website.ai.index`

#### `AssistantKnowledgeController`

- Path: `app/Http/Controllers/Website/AssistantKnowledgeController.php`
- Website: Assistant Knowledge — resource-style controller
- Public methods (3): `index`, `store`, `destroy`
- Views returned: `website.assistant.index`

#### `BlogCategoryController`

- Path: `app/Http/Controllers/Website/BlogCategoryController.php`
- Website: Blog Category — resource-style controller
- Public methods (2): `index`, `store`
- Views returned: `website.blog_categories.index`

#### `BlogController`

- Path: `app/Http/Controllers/Website/BlogController.php`
- Website: Blog — resource-style controller
- Public methods (6): `index`, `create`, `store`, `edit`, `update`, `destroy`
- Views returned: `website.blogs.create`, `website.blogs.edit`, `website.blogs.index`

#### `BrandContentController`

- Path: `app/Http/Controllers/Website/BrandContentController.php`
- Website: Brand Content — resource-style controller
- Public methods (4): `index`, `store`, `update`, `destroy`
- Views returned: `website.brand.index`

#### `BrandIntelligenceController`

- Path: `app/Http/Controllers/Website/BrandIntelligenceController.php`
- Website: Brand Intelligence — resource-style controller
- Public methods (1): `index`
- Views returned: `website.brand.index`

#### `CampaignController`

- Path: `app/Http/Controllers/Website/CampaignController.php`
- Website: Campaign — resource-style controller
- Public methods (2): `index`, `store`
- Views returned: `website.campaigns.index`

#### `CommunityAdminController`

- Path: `app/Http/Controllers/Website/CommunityAdminController.php`
- Website: Community Admin — resource-style controller
- Public methods (6): `index`, `approvePrayer`, `featurePrayer`, `markPrayerAnswered`, `storeAlumni`, `storeFamilyStory`
- Views returned: `website.community.index`

#### `ContentAssistantController`

- Path: `app/Http/Controllers/Website/ContentAssistantController.php`
- Website: Content Assistant — action controller
- Public methods (1): `prompt`

#### `ContentCalendarController`

- Path: `app/Http/Controllers/Website/ContentCalendarController.php`
- Website: Content Calendar — resource-style controller
- Public methods (3): `index`, `store`, `update`
- Views returned: `website.calendar.index`

#### `ConversionManagerController`

- Path: `app/Http/Controllers/Website/ConversionManagerController.php`
- Website: Conversion Manager — resource-style controller
- Public methods (4): `index`, `storeCta`, `storeExitIntent`, `storeLeadMagnet`
- Views returned: `website.conversion.index`

#### `EnquiryController`

- Path: `app/Http/Controllers/Website/EnquiryController.php`
- Website: Enquiry — resource-style controller
- Public methods (3): `index`, `show`, `updateStatus`
- Views returned: `website.enquiries.index`, `website.enquiries.show`

#### `FaqController`

- Path: `app/Http/Controllers/Website/FaqController.php`
- Website: Faq — resource-style controller
- Public methods (4): `index`, `store`, `update`, `destroy`
- Views returned: `website.faqs.index`

#### `HomepageBuilderController`

- Path: `app/Http/Controllers/Website/HomepageBuilderController.php`
- Website: Homepage Builder — resource-style controller
- Public methods (5): `index`, `store`, `update`, `destroy`, `reorder`
- Views returned: `website.homepage.index`

#### `MediaAlbumController`

- Path: `app/Http/Controllers/Website/MediaAlbumController.php`
- Website: Media Album — resource-style controller
- Public methods (2): `index`, `store`
- Views returned: `website.albums.index`

#### `MediaLibraryController`

- Path: `app/Http/Controllers/Website/MediaLibraryController.php`
- Website: Media Library — resource-style controller
- Public methods (5): `index`, `store`, `destroy`, `updateQuality`, `optimize`
- Views returned: `website.media.index`

#### `MenuController`

- Path: `app/Http/Controllers/Website/MenuController.php`
- Website: Menu — resource-style controller
- Public methods (2): `index`, `store`
- Views returned: `website.menus.index`

#### `NewsletterController`

- Path: `app/Http/Controllers/Website/NewsletterController.php`
- Website: Newsletter — resource-style controller
- Public methods (1): `index`
- Views returned: `website.newsletter.index`

#### `PageController`

- Path: `app/Http/Controllers/Website/PageController.php`
- Website: Page — resource-style controller
- Public methods (8): `index`, `create`, `store`, `edit`, `update`, `clone`, `preview`, `destroy`
- Views returned: `website.pages.create`, `website.pages.edit`, `website.pages.index`, `website.pages.preview`

#### `ReusableBlockController`

- Path: `app/Http/Controllers/Website/ReusableBlockController.php`
- Website: Reusable Block — resource-style controller
- Public methods (2): `index`, `store`
- Views returned: `website.blocks.index`

#### `SchoolMealController`

- Path: `app/Http/Controllers/Website/SchoolMealController.php`
- Website: School Meal — resource-style controller
- Public methods (2): `index`, `store`
- Views returned: `website.meals.index`

#### `SeoDominanceController`

- Path: `app/Http/Controllers/Website/SeoDominanceController.php`
- Website: Seo Dominance — resource-style controller
- Public methods (4): `index`, `storeKeyword`, `updateArea`, `scorePage`
- Views returned: `website.seo.engine`

#### `SeoManagerController`

- Path: `app/Http/Controllers/Website/SeoManagerController.php`
- Website: Seo Manager — resource-style controller
- Public methods (3): `index`, `updateDefaults`, `updatePage`
- Views returned: `website.seo.index`

#### `StudentSpotlightController`

- Path: `app/Http/Controllers/Website/StudentSpotlightController.php`
- Website: Student Spotlight — resource-style controller
- Public methods (3): `index`, `storeSpotlight`, `storeCompetition`
- Views returned: `website.showcase.index`

#### `TestimonialController`

- Path: `app/Http/Controllers/Website/TestimonialController.php`
- Website: Testimonial — resource-style controller
- Public methods (4): `index`, `store`, `update`, `destroy`
- Views returned: `website.testimonials.index`

#### `VirtualTourController`

- Path: `app/Http/Controllers/Website/VirtualTourController.php`
- Website: Virtual Tour — resource-style controller
- Public methods (2): `index`, `store`
- Views returned: `website.virtual_tour.index`

#### `VisualPageBuilderController`

- Path: `app/Http/Controllers/Website/VisualPageBuilderController.php`
- Website: Visual Page Builder — action controller
- Public methods (10): `show`, `addSection`, `updateSection`, `cloneSection`, `toggleSection`, `destroySection`, `reorder`, `autosave`, `snapshot`, `restoreSnapshot`
- Views returned: `website.builder.show`

#### `WebsiteAnalyticsController`

- Path: `app/Http/Controllers/Website/WebsiteAnalyticsController.php`
- Website: Website Analytics — resource-style controller
- Public methods (1): `index`
- Views returned: `website.analytics.index`

#### `WebsiteEventController`

- Path: `app/Http/Controllers/Website/WebsiteEventController.php`
- Website: Website Event — resource-style controller
- Public methods (6): `index`, `create`, `store`, `edit`, `update`, `destroy`
- Views returned: `website.events.create`, `website.events.edit`, `website.events.index`

#### `WebsiteSettingController`

- Path: `app/Http/Controllers/Website/WebsiteSettingController.php`
- Website: Website Setting — resource-style controller
- Public methods (2): `edit`, `update`
- Views returned: `website.settings.edit`

### `Hr` (23)

#### `CustomDeductionController`

- Path: `app/Http/Controllers/Hr/CustomDeductionController.php`
- Hr: Custom Deduction — full resource CRUD
- Public methods (9): `index`, `create`, `store`, `show`, `edit`, `update`, `suspend`, `activate`, `destroy`
- Views returned: `hr.payroll.custom-deductions.create`, `hr.payroll.custom-deductions.edit`, `hr.payroll.custom-deductions.index`, `hr.payroll.custom-deductions.show`

#### `DeductionTypeController`

- Path: `app/Http/Controllers/Hr/DeductionTypeController.php`
- Hr: Deduction Type — full resource CRUD
- Public methods (7): `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`
- Views returned: `hr.payroll.deduction-types.create`, `hr.payroll.deduction-types.edit`, `hr.payroll.deduction-types.index`, `hr.payroll.deduction-types.show`

#### `HRAnalyticsController`

- Path: `app/Http/Controllers/Hr/HRAnalyticsController.php`
- Hr: H R Analytics — resource-style controller
- Public methods (1): `index`
- Views returned: `hr.analytics.index`

#### `LeaveRequestController`

- Path: `app/Http/Controllers/Hr/LeaveRequestController.php`
- Hr: Leave Request — resource-style controller
- Public methods (7): `index`, `create`, `store`, `show`, `approve`, `reject`, `cancel`
- Views returned: `staff.leave_requests.create`, `staff.leave_requests.index`, `staff.leave_requests.show`

#### `LeaveTypeController`

- Path: `app/Http/Controllers/Hr/LeaveTypeController.php`
- Hr: Leave Type — resource-style controller
- Public methods (6): `index`, `create`, `store`, `edit`, `update`, `destroy`
- Views returned: `staff.leave_types.create`, `staff.leave_types.edit`, `staff.leave_types.index`

#### `LookupController`

- Path: `app/Http/Controllers/Hr/LookupController.php`
- Hr: Lookup — resource-style controller
- Public methods (9): `index`, `storeCategory`, `deleteCategory`, `storeDepartment`, `deleteDepartment`, `storeJobTitle`, `deleteJobTitle`, `storeCustomField`, `deleteCustomField`
- Views returned: `lookups.index`

#### `PayrollExportsController`

- Path: `app/Http/Controllers/Hr/PayrollExportsController.php`
- Hr: Payroll Exports — action controller
- Public methods (5): `imbank`, `nssf`, `shif`, `kraPaye`, `mpesa`

#### `PayrollImportsController`

- Path: `app/Http/Controllers/Hr/PayrollImportsController.php`
- Hr: Payroll Imports — action controller
- Public methods (3): `budgetForm`, `budgetParse`, `budgetCommit`
- Views returned: `hr.payroll.imports.budget`, `hr.payroll.imports.budget_verify`

#### `PayrollPeriodController`

- Path: `app/Http/Controllers/Hr/PayrollPeriodController.php`
- Hr: Payroll Period — resource-style controller
- Public methods (9): `index`, `create`, `store`, `show`, `process`, `approve`, `recalculate`, `lock`, `markPaid`
- Views returned: `hr.payroll.periods.create`, `hr.payroll.periods.index`, `hr.payroll.periods.show`

#### `PayrollRecordController`

- Path: `app/Http/Controllers/Hr/PayrollRecordController.php`
- Hr: Payroll Record — resource-style controller
- Public methods (6): `index`, `exportExcel`, `exportPdf`, `show`, `update`, `cancel`
- Views returned: `hr.payroll.records.index`, `hr.payroll.records.pdf`, `hr.payroll.records.show`

#### `PayslipController`

- Path: `app/Http/Controllers/Hr/PayslipController.php`
- Hr: Payslip — action controller
- Public methods (4): `show`, `generate`, `download`, `staffPayslips`
- Views returned: `hr.payroll.payslips.pdf`, `hr.payroll.payslips.show`, `hr.payroll.payslips.staff`

#### `ProfileChangeController`

- Path: `app/Http/Controllers/Hr/ProfileChangeController.php`
- Hr: Profile Change — resource-style controller
- Public methods (5): `index`, `show`, `approve`, `reject`, `approveAll`
- Views returned: `hr.Profile_changes.index`, `hr.Profile_changes.show`

#### `PublicStaffRegistrationController`

- Path: `app/Http/Controllers/Hr/PublicStaffRegistrationController.php`
- Hr: Public Staff Registration — resource-style controller
- Public methods (2): `show`, `store`
- Views returned: `staff.registrations.public_form`

#### `RolePermissionController`

- Path: `app/Http/Controllers/Hr/RolePermissionController.php`
- Hr: Role Permission — resource-style controller
- Public methods (4): `accessAndLookups`, `listRoles`, `index`, `update`
- Views returned: `hr.access_lookups`, `hr.roles.edit`, `hr.roles.index`

#### `SalaryStructureController`

- Path: `app/Http/Controllers/Hr/SalaryStructureController.php`
- Hr: Salary Structure — full resource CRUD
- Public methods (7): `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`
- Views returned: `hr.payroll.salary-structures.create`, `hr.payroll.salary-structures.edit`, `hr.payroll.salary-structures.index`, `hr.payroll.salary-structures.show`

#### `StaffAdvanceController`

- Path: `app/Http/Controllers/Hr/StaffAdvanceController.php`
- Hr: Staff Advance — full resource CRUD
- Public methods (9): `index`, `create`, `store`, `show`, `edit`, `update`, `approve`, `recordRepayment`, `destroy`
- Views returned: `hr.payroll.advances.create`, `hr.payroll.advances.edit`, `hr.payroll.advances.index`, `hr.payroll.advances.show`

#### `StaffAttendanceController`

- Path: `app/Http/Controllers/Hr/StaffAttendanceController.php`
- Hr: Staff Attendance — resource-style controller
- Public methods (6): `index`, `mark`, `bulkMark`, `report`, `gateLogs`, `myReport`
- Views returned: `staff.attendance.gate-logs`, `staff.attendance.index`, `staff.attendance.my-report`, `staff.attendance.report`

#### `StaffController`

- Path: `app/Http/Controllers/Hr/StaffController.php`
- Hr: Staff — resource-style controller
- Public methods (17): `index`, `create`, `store`, `show`, `edit`, `update`, `showArchiveForm`, `archive`, `restore`, `showUploadForm`, `handleUpload`, `template`, `uploadParse`, `uploadCommit`, `resendCredentials`, `resetPassword`, `bulkAssignSupervisor`
- Views returned: `staff.archive`, `staff.create`, `staff.edit`, `staff.index`, `staff.show`, `staff.upload`, `staff.upload_verify`

#### `StaffDocumentController`

- Path: `app/Http/Controllers/Hr/StaffDocumentController.php`
- Hr: Staff Document — resource-style controller
- Public methods (6): `index`, `create`, `store`, `show`, `destroy`, `download`
- Views returned: `staff.documents.create`, `staff.documents.index`, `staff.documents.show`

#### `StaffLeaveBalanceController`

- Path: `app/Http/Controllers/Hr/StaffLeaveBalanceController.php`
- Hr: Staff Leave Balance — resource-style controller
- Public methods (5): `index`, `show`, `update`, `create`, `store`
- Views returned: `staff.leave_balances.create`, `staff.leave_balances.index`, `staff.leave_balances.show`

#### `StaffProfileController`

- Path: `app/Http/Controllers/Hr/StaffProfileController.php`
- Hr: Staff Profile — resource-style controller
- Public methods (2): `show`, `update`
- Views returned: `staff.profile`

#### `StaffRegistrationController`

- Path: `app/Http/Controllers/Hr/StaffRegistrationController.php`
- Hr: Staff Registration — resource-style controller
- Public methods (4): `index`, `show`, `approve`, `reject`
- Views returned: `staff.registrations.index`, `staff.registrations.show`

#### `StaffReportController`

- Path: `app/Http/Controllers/Hr/StaffReportController.php`
- Hr: Staff Report — resource-style controller
- Public methods (7): `index`, `exportDirectory`, `departmentReport`, `categoryReport`, `newHiresReport`, `terminationsReport`, `turnoverAnalysis`
- Views returned: `hr.reports.index`

### `Api/Website` (16)

#### `AdmissionApplicationApiController`

- Path: `app/Http/Controllers/Api/Website/AdmissionApplicationApiController.php`
- Api/Website: Admission Application Api — action controller
- Public methods (6): `options`, `start`, `saveStep`, `uploadDocument`, `submit`, `track`

#### `AiContentApiController`

- Path: `app/Http/Controllers/Api/Website/AiContentApiController.php`
- Api/Website: Ai Content Api — resource-style controller
- Public methods (3): `generate`, `show`, `index`

#### `CommunityApiController`

- Path: `app/Http/Controllers/Api/Website/CommunityApiController.php`
- Api/Website: Community Api — resource-style controller
- Public methods (3): `index`, `submitReferral`, `submitPrayer`

#### `ConversionApiController`

- Path: `app/Http/Controllers/Api/Website/ConversionApiController.php`
- Api/Website: Conversion Api — action controller
- Public methods (6): `ctas`, `trackCta`, `exitIntent`, `exitConvert`, `leadMagnets`, `downloadLeadMagnet`

#### `EventRegistrationApiController`

- Path: `app/Http/Controllers/Api/Website/EventRegistrationApiController.php`
- Api/Website: Event Registration Api — action controller
- Public methods (1): `register`

#### `LiveOperationsApiController`

- Path: `app/Http/Controllers/Api/Website/LiveOperationsApiController.php`
- Api/Website: Live Operations Api — action controller
- Public methods (4): `dashboard`, `noticeboard`, `schoolStatus`, `meals`

#### `NewsletterApiController`

- Path: `app/Http/Controllers/Api/Website/NewsletterApiController.php`
- Api/Website: Newsletter Api — action controller
- Public methods (1): `subscribe`

#### `ParentPortalApiController`

- Path: `app/Http/Controllers/Api/Website/ParentPortalApiController.php`
- Api/Website: Parent Portal Api — action controller
- Public methods (8): `dashboard`, `children`, `child`, `statement`, `attendance`, `reportCards`, `announcements`, `paymentLink`

#### `SchoolAssistantApiController`

- Path: `app/Http/Controllers/Api/Website/SchoolAssistantApiController.php`
- Api/Website: School Assistant Api — action controller
- Public methods (1): `chat`

#### `SeoDominanceApiController`

- Path: `app/Http/Controllers/Api/Website/SeoDominanceApiController.php`
- Api/Website: Seo Dominance Api — action controller
- Public methods (4): `schema`, `score`, `localAreas`, `area`

#### `StudentShowcaseApiController`

- Path: `app/Http/Controllers/Api/Website/StudentShowcaseApiController.php`
- Api/Website: Student Showcase Api — resource-style controller
- Public methods (1): `index`

#### `WebsiteAnalyticsApiController`

- Path: `app/Http/Controllers/Api/Website/WebsiteAnalyticsApiController.php`
- Api/Website: Website Analytics Api — action controller
- Public methods (2): `trackView`, `trackEvent`

#### `WebsiteApiController`

- Path: `app/Http/Controllers/Api/Website/WebsiteApiController.php`
- Api/Website: Website Api — action controller
- Public methods (13): `settings`, `homepage`, `page`, `blogs`, `blog`, `searchBlogs`, `events`, `event`, `testimonials`, `gallery`, `heroMedia`, `faqs`, `enquiry`

#### `WebsiteBrandApiController`

- Path: `app/Http/Controllers/Api/Website/WebsiteBrandApiController.php`
- Api/Website: Website Brand Api — resource-style controller
- Public methods (1): `index`

#### `WebsiteMediaApiController`

- Path: `app/Http/Controllers/Api/Website/WebsiteMediaApiController.php`
- Api/Website: Website Media Api — action controller
- Public methods (2): `albums`, `virtualTour`

#### `WebsiteSeoController`

- Path: `app/Http/Controllers/Api/Website/WebsiteSeoController.php`
- Api/Website: Website Seo — action controller
- Public methods (2): `sitemap`, `robots`

### `Students` (15)

#### `AcademicHistoryController`

- Path: `app/Http/Controllers/Students/AcademicHistoryController.php`
- Students: Academic History — full resource CRUD
- Public methods (7): `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`
- Views returned: `students.records.academic.create`, `students.records.academic.edit`, `students.records.academic.index`, `students.records.academic.show`

#### `DisciplinaryRecordController`

- Path: `app/Http/Controllers/Students/DisciplinaryRecordController.php`
- Students: Disciplinary Record — full resource CRUD
- Public methods (7): `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`
- Views returned: `students.records.disciplinary.create`, `students.records.disciplinary.edit`, `students.records.disciplinary.index`, `students.records.disciplinary.show`

#### `EnrollmentReportController`

- Path: `app/Http/Controllers/Students/EnrollmentReportController.php`
- Students: Enrollment Report — resource-style controller
- Public methods (3): `index`, `exportExcel`, `exportPdf`
- Views returned: `students.enrollment_report`

#### `ExtracurricularActivityController`

- Path: `app/Http/Controllers/Students/ExtracurricularActivityController.php`
- Students: Extracurricular Activity — full resource CRUD
- Public methods (7): `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`
- Views returned: `students.records.activities.create`, `students.records.activities.edit`, `students.records.activities.index`, `students.records.activities.show`

#### `FamilyController`

- Path: `app/Http/Controllers/Students/FamilyController.php`
- Students: Family — resource-style controller
- Public methods (11): `populatePreview`, `populateFromPreview`, `index`, `link`, `linkStudents`, `manage`, `update`, `attachMember`, `detachMember`, `destroy`, `bulkDestroy`
- Views returned: `families.index`, `families.link`, `families.manage`, `families.populate-preview`

#### `FamilyIntegrityReportController`

- Path: `app/Http/Controllers/Students/FamilyIntegrityReportController.php`
- Students: Family Integrity Report — resource-style controller
- Public methods (3): `index`, `missingContacts`, `quickUpdateParentPhones`
- Views returned: `families.integrity_missing_contacts`, `families.integrity_report`

#### `FamilyUpdateController`

- Path: `app/Http/Controllers/Students/FamilyUpdateController.php`
- Students: Family Update — action controller
- Public methods (8): `publicFilePreview`, `publicFileDownload`, `adminIndex`, `resetAll`, `showLink`, `reset`, `publicForm`, `submit`
- Views returned: `family_update.admin.index`, `family_update.public_form`

#### `MedicalRecordController`

- Path: `app/Http/Controllers/Students/MedicalRecordController.php`
- Students: Medical Record — full resource CRUD
- Public methods (7): `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`
- Views returned: `students.records.medical.create`, `students.records.medical.edit`, `students.records.medical.index`, `students.records.medical.show`

#### `OnlineAdmissionController`

- Path: `app/Http/Controllers/Students/OnlineAdmissionController.php`
- Students: Online Admission — resource-style controller
- Public methods (10): `index`, `showPublicForm`, `storePublicApplication`, `show`, `updateStatus`, `addToWaitlist`, `approve`, `transferFromWaitlist`, `reject`, `destroy`
- Views returned: `online_admissions.index`, `online_admissions.public_form`, `online_admissions.show`

#### `ParentCredentialsController`

- Path: `app/Http/Controllers/Students/ParentCredentialsController.php`
- Students: Parent Credentials — action controller
- Public methods (2): `reset`, `requirePasswordChange`

#### `ParentCredentialsManageController`

- Path: `app/Http/Controllers/Students/ParentCredentialsManageController.php`
- Students: Parent Credentials Manage — resource-style controller
- Public methods (6): `index`, `send`, `bulkSend`, `resetPassword`, `sendPinHelp`, `assignForcedAction`
- Views returned: `parents.credentials.index`

#### `ParentInfoController`

- Path: `app/Http/Controllers/Students/ParentInfoController.php`
- Students: Parent Info — resource-style controller
- Public methods (6): `index`, `create`, `store`, `edit`, `update`, `destroy`
- Views returned: `parents.create`, `parents.edit`, `parents.index`

#### `StudentCategoryController`

- Path: `app/Http/Controllers/Students/StudentCategoryController.php`
- Students: Student Category — resource-style controller
- Public methods (6): `index`, `create`, `store`, `edit`, `update`, `destroy`
- Views returned: `student_categories.create`, `student_categories.edit`, `student_categories.index`

#### `StudentController`

- Path: `app/Http/Controllers/Students/StudentController.php`
- Students: Student — resource-style controller
- Public methods (30): `index`, `parentsContact`, `archived`, `alumni`, `detailsAjax`, `create`, `familyLinkPreview`, `store`, `edit`, `update`, `archive`, `restore`, `bulkForm`, `bulkTemplate`, `bulkParse`, `bulkImport`, `search`, `show`, `getStreams`, `bulkAssign`, `bulkAssignStreams`, `bulkAssignCategories`, `processBulkCategoryAssignment`, `processBulkStreamAssignment`, `bulkArchive`, `bulkRestore`, `updateImportForm`, `updateImportTemplate`, `updateImportPreview`, `updateImportProcess`
- Views returned: `students.alumni`, `students.archived`, `students.bulk`, `students.bulk_assign_categories`, `students.bulk_assign_streams`, `students.bulk_preview`, `students.category_change_preview`, `students.create`, `students.edit`, `students.index`, `students.parents_contact`, `students.partials.details_modal_content`, `students.show`, `students.update_import`, `students.update_import_preview`

#### `StudentDuplicateReportController`

- Path: `app/Http/Controllers/Students/StudentDuplicateReportController.php`
- Students: Student Duplicate Report — resource-style controller
- Public methods (2): `index`, `check`
- Views returned: `students.duplicate_report`

### `Inventory` (9)

#### `AcademicYearTermsController`

- Path: `app/Http/Controllers/Inventory/AcademicYearTermsController.php`
- Inventory: Academic Year Terms — resource-style controller
- Public methods (1): `index`

#### `InventoryItemController`

- Path: `app/Http/Controllers/Inventory/InventoryItemController.php`
- Inventory: Inventory Item — full resource CRUD
- Public methods (8): `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`, `adjustStock`
- Views returned: `inventory.items.create`, `inventory.items.edit`, `inventory.items.index`, `inventory.items.show`

#### `InventoryReceiptsReportController`

- Path: `app/Http/Controllers/Inventory/InventoryReceiptsReportController.php`
- Inventory: Inventory Receipts Report — resource-style controller
- Public methods (2): `index`, `csv`
- Views returned: `inventory.reports.receipts`

#### `RequirementTemplateAssignmentController`

- Path: `app/Http/Controllers/Inventory/RequirementTemplateAssignmentController.php`
- Inventory: Requirement Template Assignment — resource-style controller
- Public methods (6): `index`, `create`, `store`, `edit`, `update`, `destroy`
- Views returned: `inventory.requirement-template-assignments.create`, `inventory.requirement-template-assignments.edit`, `inventory.requirement-template-assignments.index`

#### `RequirementTemplateController`

- Path: `app/Http/Controllers/Inventory/RequirementTemplateController.php`
- Inventory: Requirement Template — resource-style controller
- Public methods (6): `index`, `create`, `store`, `edit`, `update`, `destroy`
- Views returned: `inventory.requirement-templates.create`, `inventory.requirement-templates.edit`, `inventory.requirement-templates.index`

#### `RequirementTypeController`

- Path: `app/Http/Controllers/Inventory/RequirementTypeController.php`
- Inventory: Requirement Type — resource-style controller
- Public methods (4): `index`, `store`, `update`, `destroy`
- Views returned: `inventory.requirement-types.index`

#### `RequirementsReportController`

- Path: `app/Http/Controllers/Inventory/RequirementsReportController.php`
- Inventory: Requirements Report — resource-style controller
- Public methods (2): `index`, `csv`
- Views returned: `inventory.reports.requirements`

#### `RequisitionController`

- Path: `app/Http/Controllers/Inventory/RequisitionController.php`
- Inventory: Requisition — resource-style controller
- Public methods (7): `index`, `create`, `store`, `show`, `approve`, `fulfill`, `reject`
- Views returned: `inventory.requisitions.create`, `inventory.requisitions.index`, `inventory.requisitions.show`

#### `StudentRequirementController`

- Path: `app/Http/Controllers/Inventory/StudentRequirementController.php`
- Inventory: Student Requirement — resource-style controller
- Public methods (8): `index`, `collectForm`, `loadStreams`, `loadStudents`, `loadStudentRequirements`, `collect`, `updateReceipt`, `show`
- Views returned: `inventory.student-requirements.collect`, `inventory.student-requirements.index`, `inventory.student-requirements.show`

### `Pos` (9)

#### `DiscountController`

- Path: `app/Http/Controllers/Pos/DiscountController.php`
- Pos: Discount — resource-style controller
- Public methods (6): `index`, `create`, `store`, `edit`, `update`, `destroy`
- Views returned: `pos.discounts.create`, `pos.discounts.edit`, `pos.discounts.index`

#### `OrderController`

- Path: `app/Http/Controllers/Pos/OrderController.php`
- Pos: Order — resource-style controller
- Public methods (5): `index`, `show`, `updateStatus`, `fulfillItem`, `cancel`
- Views returned: `pos.orders.index`, `pos.orders.show`

#### `PaymentController`

- Path: `app/Http/Controllers/Pos/PaymentController.php`
- Pos: Payment — action controller
- Public methods (3): `initiatePayment`, `paymentStatus`, `verifyPayment`
- Views returned: `pos.shop.payment-status`

#### `ProductController`

- Path: `app/Http/Controllers/Pos/ProductController.php`
- Pos: Product — full resource CRUD
- Public methods (10): `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`, `adjustStock`, `bulkImport`, `downloadTemplate`
- Views returned: `pos.products.create`, `pos.products.create-uniform`, `pos.products.edit`, `pos.products.index`, `pos.products.show`

#### `ProductVariantController`

- Path: `app/Http/Controllers/Pos/ProductVariantController.php`
- Pos: Product Variant — resource-style controller
- Public methods (4): `index`, `store`, `update`, `destroy`

#### `PublicShopController`

- Path: `app/Http/Controllers/Pos/PublicShopController.php`
- Pos: Public Shop — action controller
- Public methods (9): `shop`, `addToCart`, `updateCart`, `removeFromCart`, `getCart`, `applyDiscount`, `checkout`, `processCheckout`, `orderConfirmation`
- Views returned: `pos.shop.checkout`, `pos.shop.index`, `pos.shop.order-confirmation`

#### `PublicShopLinkController`

- Path: `app/Http/Controllers/Pos/PublicShopLinkController.php`
- Pos: Public Shop Link — resource-style controller
- Public methods (7): `index`, `create`, `store`, `edit`, `update`, `destroy`, `regenerateToken`
- Views returned: `pos.public-links.create`, `pos.public-links.edit`, `pos.public-links.index`

#### `TeacherRequirementsController`

- Path: `app/Http/Controllers/Pos/TeacherRequirementsController.php`
- Pos: Teacher Requirements — resource-style controller
- Public methods (3): `index`, `markReceived`, `show`
- Views returned: `pos.teacher-requirements.index`, `pos.teacher-requirements.show`

#### `UniformController`

- Path: `app/Http/Controllers/Pos/UniformController.php`
- Pos: Uniform — resource-style controller
- Public methods (6): `index`, `show`, `manageSizes`, `updateSizeStock`, `backorders`, `fulfillBackorder`
- Views returned: `pos.uniforms.backorders`, `pos.uniforms.index`, `pos.uniforms.manage-sizes`, `pos.uniforms.show`

### `Reports` (7)

#### `ClassReportController`

- Path: `app/Http/Controllers/Reports/ClassReportController.php`
- Reports: Class Report — resource-style controller
- Public methods (3): `index`, `create`, `store`
- Views returned: `reports.class_reports.create`, `reports.class_reports.index`

#### `HeatmapController`

- Path: `app/Http/Controllers/Reports/HeatmapController.php`
- Reports: Heatmap — read-heavy controller
- Public methods (1): `show`
- Views returned: `reports.heatmaps.show`

#### `OperationsFacilityController`

- Path: `app/Http/Controllers/Reports/OperationsFacilityController.php`
- Reports: Operations Facility — resource-style controller
- Public methods (3): `index`, `create`, `store`
- Views returned: `reports.operations_facilities.create`, `reports.operations_facilities.index`

#### `PhoneNormalizationReportController`

- Path: `app/Http/Controllers/Reports/PhoneNormalizationReportController.php`
- Reports: Phone Normalization Report — resource-style controller
- Public methods (1): `index`
- Views returned: `reports.phone-normalization.index`

#### `StaffWeeklyController`

- Path: `app/Http/Controllers/Reports/StaffWeeklyController.php`
- Reports: Staff Weekly — resource-style controller
- Public methods (3): `index`, `create`, `store`
- Views returned: `reports.staff_weekly.create`, `reports.staff_weekly.index`

#### `StudentFollowupController`

- Path: `app/Http/Controllers/Reports/StudentFollowupController.php`
- Reports: Student Followup — resource-style controller
- Public methods (3): `index`, `create`, `store`
- Views returned: `reports.student_followups.create`, `reports.student_followups.index`

#### `SubjectReportController`

- Path: `app/Http/Controllers/Reports/SubjectReportController.php`
- Reports: Subject Report — resource-style controller
- Public methods (3): `index`, `create`, `store`
- Views returned: `reports.subject_reports.create`, `reports.subject_reports.index`

### `Teacher` (6)

#### `AdvanceRequestController`

- Path: `app/Http/Controllers/Teacher/AdvanceRequestController.php`
- Teacher: Advance Request — resource-style controller
- Public methods (3): `index`, `create`, `store`
- Views returned: `teacher.advances.create`, `teacher.advances.index`

#### `FeeClearanceController`

- Path: `app/Http/Controllers/Teacher/FeeClearanceController.php`
- Teacher: Fee Clearance — resource-style controller
- Public methods (1): `index`
- Views returned: `teacher.fee_clearance.index`

#### `LeaveController`

- Path: `app/Http/Controllers/Teacher/LeaveController.php`
- Teacher: Leave — resource-style controller
- Public methods (5): `index`, `create`, `store`, `show`, `cancel`
- Views returned: `teacher.leave.create`, `teacher.leave.index`, `teacher.leave.show`

#### `SalaryController`

- Path: `app/Http/Controllers/Teacher/SalaryController.php`
- Teacher: Salary — resource-style controller
- Public methods (3): `index`, `payslip`, `downloadPayslip`
- Views returned: `hr.payroll.payslips.pdf`, `teacher.salary.index`, `teacher.salary.payslip`

#### `StudentsController`

- Path: `app/Http/Controllers/Teacher/StudentsController.php`
- Teacher: Students — resource-style controller
- Public methods (2): `index`, `show`
- Views returned: `teacher.students.index`, `teacher.students.show`

#### `TransportController`

- Path: `app/Http/Controllers/Teacher/TransportController.php`
- Teacher: Transport — resource-style controller
- Public methods (3): `index`, `show`, `transportSheet`
- Views returned: `teacher.transport.index`, `teacher.transport.sheet`, `teacher.transport.show`

### `Swimming` (5)

#### `SwimmingAttendanceController`

- Path: `app/Http/Controllers/Swimming/SwimmingAttendanceController.php`
- Swimming: Swimming Attendance — resource-style controller
- Public methods (6): `create`, `store`, `index`, `retryPayment`, `bulkRetryPayments`, `sendPaymentReminders`
- Views returned: `swimming.attendance.create`, `swimming.attendance.index`

#### `SwimmingPaymentController`

- Path: `app/Http/Controllers/Swimming/SwimmingPaymentController.php`
- Swimming: Swimming Payment — resource-style controller
- Public methods (6): `create`, `store`, `getSiblings`, `sendBalanceCommunication`, `bulkSendBalanceCommunications`, `getBulkSendProgress`
- Views returned: `swimming.payments.create`

#### `SwimmingReportController`

- Path: `app/Http/Controllers/Swimming/SwimmingReportController.php`
- Swimming: Swimming Report — action controller
- Public methods (4): `dailyAttendance`, `unpaidSessions`, `walletBalances`, `revenueVsSessions`
- Views returned: `swimming.reports.daily_attendance`, `swimming.reports.revenue_vs_sessions`, `swimming.reports.unpaid_sessions`, `swimming.reports.wallet_balances`

#### `SwimmingSettingsController`

- Path: `app/Http/Controllers/Swimming/SwimmingSettingsController.php`
- Swimming: Swimming Settings — resource-style controller
- Public methods (2): `index`, `update`
- Views returned: `swimming.settings.index`

#### `SwimmingWalletController`

- Path: `app/Http/Controllers/Swimming/SwimmingWalletController.php`
- Swimming: Swimming Wallet — resource-style controller
- Public methods (7): `index`, `show`, `adjust`, `creditFromOptionalFees`, `processUnpaidAttendance`, `unallocateSwimmingPayments`, `fixOrphanedCredits`
- Views returned: `swimming.wallets.index`, `swimming.wallets.show`

### `Transport` (5)

#### `DailyTransportListController`

- Path: `app/Http/Controllers/Transport/DailyTransportListController.php`
- Transport: Daily Transport List — resource-style controller
- Public methods (4): `index`, `downloadExcel`, `printList`, `printVehicle`
- Views returned: `transport.daily-list.index`, `transport.daily-list.print`, `transport.daily-list.print-vehicle`

#### `DriverChangeRequestController`

- Path: `app/Http/Controllers/Transport/DriverChangeRequestController.php`
- Transport: Driver Change Request — resource-style controller
- Public methods (5): `index`, `create`, `store`, `approve`, `reject`
- Views returned: `transport.driver_change_requests.create`, `transport.driver_change_requests.index`

#### `TransportImportController`

- Path: `app/Http/Controllers/Transport/TransportImportController.php`
- Transport: Transport Import — action controller
- Public methods (5): `importForm`, `preview`, `import`, `showLog`, `downloadTemplate`
- Views returned: `transport.import.form`, `transport.import.log`, `transport.import.preview`

#### `TransportSpecialAssignmentController`

- Path: `app/Http/Controllers/Transport/TransportSpecialAssignmentController.php`
- Transport: Transport Special Assignment — resource-style controller
- Public methods (6): `index`, `create`, `store`, `approve`, `reject`, `cancel`
- Views returned: `transport.special_assignments.create`, `transport.special_assignments.index`

#### `TripAttendanceController`

- Path: `app/Http/Controllers/Transport/TripAttendanceController.php`
- Transport: Trip Attendance — resource-style controller
- Public methods (3): `create`, `store`, `index`
- Views returned: `transport.trip_attendance.create`, `transport.trip_attendance.index`

### `Attendance` (3)

#### `AttendanceController`

- Path: `app/Http/Controllers/Attendance/AttendanceController.php`
- Attendance: Attendance — resource-style controller
- Public methods (14): `markForm`, `mark`, `index`, `reportForm`, `generateReport`, `records`, `atRiskStudents`, `consecutiveAbsences`, `studentAnalytics`, `updateConsecutiveCounts`, `notifyConsecutiveAbsences`, `edit`, `update`, `unmark`
- Views returned: `attendance.at_risk`, `attendance.consecutive_absences`, `attendance.edit`, `attendance.index`, `attendance.mark`, `attendance.report_form`, `attendance.reports`, `attendance.student_analytics`

#### `AttendanceNotificationController`

- Path: `app/Http/Controllers/Attendance/AttendanceNotificationController.php`
- Attendance: Attendance Notification — resource-style controller
- Public methods (8): `index`, `create`, `store`, `edit`, `update`, `destroy`, `notifyForm`, `sendNotify`
- Views returned: `attendance_notifications.create`, `attendance_notifications.edit`, `attendance_notifications.index`, `attendance_notifications.notify`

#### `AttendanceReasonCodeController`

- Path: `app/Http/Controllers/Attendance/AttendanceReasonCodeController.php`
- Attendance: Attendance Reason Code — resource-style controller
- Public methods (6): `index`, `create`, `store`, `edit`, `update`, `destroy`
- Views returned: `attendance.reason_codes.create`, `attendance.reason_codes.edit`, `attendance.reason_codes.index`

### `Library` (3)

#### `BookBorrowingController`

- Path: `app/Http/Controllers/Library/BookBorrowingController.php`
- Library: Book Borrowing — resource-style controller
- Public methods (6): `index`, `create`, `store`, `show`, `return`, `renew`
- Views returned: `library.borrowings.create`, `library.borrowings.index`, `library.borrowings.show`

#### `BookController`

- Path: `app/Http/Controllers/Library/BookController.php`
- Library: Book — resource-style controller
- Public methods (6): `index`, `create`, `store`, `show`, `edit`, `update`
- Views returned: `library.books.create`, `library.books.edit`, `library.books.index`, `library.books.show`

#### `LibraryCardController`

- Path: `app/Http/Controllers/Library/LibraryCardController.php`
- Library: Library Card — resource-style controller
- Public methods (5): `index`, `create`, `store`, `show`, `renew`
- Views returned: `library.cards.create`, `library.cards.index`, `library.cards.show`

### `Operations` (3)

#### `ConcernController`

- Path: `app/Http/Controllers/Operations/ConcernController.php`
- Operations: Concern — resource-style controller
- Public methods (5): `index`, `create`, `store`, `show`, `update`
- Views returned: `operations.concerns.create`, `operations.concerns.index`, `operations.concerns.show`

#### `FixedAssetController`

- Path: `app/Http/Controllers/Operations/FixedAssetController.php`
- Operations: Fixed Asset — resource-style controller
- Public methods (3): `index`, `create`, `store`
- Views returned: `operations.assets.create`, `operations.assets.index`

#### `VisitorLogController`

- Path: `app/Http/Controllers/Operations/VisitorLogController.php`
- Operations: Visitor Log — resource-style controller
- Public methods (4): `index`, `create`, `store`, `checkout`
- Views returned: `operations.visitors.create`, `operations.visitors.index`

### `Activities` (2)

#### `ActivityFeeController`

- Path: `app/Http/Controllers/Activities/ActivityFeeController.php`
- Activities: Activity Fee — resource-style controller
- Public methods (6): `index`, `show`, `printRoster`, `attendance`, `attendanceStore`, `records`
- Views returned: `activities.fees.attendance`, `activities.fees.index`, `activities.fees.records`, `activities.fees.roster_print`, `activities.fees.show`

#### `ParentActivityRequestController`

- Path: `app/Http/Controllers/Activities/ParentActivityRequestController.php`
- Activities: Parent Activity Request — resource-style controller
- Public methods (3): `index`, `approve`, `reject`
- Views returned: `activities.parent_requests.index`

### `Hostel` (2)

#### `HostelAllocationController`

- Path: `app/Http/Controllers/Hostel/HostelAllocationController.php`
- Hostel: Hostel Allocation — resource-style controller
- Public methods (5): `index`, `create`, `store`, `show`, `deallocate`
- Views returned: `hostel.allocations.create`, `hostel.allocations.index`, `hostel.allocations.show`

#### `HostelController`

- Path: `app/Http/Controllers/Hostel/HostelController.php`
- Hostel: Hostel — resource-style controller
- Public methods (6): `index`, `create`, `store`, `show`, `edit`, `update`
- Views returned: `hostel.hostels.create`, `hostel.hostels.edit`, `hostel.hostels.index`, `hostel.hostels.show`

### `Settings` (2)

#### `SchoolDayController`

- Path: `app/Http/Controllers/Settings/SchoolDayController.php`
- Settings: School Day — resource-style controller
- Public methods (4): `index`, `generateHolidays`, `store`, `destroy`
- Views returned: `settings.school_days.index`

#### `SettingController`

- Path: `app/Http/Controllers/Settings/SettingController.php`
- Settings: Setting — resource-style controller
- Public methods (13): `index`, `updateSettings`, `updateModules`, `updateFeatures`, `updateRegional`, `updateSystem`, `uploadMobileApk`, `updateBranding`, `updateIdSettings`, `placeholders`, `storePlaceholder`, `academicReports`, `updateAcademicReports`
- Views returned: `settings.academic_reports`, `settings.index`, `settings.partials.placeholders`

### `WebAuthn` (2)

#### `WebAuthnLoginController`

- Path: `app/Http/Controllers/WebAuthn/WebAuthnLoginController.php`
- WebAuthn: Web Authn Login — action controller
- Public methods (2): `options`, `login`

#### `WebAuthnRegisterController`

- Path: `app/Http/Controllers/WebAuthn/WebAuthnRegisterController.php`
- WebAuthn: Web Authn Register — action controller
- Public methods (2): `options`, `register`

### `Admin` (1)

#### `SeniorTeacherAssignmentController`

- Path: `app/Http/Controllers/Admin/SeniorTeacherAssignmentController.php`
- Admin: Senior Teacher Assignment — resource-style controller
- Public methods (4): `index`, `edit`, `updateCampus`, `updateClassrooms`
- Views returned: `admin.senior_teacher_assignments.edit`, `admin.senior_teacher_assignments.index`

### `Api/Concerns` (1)

#### `ResolvesDocumentStorage`

- Path: `app/Http/Controllers/Api/Concerns/ResolvesDocumentStorage.php`
- Api/Concerns: Resolves Document Storage — action controller
- Public methods (0): _none_

### `Auth` (1)

#### `ChangePasswordController`

- Path: `app/Http/Controllers/Auth/ChangePasswordController.php`
- Auth: Change Password — resource-style controller
- Public methods (2): `show`, `update`
- Views returned: `auth.passwords.change`

### `Driver` (1)

#### `DriverController`

- Path: `app/Http/Controllers/Driver/DriverController.php`
- Driver: Driver — resource-style controller
- Public methods (3): `index`, `showTrip`, `transportSheet`
- Views returned: `driver.index`, `driver.transport-sheet`, `driver.trip`

### `ParentPortal` (1)

#### `DiaryController`

- Path: `app/Http/Controllers/ParentPortal/DiaryController.php`
- ParentPortal: Diary — resource-style controller
- Public methods (3): `index`, `show`, `storeEntry`
- Views returned: `parent.diaries.index`, `parent.diaries.show`

### `SeniorTeacher` (1)

#### `SeniorTeacherController`

- Path: `app/Http/Controllers/SeniorTeacher/SeniorTeacherController.php`
- SeniorTeacher: Senior Teacher — action controller
- Public methods (6): `dashboard`, `supervisedClassrooms`, `supervisedStaff`, `students`, `studentShow`, `feeBalances`
- Views returned: `senior_teacher.dashboard`, `senior_teacher.fee_balances`, `senior_teacher.student_show`, `senior_teacher.students`, `senior_teacher.supervised_classrooms`, `senior_teacher.supervised_staff`

### `Users` (1)

#### `ForcePasswordChangeController`

- Path: `app/Http/Controllers/Users/ForcePasswordChangeController.php`
- Users: Force Password Change — resource-style controller
- Public methods (2): `index`, `store`
- Views returned: `users.force-password-change`
