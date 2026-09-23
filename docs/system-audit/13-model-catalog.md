# Model catalog (live inventory, 2026-09-22)

Total Eloquent models: **323** (Concerns excluded from unused analysis).
Table names are explicit `$table` when set, otherwise Laravel-style guess.
`ref_files` is how many other PHP files mention the class name (not a proof of runtime use).

## Counts by folder

| Folder | Models |
|---|---:|
| `root` | 208 |
| `Academics` | 49 |
| `Website` | 48 |
| `Pos` | 6 |
| `AcademicReports` | 5 |
| `Reports` | 5 |
| `Admissions` | 2 |

## Every model

### `root` (208)

| Model | Table | Refs | Path | Purpose |
|---|---|---:|---|---|
| `AcademicYear` | `academic_years` | 116 | `app/Models/AcademicYear.php` | core entity `Academic Year` (table `academic_years`) |
| `Account` | `accounts` | 101 | `app/Models/Account.php` | core entity `Account` (table `accounts`) |
| `AccountingBudget` | `accounting_budgets` | 4 | `app/Models/AccountingBudget.php` | core entity `Accounting Budget` (table `accounting_budgets`) |
| `AccountingBudgetLine` | `accounting_budget_lines` | 3 | `app/Models/AccountingBudgetLine.php` | core entity `Accounting Budget Line` (table `accounting_budget_lines`) |
| `ActivityFeeAttendance` | `activity_fee_attendances` | 4 | `app/Models/ActivityFeeAttendance.php` | core entity `Activity Fee Attendance` (table `activity_fee_attendances`) |
| `ActivityLog` | `activity_logs` | 23 | `app/Models/ActivityLog.php` | core entity `Activity Log` (table `activity_logs`) |
| `Admin` | `admins` | 144 | `app/Models/Admin.php` | core entity `Admin` (table `admins`) |
| `Announcement` | `announcements` | 16 | `app/Models/Announcement.php` | core entity `Announcement` (table `announcements`) |
| `AppClientIssue` | `app_client_issues` | 2 | `app/Models/AppClientIssue.php` | core entity `App Client Issue` (table `app_client_issues`) |
| `ArchiveAudit` | `archive_audits` | 4 | `app/Models/ArchiveAudit.php` | core entity `Archive Audit` (table `archive_audits`) |
| `AssessmentRubric` | `assessment_rubrics` | 3 | `app/Models/AssessmentRubric.php` | core entity `Assessment Rubric` (table `assessment_rubrics`) |
| `AssistantClassTeacherAssignment` | `assistant_class_teacher_assignments` | 6 | `app/Models/AssistantClassTeacherAssignment.php` | core entity `Assistant Class Teacher Assignment` (table `assistant_class_teacher_assignments`) |
| `Attendance` | `attendance` | 72 | `app/Models/Attendance.php` | core entity `Attendance` (table `attendance`) |
| `AttendanceReasonCode` | `attendance_reason_codes` | 8 | `app/Models/AttendanceReasonCode.php` | core entity `Attendance Reason Code` (table `attendance_reason_codes`) |
| `AttendanceRecipient` | `attendance_recipients` | 2 | `app/Models/AttendanceRecipient.php` | core entity `Attendance Recipient` (table `attendance_recipients`) |
| `AuditLog` | `audit_logs` | 12 | `app/Models/AuditLog.php` | core entity `Audit Log` (table `audit_logs`) |
| `BalanceBroughtForwardImport` | `balance_brought_forward_imports` | 1 | `app/Models/BalanceBroughtForwardImport.php` | core entity `Balance Brought Forward Import` (table `balance_brought_forward_imports`) |
| `BankAccount` | `bank_accounts` | 18 | `app/Models/BankAccount.php` | core entity `Bank Account` (table `bank_accounts`) |
| `BankStatementTransaction` | `bank_statement_transactions` | 31 | `app/Models/BankStatementTransaction.php` | core entity `Bank Statement Transaction` (table `bank_statement_transactions`) |
| `BioTimePunch` | `biotime_punches` | 2 | `app/Models/BioTimePunch.php` | core entity `Bio Time Punch` (table `biotime_punches`) |
| `Book` | `books` | 19 | `app/Models/Book.php` | core entity `Book` (table `books`) |
| `BookBorrowing` | `book_borrowings` | 8 | `app/Models/BookBorrowing.php` | core entity `Book Borrowing` (table `book_borrowings`) |
| `BookCopy` | `book_copies` | 7 | `app/Models/BookCopy.php` | core entity `Book Copy` (table `book_copies`) |
| `BookReservation` | `book_reservations` | 2 | `app/Models/BookReservation.php` | core entity `Book Reservation` (table `book_reservations`) |
| `CampusSeniorTeacher` | `campus_senior_teachers` | 3 | `app/Models/CampusSeniorTeacher.php` | core entity `Campus Senior Teacher` (table `campus_senior_teachers`) |
| `ClassTeacherAssignment` | `class_teacher_assignments` | 8 | `app/Models/ClassTeacherAssignment.php` | core entity `Class Teacher Assignment` (table `class_teacher_assignments`) |
| `CommunicationJob` | `communication_jobs` | 11 | `app/Models/CommunicationJob.php` | core entity `Communication Job` (table `communication_jobs`) |
| `CommunicationJobRecipient` | `communication_job_recipients` | 2 | `app/Models/CommunicationJobRecipient.php` | core entity `Communication Job Recipient` (table `communication_job_recipients`) |
| `CommunicationLog` | `communication_logs` | 24 | `app/Models/CommunicationLog.php` | core entity `Communication Log` (table `communication_logs`) |
| `CommunicationPlaceholder` | `communication_placeholders` | 2 | `app/Models/CommunicationPlaceholder.php` | core entity `Communication Placeholder` (table `communication_placeholders`) |
| `CommunicationTemplate` | `communication_templates` | 32 | `app/Models/CommunicationTemplate.php` | core entity `Communication Template` (table `communication_templates`) |
| `CreditDebitNoteImport` | `credit_debit_note_imports` | 2 | `app/Models/CreditDebitNoteImport.php` | core entity `Credit Debit Note Import` (table `credit_debit_note_imports`) |
| `CreditNote` | `credit_notes` | 15 | `app/Models/CreditNote.php` | core entity `Credit Note` (table `credit_notes`) |
| `CurriculumDesign` | `curriculum_designs` | 15 | `app/Models/CurriculumDesign.php` | core entity `Curriculum Design` (table `curriculum_designs`) |
| `CurriculumEmbedding` | `curriculum_embeddings` | 2 | `app/Models/CurriculumEmbedding.php` | core entity `Curriculum Embedding` (table `curriculum_embeddings`) |
| `CurriculumExtractionAudit` | `curriculum_extraction_audits` | 1 | `app/Models/CurriculumExtractionAudit.php` | core entity `Curriculum Extraction Audit` (table `curriculum_extraction_audits`) |
| `CurriculumPage` | `curriculum_pages` | 3 | `app/Models/CurriculumPage.php` | core entity `Curriculum Page` (table `curriculum_pages`) |
| `CustomDeduction` | `custom_deductions` | 8 | `app/Models/CustomDeduction.php` | core entity `Custom Deduction` (table `custom_deductions`) |
| `CustomField` | `custom_fields` | 5 | `app/Models/CustomField.php` | core entity `Custom Field` (table `custom_fields`) |
| `CustomPlaceholder` | `custom_placeholders` | 6 | `app/Models/CustomPlaceholder.php` | core entity `Custom Placeholder` (table `custom_placeholders`) |
| `DebitNote` | `debit_notes` | 16 | `app/Models/DebitNote.php` | core entity `Debit Note` (table `debit_notes`) |
| `DeductionType` | `deduction_types` | 5 | `app/Models/DeductionType.php` | core entity `Deduction Type` (table `deduction_types`) |
| `Department` | `departments` | 18 | `app/Models/Department.php` | core entity `Department` (table `departments`) |
| `DiscountTemplate` | `discount_templates` | 2 | `app/Models/DiscountTemplate.php` | core entity `Discount Template` (table `discount_templates`) |
| `Document` | `documents` | 47 | `app/Models/Document.php` | core entity `Document` (table `documents`) |
| `DocumentCounter` | `document_counters` | 3 | `app/Models/DocumentCounter.php` | core entity `Document Counter` (table `document_counters`) |
| `DocumentTemplate` | `document_templates` | 5 | `app/Models/DocumentTemplate.php` | core entity `Document Template` (table `document_templates`) |
| `DriverChangeRequest` | `driver_change_requests` | 2 | `app/Models/DriverChangeRequest.php` | core entity `Driver Change Request` (table `driver_change_requests`) |
| `DropOffPoint` | `drop_off_points` | 35 | `app/Models/DropOffPoint.php` | core entity `Drop Off Point` (table `drop_off_points`) |
| `Event` | `events` | 31 | `app/Models/Event.php` | core entity `Event` (table `events`) |
| `Expense` | `expenses` | 64 | `app/Models/Expense.php` | core entity `Expense` (table `expenses`) |
| `ExpenseApproval` | `expense_approvals` | 5 | `app/Models/ExpenseApproval.php` | core entity `Expense Approval` (table `expense_approvals`) |
| `ExpenseAttachment` | `expense_attachments` | 2 | `app/Models/ExpenseAttachment.php` | core entity `Expense Attachment` (table `expense_attachments`) |
| `ExpenseCategory` | `expense_categories` | 25 | `app/Models/ExpenseCategory.php` | core entity `Expense Category` (table `expense_categories`) |
| `ExpenseLine` | `expense_lines` | 5 | `app/Models/ExpenseLine.php` | core entity `Expense Line` (table `expense_lines`) |
| `ExpensePayment` | `expense_payments` | 5 | `app/Models/ExpensePayment.php` | core entity `Expense Payment` (table `expense_payments`) |
| `ExpenseStatementImport` | `expense_statement_imports` | 10 | `app/Models/ExpenseStatementImport.php` | core entity `Expense Statement Import` (table `expense_statement_imports`) |
| `ExpenseStatementLine` | `expense_statement_lines` | 20 | `app/Models/ExpenseStatementLine.php` | core entity `Expense Statement Line` (table `expense_statement_lines`) |
| `ExpenseStatementRecipientProfile` | `expense_statement_recipient_profiles` | 3 | `app/Models/ExpenseStatementRecipientProfile.php` | core entity `Expense Statement Recipient Profile` (table `expense_statement_recipient_profiles`) |
| `Family` | `families` | 79 | `app/Models/Family.php` | core entity `Family` (table `families`) |
| `FamilyReceiptLink` | `family_receipt_links` | 5 | `app/Models/FamilyReceiptLink.php` | core entity `Family Receipt Link` (table `family_receipt_links`) |
| `FamilyReportPortalLink` | `family_report_portal_links` | 3 | `app/Models/FamilyReportPortalLink.php` | core entity `Family Report Portal Link` (table `family_report_portal_links`) |
| `FamilyUpdateAudit` | `family_update_audits` | 1 | `app/Models/FamilyUpdateAudit.php` | core entity `Family Update Audit` (table `family_update_audits`) |
| `FamilyUpdateLink` | `family_update_links` | 14 | `app/Models/FamilyUpdateLink.php` | core entity `Family Update Link` (table `family_update_links`) |
| `FeeCharge` | `fee_charges` | 12 | `app/Models/FeeCharge.php` | core entity `Fee Charge` (table `fee_charges`) |
| `FeeConcession` | `fee_concessions` | 14 | `app/Models/FeeConcession.php` | core entity `Fee Concession` (table `fee_concessions`) |
| `FeePaymentPlan` | `fee_payment_plans` | 18 | `app/Models/FeePaymentPlan.php` | core entity `Fee Payment Plan` (table `fee_payment_plans`) |
| `FeePaymentPlanInstallment` | `fee_payment_plan_installments` | 10 | `app/Models/FeePaymentPlanInstallment.php` | core entity `Fee Payment Plan Installment` (table `fee_payment_plan_installments`) |
| `FeePostingRun` | `fee_posting_runs` | 7 | `app/Models/FeePostingRun.php` | core entity `Fee Posting Run` (table `fee_posting_runs`) |
| `FeeReminder` | `fee_reminders` | 12 | `app/Models/FeeReminder.php` | core entity `Fee Reminder` (table `fee_reminders`) |
| `FeeStatement` | `fee_statements` | 8 | `app/Models/FeeStatement.php` | core entity `Fee Statement` (table `fee_statements`) |
| `FeeStructure` | `fee_structures` | 19 | `app/Models/FeeStructure.php` | core entity `Fee Structure` (table `fee_structures`) |
| `FeeStructureVersion` | `fee_structure_versions` | 1 | `app/Models/FeeStructureVersion.php` | core entity `Fee Structure Version` (table `fee_structure_versions`) |
| `FeesComparisonPreview` | `fees_comparison_previews` | 1 | `app/Models/FeesComparisonPreview.php` | core entity `Fees Comparison Preview` (table `fees_comparison_previews`) |
| `FiscalPeriod` | `fiscal_periods` | 6 | `app/Models/FiscalPeriod.php` | core entity `Fiscal Period` (table `fiscal_periods`) |
| `FixedAsset` | `fixed_assets` | 8 | `app/Models/FixedAsset.php` | core entity `Fixed Asset` (table `fixed_assets`) |
| `GalleryImage` | `gallery_images` | 2 | `app/Models/GalleryImage.php` | core entity `Gallery Image` (table `gallery_images`) |
| `GeneratedDocument` | `generated_documents` | 4 | `app/Models/GeneratedDocument.php` | core entity `Generated Document` (table `generated_documents`) |
| `Hostel` | `hostels` | 13 | `app/Models/Hostel.php` | core entity `Hostel` (table `hostels`) |
| `HostelAllocation` | `hostel_allocations` | 6 | `app/Models/HostelAllocation.php` | core entity `Hostel Allocation` (table `hostel_allocations`) |
| `HostelAttendance` | `hostel_attendance` | 0 | `app/Models/HostelAttendance.php` | core entity `Hostel Attendance` (table `hostel_attendance`) |
| `HostelFee` | `hostel_fees` | 1 | `app/Models/HostelFee.php` | core entity `Hostel Fee` (table `hostel_fees`) |
| `HostelRoom` | `hostel_rooms` | 5 | `app/Models/HostelRoom.php` | core entity `Hostel Room` (table `hostel_rooms`) |
| `InventoryItem` | `inventory_items` | 19 | `app/Models/InventoryItem.php` | core entity `Inventory Item` (table `inventory_items`) |
| `InventoryTransaction` | `inventory_transactions` | 11 | `app/Models/InventoryTransaction.php` | core entity `Inventory Transaction` (table `inventory_transactions`) |
| `InventoryType` | `inventory_types` | 1 | `app/Models/InventoryType.php` | core entity `Inventory Type` (table `inventory_types`) |
| `Invoice` | `invoices` | 136 | `app/Models/Invoice.php` | core entity `Invoice` (table `invoices`) |
| `InvoiceItem` | `invoice_items` | 53 | `app/Models/InvoiceItem.php` | core entity `Invoice Item` (table `invoice_items`) |
| `ItemReceipt` | `item_receipts` | 2 | `app/Models/ItemReceipt.php` | core entity `Item Receipt` (table `item_receipts`) |
| `JobTitle` | `job_titles` | 13 | `app/Models/JobTitle.php` | core entity `Job Title` (table `job_titles`) |
| `Journal` | `journals` | 21 | `app/Models/Journal.php` | core entity `Journal` (table `journals`) |
| `JournalEntry` | `journal_entries` | 11 | `app/Models/JournalEntry.php` | core entity `Journal Entry` (table `journal_entries`) |
| `JournalLine` | `journal_lines` | 6 | `app/Models/JournalLine.php` | core entity `Journal Line` (table `journal_lines`) |
| `KitchenRecipient` | `kitchen_recipients` | 0 | `app/Models/KitchenRecipient.php` | core entity `Kitchen Recipient` (table `kitchen_recipients`) |
| `LeaveRequest` | `leave_requests` | 17 | `app/Models/LeaveRequest.php` | core entity `Leave Request` (table `leave_requests`) |
| `LeaveType` | `leave_types` | 9 | `app/Models/LeaveType.php` | core entity `Leave Type` (table `leave_types`) |
| `LedgerPosting` | `ledger_postings` | 2 | `app/Models/LedgerPosting.php` | core entity `Ledger Posting` (table `ledger_postings`) |
| `LegacyFinanceImportBatch` | `legacy_finance_import_batchs` | 5 | `app/Models/LegacyFinanceImportBatch.php` | core entity `Legacy Finance Import Batch` (table `legacy_finance_import_batchs`) |
| `LegacyStatementLine` | `legacy_statement_lines` | 7 | `app/Models/LegacyStatementLine.php` | core entity `Legacy Statement Line` (table `legacy_statement_lines`) |
| `LegacyStatementLineEditHistory` | `legacy_statement_line_edit_history` | 3 | `app/Models/LegacyStatementLineEditHistory.php` | core entity `Legacy Statement Line Edit History` (table `legacy_statement_line_edit_history`) |
| `LegacyStatementTerm` | `legacy_statement_terms` | 13 | `app/Models/LegacyStatementTerm.php` | core entity `Legacy Statement Term` (table `legacy_statement_terms`) |
| `LibraryCard` | `library_cards` | 8 | `app/Models/LibraryCard.php` | core entity `Library Card` (table `library_cards`) |
| `LibraryFine` | `library_fines` | 1 | `app/Models/LibraryFine.php` | core entity `Library Fine` (table `library_fines`) |
| `ManualMatchLearning` | `manual_match_learnings` | 3 | `app/Models/ManualMatchLearning.php` | core entity `Manual Match Learning` (table `manual_match_learnings`) |
| `MessMenu` | `mess_menus` | 0 | `app/Models/MessMenu.php` | core entity `Mess Menu` (table `mess_menus`) |
| `MessSubscription` | `mess_subscriptions` | 0 | `app/Models/MessSubscription.php` | core entity `Mess Subscription` (table `mess_subscriptions`) |
| `MpesaC2BTransaction` | `mpesa_c2b_transactions` | 21 | `app/Models/MpesaC2BTransaction.php` | core entity `Mpesa C2 B Transaction` (table `mpesa_c2b_transactions`) |
| `OnlineAdmission` | `online_admissions` | 18 | `app/Models/OnlineAdmission.php` | core entity `Online Admission` (table `online_admissions`) |
| `OptionalFee` | `optional_fees` | 22 | `app/Models/OptionalFee.php` | core entity `Optional Fee` (table `optional_fees`) |
| `OptionalFeeImport` | `optional_fee_imports` | 2 | `app/Models/OptionalFeeImport.php` | core entity `Optional Fee Import` (table `optional_fee_imports`) |
| `OtpVerification` | `otp_verifications` | 1 | `app/Models/OtpVerification.php` | core entity `Otp Verification` (table `otp_verifications`) |
| `ParentActivityChangeRequest` | `parent_activity_change_requests` | 3 | `app/Models/ParentActivityChangeRequest.php` | core entity `Parent Activity Change Request` (table `parent_activity_change_requests`) |
| `ParentForcedAction` | `parent_forced_actions` | 8 | `app/Models/ParentForcedAction.php` | core entity `Parent Forced Action` (table `parent_forced_actions`) |
| `ParentInfo` | `parent_info` | 49 | `app/Models/ParentInfo.php` | core entity `Parent Info` (table `parent_info`) |
| `ParentWallet` | `parent_wallets` | 9 | `app/Models/ParentWallet.php` | core entity `Parent Wallet` (table `parent_wallets`) |
| `ParentWalletLedger` | `parent_wallet_ledger` | 2 | `app/Models/ParentWalletLedger.php` | core entity `Parent Wallet Ledger` (table `parent_wallet_ledger`) |
| `ParentWalletSavingPlan` | `parent_wallet_saving_plans` | 3 | `app/Models/ParentWalletSavingPlan.php` | core entity `Parent Wallet Saving Plan` (table `parent_wallet_saving_plans`) |
| `Payment` | `payments` | 178 | `app/Models/Payment.php` | core entity `Payment` (table `payments`) |
| `PaymentAllocation` | `payment_allocations` | 34 | `app/Models/PaymentAllocation.php` | core entity `Payment Allocation` (table `payment_allocations`) |
| `PaymentLink` | `payment_links` | 21 | `app/Models/PaymentLink.php` | core entity `Payment Link` (table `payment_links`) |
| `PaymentMethod` | `payment_methods` | 17 | `app/Models/PaymentMethod.php` | core entity `Payment Method` (table `payment_methods`) |
| `PaymentThreshold` | `payment_thresholds` | 6 | `app/Models/PaymentThreshold.php` | core entity `Payment Threshold` (table `payment_thresholds`) |
| `PaymentTransaction` | `payment_transactions` | 15 | `app/Models/PaymentTransaction.php` | core entity `Payment Transaction` (table `payment_transactions`) |
| `PaymentVoucher` | `payment_vouchers` | 7 | `app/Models/PaymentVoucher.php` | core entity `Payment Voucher` (table `payment_vouchers`) |
| `PaymentWebhook` | `payment_webhooks` | 2 | `app/Models/PaymentWebhook.php` | core entity `Payment Webhook` (table `payment_webhooks`) |
| `PayrollExport` | `payroll_exports` | 9 | `app/Models/PayrollExport.php` | core entity `Payroll Export` (table `payroll_exports`) |
| `PayrollPeriod` | `payroll_periods` | 17 | `app/Models/PayrollPeriod.php` | core entity `Payroll Period` (table `payroll_periods`) |
| `PayrollRecord` | `payroll_records` | 25 | `app/Models/PayrollRecord.php` | core entity `Payroll Record` (table `payroll_records`) |
| `PerformanceFeedback` | `performance_feedbacks` | 0 | `app/Models/PerformanceFeedback.php` | core entity `Performance Feedback` (table `performance_feedbacks`) |
| `PerformanceGoal` | `performance_goals` | 0 | `app/Models/PerformanceGoal.php` | core entity `Performance Goal` (table `performance_goals`) |
| `PerformanceReview` | `performance_reviews` | 1 | `app/Models/PerformanceReview.php` | core entity `Performance Review` (table `performance_reviews`) |
| `PettyCashFund` | `petty_cash_funds` | 6 | `app/Models/PettyCashFund.php` | core entity `Petty Cash Fund` (table `petty_cash_funds`) |
| `PettyCashVoucher` | `petty_cash_vouchers` | 5 | `app/Models/PettyCashVoucher.php` | core entity `Petty Cash Voucher` (table `petty_cash_vouchers`) |
| `PhoneNumberNormalizationLog` | `phone_number_normalization_logs` | 9 | `app/Models/PhoneNumberNormalizationLog.php` | core entity `Phone Number Normalization Log` (table `phone_number_normalization_logs`) |
| `PostingDiff` | `posting_diffs` | 2 | `app/Models/PostingDiff.php` | core entity `Posting Diff` (table `posting_diffs`) |
| `Receipt` | `receipts` | 48 | `app/Models/Receipt.php` | core entity `Receipt` (table `receipts`) |
| `RequirementTemplate` | `requirement_templates` | 19 | `app/Models/RequirementTemplate.php` | core entity `Requirement Template` (table `requirement_templates`) |
| `RequirementTemplateAssignment` | `requirement_template_assignments` | 4 | `app/Models/RequirementTemplateAssignment.php` | core entity `Requirement Template Assignment` (table `requirement_template_assignments`) |
| `RequirementType` | `requirement_types` | 12 | `app/Models/RequirementType.php` | core entity `Requirement Type` (table `requirement_types`) |
| `Requisition` | `requisitions` | 12 | `app/Models/Requisition.php` | core entity `Requisition` (table `requisitions`) |
| `RequisitionItem` | `requisition_items` | 6 | `app/Models/RequisitionItem.php` | core entity `Requisition Item` (table `requisition_items`) |
| `Route` | `routes` | 34 | `app/Models/Route.php` | core entity `Route` (table `routes`) |
| `SalaryHistory` | `salary_history` | 4 | `app/Models/SalaryHistory.php` | core entity `Salary History` (table `salary_history`) |
| `SalaryStructure` | `salary_structures` | 11 | `app/Models/SalaryStructure.php` | core entity `Salary Structure` (table `salary_structures`) |
| `ScheduledCommunication` | `scheduled_communications` | 5 | `app/Models/ScheduledCommunication.php` | core entity `Scheduled Communication` (table `scheduled_communications`) |
| `ScheduledFeeCommunication` | `scheduled_fee_communications` | 8 | `app/Models/ScheduledFeeCommunication.php` | core entity `Scheduled Fee Communication` (table `scheduled_fee_communications`) |
| `SchoolDay` | `school_days` | 20 | `app/Models/SchoolDay.php` | core entity `School Day` (table `school_days`) |
| `SchoolRegistry` | `schools_registry` | 4 | `app/Models/SchoolRegistry.php` | core entity `School Registry` (table `schools_registry`) |
| `SeniorTeacherClassroomAssignment` | `senior_teacher_classroom_assignments` | 3 | `app/Models/SeniorTeacherClassroomAssignment.php` | core entity `Senior Teacher Classroom Assignment` (table `senior_teacher_classroom_assignments`) |
| `Setting` | `settings` | 64 | `app/Models/Setting.php` | core entity `Setting` (table `settings`) |
| `SmsLog` | `sms_logs` | 3 | `app/Models/SmsLog.php` | core entity `Sms Log` (table `sms_logs`) |
| `Staff` | `staffs` | 209 | `app/Models/Staff.php` | core entity `Staff` (table `staffs`) |
| `StaffAdvance` | `staff_advances` | 12 | `app/Models/StaffAdvance.php` | core entity `Staff Advance` (table `staff_advances`) |
| `StaffAttendance` | `staff_attendance` | 12 | `app/Models/StaffAttendance.php` | core entity `Staff Attendance` (table `staff_attendance`) |
| `StaffCategory` | `staff_categories` | 14 | `app/Models/StaffCategory.php` | core entity `Staff Category` (table `staff_categories`) |
| `StaffCertification` | `staff_certifications` | 0 | `app/Models/StaffCertification.php` | core entity `Staff Certification` (table `staff_certifications`) |
| `StaffDocument` | `staff_documents` | 5 | `app/Models/StaffDocument.php` | core entity `Staff Document` (table `staff_documents`) |
| `StaffLeaveBalance` | `staff_leave_balances` | 9 | `app/Models/StaffLeaveBalance.php` | core entity `Staff Leave Balance` (table `staff_leave_balances`) |
| `StaffMeta` | `staff_metas` | 2 | `app/Models/StaffMeta.php` | core entity `Staff Meta` (table `staff_metas`) |
| `StaffProfileChange` | `staff_profile_changes` | 3 | `app/Models/StaffProfileChange.php` | core entity `Staff Profile Change` (table `staff_profile_changes`) |
| `StaffQualification` | `staff_qualifications` | 0 | `app/Models/StaffQualification.php` | core entity `Staff Qualification` (table `staff_qualifications`) |
| `StaffRegistration` | `staff_registrations` | 4 | `app/Models/StaffRegistration.php` | core entity `Staff Registration` (table `staff_registrations`) |
| `StaffSkill` | `staff_skills` | 0 | `app/Models/StaffSkill.php` | core entity `Staff Skill` (table `staff_skills`) |
| `StaffStatutoryExemption` | `staff_statutory_exemptions` | 3 | `app/Models/StaffStatutoryExemption.php` | core entity `Staff Statutory Exemption` (table `staff_statutory_exemptions`) |
| `StatementLink` | `statement_links` | 4 | `app/Models/StatementLink.php` | core entity `Statement Link` (table `statement_links`) |
| `StatutoryRuleset` | `statutory_rulesets` | 8 | `app/Models/StatutoryRuleset.php` | core entity `Statutory Ruleset` (table `statutory_rulesets`) |
| `Student` | `students` | 412 | `app/Models/Student.php` | core entity `Student` (table `students`) |
| `StudentAcademicHistory` | `student_academic_history` | 5 | `app/Models/StudentAcademicHistory.php` | core entity `Student Academic History` (table `student_academic_history`) |
| `StudentAssignment` | `student_assignments` | 28 | `app/Models/StudentAssignment.php` | core entity `Student Assignment` (table `student_assignments`) |
| `StudentCategory` | `student_categories` | 20 | `app/Models/StudentCategory.php` | core entity `Student Category` (table `student_categories`) |
| `StudentConcern` | `student_concerns` | 2 | `app/Models/StudentConcern.php` | core entity `Student Concern` (table `student_concerns`) |
| `StudentDailyPickup` | `student_daily_pickups` | 1 | `app/Models/StudentDailyPickup.php` | core entity `Student Daily Pickup` (table `student_daily_pickups`) |
| `StudentDisciplinaryRecord` | `student_disciplinary_records` | 3 | `app/Models/StudentDisciplinaryRecord.php` | core entity `Student Disciplinary Record` (table `student_disciplinary_records`) |
| `StudentExtracurricularActivity` | `student_extracurricular_activities` | 6 | `app/Models/StudentExtracurricularActivity.php` | core entity `Student Extracurricular Activity` (table `student_extracurricular_activities`) |
| `StudentMedicalRecord` | `student_medical_records` | 4 | `app/Models/StudentMedicalRecord.php` | core entity `Student Medical Record` (table `student_medical_records`) |
| `StudentRequirement` | `student_requirements` | 17 | `app/Models/StudentRequirement.php` | core entity `Student Requirement` (table `student_requirements`) |
| `StudentSibling` | `student_siblings` | 1 | `app/Models/StudentSibling.php` | core entity `Student Sibling` (table `student_siblings`) |
| `StudentTermFeeClearance` | `student_term_fee_clearances` | 7 | `app/Models/StudentTermFeeClearance.php` | core entity `Student Term Fee Clearance` (table `student_term_fee_clearances`) |
| `SuggestedExperience` | `suggested_experiences` | 3 | `app/Models/SuggestedExperience.php` | core entity `Suggested Experience` (table `suggested_experiences`) |
| `SwimmingAttendance` | `swimming_attendance` | 11 | `app/Models/SwimmingAttendance.php` | core entity `Swimming Attendance` (table `swimming_attendance`) |
| `SwimmingLedger` | `swimming_ledger` | 9 | `app/Models/SwimmingLedger.php` | core entity `Swimming Ledger` (table `swimming_ledger`) |
| `SwimmingTransactionAllocation` | `swimming_transaction_allocations` | 3 | `app/Models/SwimmingTransactionAllocation.php` | core entity `Swimming Transaction Allocation` (table `swimming_transaction_allocations`) |
| `SwimmingWallet` | `swimming_wallets` | 20 | `app/Models/SwimmingWallet.php` | core entity `Swimming Wallet` (table `swimming_wallets`) |
| `SystemSetting` | `system_settings` | 0 | `app/Models/SystemSetting.php` | core entity `System Setting` (table `system_settings`) |
| `Teacher` | `teachers` | 152 | `app/Models/Teacher.php` | core entity `Teacher` (table `teachers`) |
| `Term` | `terms` | 229 | `app/Models/Term.php` | core entity `Term` (table `terms`) |
| `TrainingCourse` | `training_courses` | 0 | `app/Models/TrainingCourse.php` | core entity `Training Course` (table `training_courses`) |
| `TrainingRecord` | `training_records` | 1 | `app/Models/TrainingRecord.php` | core entity `Training Record` (table `training_records`) |
| `TrainingRequest` | `training_requests` | 0 | `app/Models/TrainingRequest.php` | core entity `Training Request` (table `training_requests`) |
| `TransactionFixAudit` | `transaction_fix_audit` | 2 | `app/Models/TransactionFixAudit.php` | core entity `Transaction Fix Audit` (table `transaction_fix_audit`) |
| `Transport` | `transport` | 65 | `app/Models/Transport.php` | core entity `Transport` (table `transport`) |
| `TransportFee` | `transport_fees` | 26 | `app/Models/TransportFee.php` | core entity `Transport Fee` (table `transport_fees`) |
| `TransportFeeImport` | `transport_fee_imports` | 2 | `app/Models/TransportFeeImport.php` | core entity `Transport Fee Import` (table `transport_fee_imports`) |
| `TransportFeeRevision` | `transport_fee_revisions` | 2 | `app/Models/TransportFeeRevision.php` | core entity `Transport Fee Revision` (table `transport_fee_revisions`) |
| `TransportImportLog` | `transport_import_logs` | 1 | `app/Models/TransportImportLog.php` | core entity `Transport Import Log` (table `transport_import_logs`) |
| `TransportSpecialAssignment` | `transport_special_assignments` | 9 | `app/Models/TransportSpecialAssignment.php` | core entity `Transport Special Assignment` (table `transport_special_assignments`) |
| `Trip` | `trips` | 46 | `app/Models/Trip.php` | core entity `Trip` (table `trips`) |
| `TripAttendance` | `trip_attendances` | 4 | `app/Models/TripAttendance.php` | core entity `Trip Attendance` (table `trip_attendances`) |
| `TripRun` | `trip_runs` | 5 | `app/Models/TripRun.php` | core entity `Trip Run` (table `trip_runs`) |
| `TripRunLocation` | `trip_run_locations` | 2 | `app/Models/TripRunLocation.php` | core entity `Trip Run Location` (table `trip_run_locations`) |
| `TripStop` | `trip_stops` | 1 | `app/Models/TripStop.php` | core entity `Trip Stop` (table `trip_stops`) |
| `User` | `users` | 270 | `app/Models/User.php` | core entity `User` (table `users`) |
| `UserBiometricUnlock` | `user_biometric_unlocks` | 2 | `app/Models/UserBiometricUnlock.php` | core entity `User Biometric Unlock` (table `user_biometric_unlocks`) |
| `Vehicle` | `vehicles` | 34 | `app/Models/Vehicle.php` | core entity `Vehicle` (table `vehicles`) |
| `Vendor` | `vendors` | 16 | `app/Models/Vendor.php` | core entity `Vendor` (table `vendors`) |
| `VisitorLog` | `visitor_logs` | 7 | `app/Models/VisitorLog.php` | core entity `Visitor Log` (table `visitor_logs`) |
| `Votehead` | `voteheads` | 68 | `app/Models/Votehead.php` | core entity `Votehead` (table `voteheads`) |
| `VoteheadCategory` | `votehead_categories` | 4 | `app/Models/VoteheadCategory.php` | core entity `Votehead Category` (table `votehead_categories`) |

### `Academics` (49)

| Model | Table | Refs | Path | Purpose |
|---|---|---:|---|---|
| `Assessment` | `assessments` | 33 | `app/Models/Academics/Assessment.php` | Academics entity `Assessment` (table `assessments`) |
| `Behaviour` | `behaviours` | 12 | `app/Models/Academics/Behaviour.php` | Academics entity `Behaviour` (table `behaviours`) |
| `CBCCoreCompetency` | `cbc_core_competencies` | 3 | `app/Models/Academics/CBCCoreCompetency.php` | Academics entity `C B C Core Competency` (table `cbc_core_competencies`) |
| `CBCPerformanceLevel` | `cbc_performance_levels` | 8 | `app/Models/Academics/CBCPerformanceLevel.php` | Academics entity `C B C Performance Level` (table `cbc_performance_levels`) |
| `CBCStrand` | `cbc_strands` | 22 | `app/Models/Academics/CBCStrand.php` | Academics entity `C B C Strand` (table `cbc_strands`) |
| `CBCSubstrand` | `cbc_substrands` | 21 | `app/Models/Academics/CBCSubstrand.php` | Academics entity `C B C Substrand` (table `cbc_substrands`) |
| `Classroom` | `classrooms` | 189 | `app/Models/Academics/Classroom.php` | Academics entity `Classroom` (table `classrooms`) |
| `ClassroomSubject` | `classroom_subjects` | 19 | `app/Models/Academics/ClassroomSubject.php` | Academics entity `Classroom Subject` (table `classroom_subjects`) |
| `Competency` | `competencies` | 20 | `app/Models/Academics/Competency.php` | Academics entity `Competency` (table `competencies`) |
| `DiaryEntry` | `diary_entries` | 5 | `app/Models/Academics/DiaryEntry.php` | Academics entity `Diary Entry` (table `diary_entries`) |
| `Exam` | `exams` | 89 | `app/Models/Academics/Exam.php` | Academics entity `Exam` (table `exams`) |
| `ExamGrade` | `exam_grades` | 5 | `app/Models/Academics/ExamGrade.php` | Academics entity `Exam Grade` (table `exam_grades`) |
| `ExamGroup` | `exam_groups` | 2 | `app/Models/Academics/ExamGroup.php` | Academics entity `Exam Group` (table `exam_groups`) |
| `ExamItem` | `exam_items` | 0 | `app/Models/Academics/ExamItem.php` | Academics entity `Exam Item` (table `exam_items`) |
| `ExamMark` | `exam_marks` | 37 | `app/Models/Academics/ExamMark.php` | Academics entity `Exam Mark` (table `exam_marks`) |
| `ExamPaper` | `exam_papers` | 3 | `app/Models/Academics/ExamPaper.php` | Academics entity `Exam Paper` (table `exam_papers`) |
| `ExamSchedule` | `exam_schedules` | 5 | `app/Models/Academics/ExamSchedule.php` | Academics entity `Exam Schedule` (table `exam_schedules`) |
| `ExamSession` | `exam_sessions` | 10 | `app/Models/Academics/ExamSession.php` | Academics entity `Exam Session` (table `exam_sessions`) |
| `ExamType` | `exam_types` | 13 | `app/Models/Academics/ExamType.php` | Academics entity `Exam Type` (table `exam_types`) |
| `ExtraCurricularActivity` | `extra_curricular_activities` | 6 | `app/Models/Academics/ExtraCurricularActivity.php` | Academics entity `Extra Curricular Activity` (table `extra_curricular_activities`) |
| `GradingBand` | `grading_bands` | 5 | `app/Models/Academics/GradingBand.php` | Academics entity `Grading Band` (table `grading_bands`) |
| `GradingScheme` | `grading_schemes` | 6 | `app/Models/Academics/GradingScheme.php` | Academics entity `Grading Scheme` (table `grading_schemes`) |
| `GradingSchemeMapping` | `grading_scheme_mappings` | 2 | `app/Models/Academics/GradingSchemeMapping.php` | Academics entity `Grading Scheme Mapping` (table `grading_scheme_mappings`) |
| `Homework` | `homeworks` | 22 | `app/Models/Academics/Homework.php` | Academics entity `Homework` (table `homeworks`) |
| `HomeworkDiary` | `homework_diary` | 9 | `app/Models/Academics/HomeworkDiary.php` | Academics entity `Homework Diary` (table `homework_diary`) |
| `LearningArea` | `learning_areas` | 19 | `app/Models/Academics/LearningArea.php` | Academics entity `Learning Area` (table `learning_areas`) |
| `LessonPlan` | `lesson_plans` | 25 | `app/Models/Academics/LessonPlan.php` | Academics entity `Lesson Plan` (table `lesson_plans`) |
| `PortfolioAssessment` | `portfolio_assessments` | 8 | `app/Models/Academics/PortfolioAssessment.php` | Academics entity `Portfolio Assessment` (table `portfolio_assessments`) |
| `ReportCard` | `report_cards` | 31 | `app/Models/Academics/ReportCard.php` | Academics entity `Report Card` (table `report_cards`) |
| `ReportCardSkill` | `report_card_skills` | 9 | `app/Models/Academics/ReportCardSkill.php` | Academics entity `Report Card Skill` (table `report_card_skills`) |
| `SchemeOfWork` | `schemes_of_work` | 10 | `app/Models/Academics/SchemeOfWork.php` | Academics entity `Scheme Of Work` (table `schemes_of_work`) |
| `Stream` | `streams` | 98 | `app/Models/Academics/Stream.php` | Academics entity `Stream` (table `streams`) |
| `StudentBehaviour` | `student_behaviours` | 9 | `app/Models/Academics/StudentBehaviour.php` | Academics entity `Student Behaviour` (table `student_behaviours`) |
| `StudentDiary` | `student_diaries` | 7 | `app/Models/Academics/StudentDiary.php` | Academics entity `Student Diary` (table `student_diaries`) |
| `StudentSkillGrade` | `student_skill_grades` | 2 | `app/Models/Academics/StudentSkillGrade.php` | Academics entity `Student Skill Grade` (table `student_skill_grades`) |
| `Subject` | `subjects` | 105 | `app/Models/Academics/Subject.php` | Academics entity `Subject` (table `subjects`) |
| `TimePeriod` | `time_periods` | 1 | `app/Models/Academics/TimePeriod.php` | Academics entity `Time Period` (table `time_periods`) |
| `Timetable` | `timetables` | 32 | `app/Models/Academics/Timetable.php` | Academics entity `Timetable` (table `timetables`) |
| `TimetableGeneratedSlot` | `timetable_generated_slots` | 3 | `app/Models/Academics/TimetableGeneratedSlot.php` | Academics entity `Timetable Generated Slot` (table `timetable_generated_slots`) |
| `TimetableGenerationRun` | `timetable_generation_runs` | 4 | `app/Models/Academics/TimetableGenerationRun.php` | Academics entity `Timetable Generation Run` (table `timetable_generation_runs`) |
| `TimetableLayoutPeriod` | `timetable_layout_periods` | 5 | `app/Models/Academics/TimetableLayoutPeriod.php` | Academics entity `Timetable Layout Period` (table `timetable_layout_periods`) |
| `TimetableLayoutTemplate` | `timetable_layout_templates` | 2 | `app/Models/Academics/TimetableLayoutTemplate.php` | Academics entity `Timetable Layout Template` (table `timetable_layout_templates`) |
| `TimetableSlotLock` | `timetable_slot_locks` | 2 | `app/Models/Academics/TimetableSlotLock.php` | Academics entity `Timetable Slot Lock` (table `timetable_slot_locks`) |
| `TimetableSlotOverride` | `timetable_slot_overrides` | 1 | `app/Models/Academics/TimetableSlotOverride.php` | Academics entity `Timetable Slot Override` (table `timetable_slot_overrides`) |
| `TimetableStreamActivityRequirement` | `timetable_stream_activity_requirements` | 4 | `app/Models/Academics/TimetableStreamActivityRequirement.php` | Academics entity `Timetable Stream Activity Requirement` (table `timetable_stream_activity_requirements`) |
| `TimetableStreamActivityTeacher` | `timetable_stream_activity_teachers` | 5 | `app/Models/Academics/TimetableStreamActivityTeacher.php` | Academics entity `Timetable Stream Activity Teacher` (table `timetable_stream_activity_teachers`) |
| `TimetableStreamLayout` | `timetable_stream_layouts` | 3 | `app/Models/Academics/TimetableStreamLayout.php` | Academics entity `Timetable Stream Layout` (table `timetable_stream_layouts`) |
| `TimetableStreamSubjectRequirement` | `timetable_stream_subject_requirements` | 3 | `app/Models/Academics/TimetableStreamSubjectRequirement.php` | Academics entity `Timetable Stream Subject Requirement` (table `timetable_stream_subject_requirements`) |
| `TimetableStreamSubjectTeacher` | `timetable_stream_subject_teachers` | 4 | `app/Models/Academics/TimetableStreamSubjectTeacher.php` | Academics entity `Timetable Stream Subject Teacher` (table `timetable_stream_subject_teachers`) |

### `Website` (48)

| Model | Table | Refs | Path | Purpose |
|---|---|---:|---|---|
| `AiChatMessage` | `ai_chat_messages` | 2 | `app/Models/Website/AiChatMessage.php` | Website entity `Ai Chat Message` (table `ai_chat_messages`) |
| `AiChatSession` | `ai_chat_sessions` | 3 | `app/Models/Website/AiChatSession.php` | Website entity `Ai Chat Session` (table `ai_chat_sessions`) |
| `AiContentLog` | `ai_content_logs` | 5 | `app/Models/Website/AiContentLog.php` | Website entity `Ai Content Log` (table `ai_content_logs`) |
| `AlumniStory` | `alumni_stories` | 2 | `app/Models/Website/AlumniStory.php` | Website entity `Alumni Story` (table `alumni_stories`) |
| `AssistantKnowledgeArticle` | `assistant_knowledge_articles` | 2 | `app/Models/Website/AssistantKnowledgeArticle.php` | Website entity `Assistant Knowledge Article` (table `assistant_knowledge_articles`) |
| `Blog` | `blogs` | 14 | `app/Models/Website/Blog.php` | Website entity `Blog` (table `blogs`) |
| `BlogCategory` | `blog_categories` | 3 | `app/Models/Website/BlogCategory.php` | Website entity `Blog Category` (table `blog_categories`) |
| `BlogTag` | `blog_tags` | 1 | `app/Models/Website/BlogTag.php` | Website entity `Blog Tag` (table `blog_tags`) |
| `CampaignLog` | `campaign_logs` | 3 | `app/Models/Website/CampaignLog.php` | Website entity `Campaign Log` (table `campaign_logs`) |
| `ContentCalendarItem` | `content_calendar` | 2 | `app/Models/Website/ContentCalendarItem.php` | Website entity `Content Calendar Item` (table `content_calendar`) |
| `ConversionEvent` | `conversion_events` | 3 | `app/Models/Website/ConversionEvent.php` | Website entity `Conversion Event` (table `conversion_events`) |
| `Enquiry` | `enquiries` | 4 | `app/Models/Website/Enquiry.php` | Website entity `Enquiry` (table `enquiries`) |
| `ExitIntentCampaign` | `exit_intent_campaigns` | 2 | `app/Models/Website/ExitIntentCampaign.php` | Website entity `Exit Intent Campaign` (table `exit_intent_campaigns`) |
| `FamilyStory` | `family_stories` | 3 | `app/Models/Website/FamilyStory.php` | Website entity `Family Story` (table `family_stories`) |
| `Faq` | `faqs` | 6 | `app/Models/Website/Faq.php` | Website entity `Faq` (table `faqs`) |
| `LeadMagnet` | `lead_magnets` | 7 | `app/Models/Website/LeadMagnet.php` | Website entity `Lead Magnet` (table `lead_magnets`) |
| `LeadMagnetDownload` | `lead_magnet_downloads` | 3 | `app/Models/Website/LeadMagnetDownload.php` | Website entity `Lead Magnet Download` (table `lead_magnet_downloads`) |
| `MediaAlbum` | `media_albums` | 4 | `app/Models/Website/MediaAlbum.php` | Website entity `Media Album` (table `media_albums`) |
| `MediaLibraryItem` | `media_library` | 8 | `app/Models/Website/MediaLibraryItem.php` | Website entity `Media Library Item` (table `media_library`) |
| `MediaQualityFlag` | `media_quality_flags` | 2 | `app/Models/Website/MediaQualityFlag.php` | Website entity `Media Quality Flag` (table `media_quality_flags`) |
| `MediaTag` | `media_tags` | 2 | `app/Models/Website/MediaTag.php` | Website entity `Media Tag` (table `media_tags`) |
| `NewsletterSubscriber` | `newsletter_subscribers` | 3 | `app/Models/Website/NewsletterSubscriber.php` | Website entity `Newsletter Subscriber` (table `newsletter_subscribers`) |
| `Page` | `pages` | 101 | `app/Models/Website/Page.php` | Website entity `Page` (table `pages`) |
| `PageBuilderDraft` | `page_builder_drafts` | 1 | `app/Models/Website/PageBuilderDraft.php` | Website entity `Page Builder Draft` (table `page_builder_drafts`) |
| `PageBuilderSnapshot` | `page_builder_snapshots` | 3 | `app/Models/Website/PageBuilderSnapshot.php` | Website entity `Page Builder Snapshot` (table `page_builder_snapshots`) |
| `PageRevision` | `page_revisions` | 1 | `app/Models/Website/PageRevision.php` | Website entity `Page Revision` (table `page_revisions`) |
| `PageSection` | `page_sections` | 9 | `app/Models/Website/PageSection.php` | Website entity `Page Section` (table `page_sections`) |
| `PageView` | `page_views` | 3 | `app/Models/Website/PageView.php` | Website entity `Page View` (table `page_views`) |
| `PrayerRequest` | `prayer_requests` | 2 | `app/Models/Website/PrayerRequest.php` | Website entity `Prayer Request` (table `prayer_requests`) |
| `Referral` | `referrals` | 4 | `app/Models/Website/Referral.php` | Website entity `Referral` (table `referrals`) |
| `ReusableBlock` | `reusable_blocks` | 2 | `app/Models/Website/ReusableBlock.php` | Website entity `Reusable Block` (table `reusable_blocks`) |
| `SchoolMeal` | `school_meals` | 3 | `app/Models/Website/SchoolMeal.php` | Website entity `School Meal` (table `school_meals`) |
| `SectionTemplate` | `section_templates` | 3 | `app/Models/Website/SectionTemplate.php` | Website entity `Section Template` (table `section_templates`) |
| `SeoKeyword` | `seo_keywords` | 3 | `app/Models/Website/SeoKeyword.php` | Website entity `Seo Keyword` (table `seo_keywords`) |
| `SeoMeta` | `seo_meta` | 0 | `app/Models/Website/SeoMeta.php` | Website entity `Seo Meta` (table `seo_meta`) |
| `ServiceAreaPage` | `service_area_pages` | 4 | `app/Models/Website/ServiceAreaPage.php` | Website entity `Service Area Page` (table `service_area_pages`) |
| `StudentSpotlight` | `student_spotlights` | 3 | `app/Models/Website/StudentSpotlight.php` | Website entity `Student Spotlight` (table `student_spotlights`) |
| `Testimonial` | `testimonials` | 9 | `app/Models/Website/Testimonial.php` | Website entity `Testimonial` (table `testimonials`) |
| `TestimonialCategory` | `testimonial_categories` | 1 | `app/Models/Website/TestimonialCategory.php` | Website entity `Testimonial Category` (table `testimonial_categories`) |
| `VirtualTourStop` | `virtual_tour_stops` | 2 | `app/Models/Website/VirtualTourStop.php` | Website entity `Virtual Tour Stop` (table `virtual_tour_stops`) |
| `WebsiteBrandItem` | `website_brand_items` | 3 | `app/Models/Website/WebsiteBrandItem.php` | Website entity `Website Brand Item` (table `website_brand_items`) |
| `WebsiteCompetition` | `website_competitions` | 2 | `app/Models/Website/WebsiteCompetition.php` | Website entity `Website Competition` (table `website_competitions`) |
| `WebsiteCta` | `website_ctas` | 3 | `app/Models/Website/WebsiteCta.php` | Website entity `Website Cta` (table `website_ctas`) |
| `WebsiteEvent` | `website_events` | 8 | `app/Models/Website/WebsiteEvent.php` | Website entity `Website Event` (table `website_events`) |
| `WebsiteEventRegistration` | `website_event_registrations` | 2 | `app/Models/Website/WebsiteEventRegistration.php` | Website entity `Website Event Registration` (table `website_event_registrations`) |
| `WebsiteMenu` | `website_menus` | 2 | `app/Models/Website/WebsiteMenu.php` | Website entity `Website Menu` (table `website_menus`) |
| `WebsiteMenuItem` | `website_menu_items` | 1 | `app/Models/Website/WebsiteMenuItem.php` | Website entity `Website Menu Item` (table `website_menu_items`) |
| `WebsiteSetting` | `website_settings` | 11 | `app/Models/Website/WebsiteSetting.php` | Website entity `Website Setting` (table `website_settings`) |

### `Pos` (6)

| Model | Table | Refs | Path | Purpose |
|---|---|---:|---|---|
| `Discount` | `pos_discounts` | 20 | `app/Models/Pos/Discount.php` | Pos entity `Discount` (table `pos_discounts`) |
| `Order` | `pos_orders` | 41 | `app/Models/Pos/Order.php` | Pos entity `Order` (table `pos_orders`) |
| `OrderItem` | `pos_order_items` | 10 | `app/Models/Pos/OrderItem.php` | Pos entity `Order Item` (table `pos_order_items`) |
| `Product` | `pos_products` | 17 | `app/Models/Pos/Product.php` | Pos entity `Product` (table `pos_products`) |
| `ProductVariant` | `pos_product_variants` | 6 | `app/Models/Pos/ProductVariant.php` | Pos entity `Product Variant` (table `pos_product_variants`) |
| `PublicShopLink` | `pos_public_shop_links` | 5 | `app/Models/Pos/PublicShopLink.php` | Pos entity `Public Shop Link` (table `pos_public_shop_links`) |

### `AcademicReports` (5)

| Model | Table | Refs | Path | Purpose |
|---|---|---:|---|---|
| `AcademicReportAnswer` | `academic_report_answers` | 4 | `app/Models/AcademicReports/AcademicReportAnswer.php` | AcademicReports entity `Academic Report Answer` (table `academic_report_answers`) |
| `AcademicReportAssignment` | `academic_report_assignments` | 2 | `app/Models/AcademicReports/AcademicReportAssignment.php` | AcademicReports entity `Academic Report Assignment` (table `academic_report_assignments`) |
| `AcademicReportQuestion` | `academic_report_questions` | 4 | `app/Models/AcademicReports/AcademicReportQuestion.php` | AcademicReports entity `Academic Report Question` (table `academic_report_questions`) |
| `AcademicReportSubmission` | `academic_report_submissions` | 4 | `app/Models/AcademicReports/AcademicReportSubmission.php` | AcademicReports entity `Academic Report Submission` (table `academic_report_submissions`) |
| `AcademicReportTemplate` | `academic_report_templates` | 5 | `app/Models/AcademicReports/AcademicReportTemplate.php` | AcademicReports entity `Academic Report Template` (table `academic_report_templates`) |

### `Reports` (5)

| Model | Table | Refs | Path | Purpose |
|---|---|---:|---|---|
| `ClassReport` | `class_reports` | 3 | `app/Models/Reports/ClassReport.php` | Reports entity `Class Report` (table `class_reports`) |
| `OperationsFacility` | `operations_facilities` | 5 | `app/Models/Reports/OperationsFacility.php` | Reports entity `Operations Facility` (table `operations_facilities`) |
| `StaffWeekly` | `staff_weeklies` | 3 | `app/Models/Reports/StaffWeekly.php` | Reports entity `Staff Weekly` (table `staff_weeklies`) |
| `StudentFollowup` | `student_followups` | 3 | `app/Models/Reports/StudentFollowup.php` | Reports entity `Student Followup` (table `student_followups`) |
| `SubjectReport` | `subject_reports` | 3 | `app/Models/Reports/SubjectReport.php` | Reports entity `Subject Report` (table `subject_reports`) |

### `Admissions` (2)

| Model | Table | Refs | Path | Purpose |
|---|---|---:|---|---|
| `AdmissionApplication` | `admission_applications` | 12 | `app/Models/Admissions/AdmissionApplication.php` | Admissions entity `Admission Application` (table `admission_applications`) |
| `AdmissionDocument` | `admission_documents` | 4 | `app/Models/Admissions/AdmissionDocument.php` | Admissions entity `Admission Document` (table `admission_documents`) |
