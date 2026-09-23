# Blade view catalog (live inventory, 2026-09-22)

Total Blade files: **861**.
Purpose is inferred from path/filename plus module. `partial` means included by another view; `page` is typically returned by a controller.

## Counts by folder

| Folder | Views | Role |
|---|---:|---|
| `finance` | 183 | Fees, payments, invoices, receipts, accounting, M-Pesa, expenses |
| `academics` | 150 | Classes, CBC, exams, timetable, lesson plans, report cards |
| `communication` | 43 | SMS, email, WhatsApp, templates, announcements, notes |
| `students` | 43 | Registry, families, records, admissions, imports |
| `website` | 38 | Public CMS / school website admin |
| `dashboard` | 34 | Admin home KPIs and widgets |
| `hr` | 34 | Payroll, advances, statutory, staff analytics |
| `staff` | 29 | Staff CRUD, leave, documents, attendance, profile |
| `components` | 28 | Reusable Blade/UI kit (x- components) |
| `vendor` | 26 | Published package views (AdminLTE, mail, pagination) |
| `settings` | 21 | School settings, terms, placeholders, features |
| `pos` | 20 | School shop / uniforms / discounts |
| `inventory` | 19 | Items, requisitions, student requirements |
| `transport` | 17 | Routes, trips, assignments, import |
| `reports` | 12 | Weekly/class/subject/operations reports |
| `teacher` | 12 | Teacher portal screens |
| `partials` | 11 | Global shared snippets (SMS/email forms, terms) |
| `auth` | 10 | Login, passwords, 2FA/verify |
| `errors` | 10 | HTTP error pages (framework-wired) |
| `layouts` | 10 | App shells and role navs |
| `attendance` | 9 | Student attendance marking & reports |
| `families` | 8 | Family hub / linking |
| `operations` | 7 | Assets, visitors, concerns |
| `senior_teacher` | 7 | Senior teacher workspace |
| `activities` | 6 | Extra-curricular + parent requests |
| `student_assignments` | 6 | Student–class assignment UI |
| `swimming` | 6 | Swimming module |
| `dropoffpoints` | 5 | Drop-off point CRUD/import |
| `attendance_notifications` | 4 | Absence notification setup |
| `emails` | 4 | Mailable markdown/html |
| `events` | 4 | Calendar events |
| `family_update` | 4 | Parent self-service family update |
| `trips` | 4 | Trip CRUD (parallel to transport) |
| `documents` | 3 | Generated document UI |
| `driver` | 3 | Driver portal |
| `online_admissions` | 3 | Public/admin admissions |
| `student_categories` | 3 | Student category lookup |
| `vehicles` | 3 | Vehicle CRUD (parallel to transport) |
| `activity-logs` | 2 | User activity log |
| `admin` | 2 | Admin-only extras (senior teacher assignments) |
| `exports` | 2 | HTML/Excel-ish export layouts |
| `family` | 2 | Family-scoped screens (split from families/) |
| `legal` | 2 | Privacy / terms |
| `parent` | 2 | Parent diary portal |
| `pdf` | 2 | Generic PDF wrappers |
| `backup_restore` | 1 | Backup UI |
| `gallery` | 1 | Photo gallery |
| `home` | 1 | Legacy home |
| `parents` | 1 | Legacy/parent alias |
| `sms_logs` | 1 | SMS delivery log |
| `system-logs` | 1 | System log viewer |
| `users` | 1 | User admin |
| `welcome` | 1 | Laravel default welcome |

## Every Blade file

### `finance/` (183)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `finance.accountant_dashboard.index` | page | 298 | list/index page |
| `finance.accountant_dashboard.settings` | page | 101 | dashboard |
| `finance.accountant_dashboard.student_history` | page | 96 | dashboard |
| `finance.accounting.budgets.index` | page | 38 | list/index page |
| `finance.accounting.budgets.show` | page | 43 | detail view |
| `finance.accounting.chart_of_accounts._row` | partial | 15 |  row |
| `finance.accounting.chart_of_accounts.index` | page | 56 | list/index page |
| `finance.accounting.fiscal_periods.index` | page | 43 | list/index page |
| `finance.accounting.journal_entries.create` | page | 77 | create form |
| `finance.accounting.journal_entries.index` | page | 34 | list/index page |
| `finance.accounting.journal_entries.show` | page | 37 | detail view |
| `finance.accounting.petty_cash.funds.create` | page | 28 | create form |
| `finance.accounting.petty_cash.funds.index` | page | 34 | list/index page |
| `finance.accounting.petty_cash.vouchers.create` | page | 39 | create form |
| `finance.accounting.petty_cash.vouchers.index` | page | 36 | list/index page |
| `finance.accounting.petty_cash.vouchers.show` | page | 58 | detail view |
| `finance.accounting.reports.balance_sheet` | page | 44 | balance sheet |
| `finance.accounting.reports.profit_and_loss` | page | 38 | profit and loss |
| `finance.accounting.reports.profit_loss_reconciliation` | page | 199 | profit loss reconciliation |
| `finance.accounting.reports.trial_balance` | page | 40 | trial balance |
| `finance.balance_brought_forward.import_preview` | page | 339 | import preview |
| `finance.balance_brought_forward.index` | page | 225 | list/index page |
| `finance.bank-statements.create` | page | 86 | create form |
| `finance.bank-statements.edit` | page | 128 | edit form |
| `finance.bank-statements.history` | page | 128 | history |
| `finance.bank-statements.index` | page | 1265 | list/index page |
| `finance.bank-statements.show` | page | 2171 | detail view |
| `finance.bank-statements.statements` | page | 285 | statements |
| `finance.bank-statements.view-pdf` | page | 29 | view pdf |
| `finance.bank_accounts.create` | page | 133 | create form |
| `finance.bank_accounts.edit` | page | 129 | edit form |
| `finance.bank_accounts.index` | page | 101 | list/index page |
| `finance.bank_accounts.show` | page | 92 | detail view |
| `finance.credit_debit_adjustments.bulk` | page | 83 | bulk |
| `finance.credit_debit_adjustments.create` | page | 97 | create form |
| `finance.credit_debit_adjustments.index` | page | 258 | list/index page |
| `finance.credit_debit_adjustments.show` | page | 2 | detail view |
| `finance.credit_debit_notes.import_preview` | page | 84 | import preview |
| `finance.discounts.allocate` | page | 225 | allocate |
| `finance.discounts.allocations.index` | page | 800 | list/index page |
| `finance.discounts.bulk-allocate-sibling` | page | 232 | bulk allocate sibling |
| `finance.discounts.create` | page | 226 | create form |
| `finance.discounts.index` | page | 145 | list/index page |
| `finance.discounts.replicate` | page | 170 | replicate |
| `finance.discounts.show` | page | 123 | detail view |
| `finance.discounts.templates.index` | page | 171 | list/index page |
| `finance.document_settings.index` | page | 408 | list/index page |
| `finance.expense-statements._grouping` | partial | 581 |  grouping |
| `finance.expense-statements.create` | page | 86 | create form |
| `finance.expense-statements.index` | page | 73 | list/index page |
| `finance.expense-statements.processing` | page | 121 | processing |
| `finance.expense-statements.show` | page | 260 | detail view |
| `finance.expense_categories._row` | partial | 97 |  row |
| `finance.expense_categories.index` | page | 52 | list/index page |
| `finance.expenses.create` | page | 16 | create form |
| `finance.expenses.edit` | page | 17 | edit form |
| `finance.expenses.index` | page | 227 | list/index page |
| `finance.expenses.partials.form` | partial | 47 | shared form partial |
| `finance.expenses.show` | page | 71 | detail view |
| `finance.fee_balances.index` | page | 495 | list/index page |
| `finance.fee_balances.partials.exclude_modal` | partial | 62 | partial/include |
| `finance.fee_balances.partials.student_row` | partial | 141 | partial/include |
| `finance.fee_balances.pdf` | page | 231 | PDF template |
| `finance.fee_clearance.index` | page | 257 | list/index page |
| `finance.fee_clearance.pdf_by_class` | page | 112 | pdf by class |
| `finance.fee_concessions.create` | page | 127 | create form |
| `finance.fee_concessions.index` | page | 126 | list/index page |
| `finance.fee_concessions.show` | page | 84 | detail view |
| `finance.fee_payment_plans.create` | page | 558 | create form |
| `finance.fee_payment_plans.index` | page | 109 | list/index page |
| `finance.fee_payment_plans.pdf.agreement` | page | 298 | PDF template |
| `finance.fee_payment_plans.public` | page | 189 | public/unauthenticated page |
| `finance.fee_payment_plans.show` | page | 200 | detail view |
| `finance.fee_reminders.index` | page | 306 | list/index page |
| `finance.fee_reminders.schedule.create` | page | 771 | create form |
| `finance.fee_statements.index` | page | 2 | list/index page |
| `finance.fee_statements.show` | page | 34 | detail view |
| `finance.fee_structures.import` | page | 236 | import workflow |
| `finance.fee_structures.index` | page | 113 | list/index page |
| `finance.fee_structures.manage` | page | 260 | manage |
| `finance.fees_comparison_import.index` | page | 113 | list/index page |
| `finance.fees_comparison_import.preview` | page | 369 | preview |
| `finance.invoices.create` | page | 12 | create form |
| `finance.invoices.history` | page | 225 | history |
| `finance.invoices.import` | page | 19 | import workflow |
| `finance.invoices.index` | page | 385 | list/index page |
| `finance.invoices.partials.alerts` | partial | 34 | partial/include |
| `finance.invoices.pdf.bulk` | page | 341 | PDF template |
| `finance.invoices.pdf.single` | page | 313 | PDF template |
| `finance.invoices.public` | page | 187 | public/unauthenticated page |
| `finance.invoices.show` | page | 1022 | detail view |
| `finance.journals.create` | page | 186 | create form |
| `finance.legacy-imports.edit-history` | page | 179 | edit history |
| `finance.legacy-imports.index` | page | 115 | list/index page |
| `finance.legacy-imports.show` | page | 533 | detail view |
| `finance.mpesa.c2b-allocate` | page | 984 | c2b allocate |
| `finance.mpesa.c2b-dashboard` | page | 362 | dashboard |
| `finance.mpesa.c2b-transactions` | page | 199 | c2b transactions |
| `finance.mpesa.create-link` | page | 654 | create link |
| `finance.mpesa.dashboard` | page | 237 | dashboard |
| `finance.mpesa.invoice-payment` | page | 355 | invoice payment |
| `finance.mpesa.link-details` | page | 103 | link details |
| `finance.mpesa.link-expired` | page | 75 | link expired |
| `finance.mpesa.links` | page | 227 | links |
| `finance.mpesa.partials.unpaid-invoice-breakdown` | partial | 20 | partial/include |
| `finance.mpesa.payment-page` | page | 597 | payment page |
| `finance.mpesa.prompt-payment` | page | 776 | prompt payment |
| `finance.mpesa.public-pay` | page | 272 | public pay |
| `finance.mpesa.transaction` | page | 207 | transaction |
| `finance.mpesa.waiting` | page | 601 | waiting |
| `finance.mpesa.waiting-standalone` | page | 208 | waiting standalone |
| `finance.optional_fees.duplicate_preview` | page | 133 | duplicate preview |
| `finance.optional_fees.import_details` | page | 102 | import details |
| `finance.optional_fees.import_history` | page | 120 | import history |
| `finance.optional_fees.import_preview` | page | 413 | import preview |
| `finance.optional_fees.index` | page | 178 | list/index page |
| `finance.optional_fees.partials.class_view` | partial | 72 | partial/include |
| `finance.optional_fees.partials.duplicate_form` | partial | 103 | shared form partial |
| `finance.optional_fees.partials.import_tabs` | partial | 91 | partial/include |
| `finance.optional_fees.partials.student_view` | partial | 160 | partial/include |
| `finance.partials.header` | partial | 16 | partial/include |
| `finance.partials.mobile-public-viewport` | partial | 60 | partial/include |
| `finance.partials.styles` | partial | 197 | partial/include |
| `finance.payment_methods.create` | page | 150 | create form |
| `finance.payment_methods.edit` | page | 150 | edit form |
| `finance.payment_methods.index` | page | 117 | list/index page |
| `finance.payment_methods.show` | page | 104 | detail view |
| `finance.payment_thresholds._form` | partial | 120 | shared form partial |
| `finance.payment_thresholds.create` | page | 41 | create form |
| `finance.payment_thresholds.edit` | page | 41 | edit form |
| `finance.payment_thresholds.index` | page | 129 | list/index page |
| `finance.payments.bulk-send-preview` | page | 280 | bulk send preview |
| `finance.payments.bulk-send-progress` | page | 335 | bulk send progress |
| `finance.payments.create` | page | 595 | create form |
| `finance.payments.failed-communications` | page | 256 | failed communications |
| `finance.payments.history` | page | 126 | history |
| `finance.payments.index` | page | 699 | list/index page |
| `finance.payments.show` | page | 1412 | detail view |
| `finance.posting.index` | page | 213 | list/index page |
| `finance.posting.preview` | page | 375 | preview |
| `finance.posting.show` | page | 323 | detail view |
| `finance.receipts.bulk-print` | page | 132 | bulk print |
| `finance.receipts.bulk-print-pdf` | page | 273 | bulk print pdf |
| `finance.receipts.index` | page | 39 | list/index page |
| `finance.receipts.my-receipts` | page | 87 | my receipts |
| `finance.receipts.pdf._receipt-body` | partial | 226 | PDF template |
| `finance.receipts.pdf.basic` | page | 70 | PDF template |
| `finance.receipts.pdf.template` | page | 365 | PDF template |
| `finance.receipts.print` | page | 285 | print layout |
| `finance.receipts.public` | page | 187 | public/unauthenticated page |
| `finance.receipts.show` | page | 13 | detail view |
| `finance.receipts.view` | page | 138 | view |
| `finance.reports.expenses` | page | 31 | expenses |
| `finance.reports.expenses_pdf` | page | 22 | expenses pdf |
| `finance.statement-transactions.index` | page | 82 | list/index page |
| `finance.student_credit_debit_notes.index` | page | 115 | list/index page |
| `finance.student_credit_debit_notes.show` | page | 197 | detail view |
| `finance.student_statements.family` | page | 165 | family |
| `finance.student_statements.index` | page | 49 | list/index page |
| `finance.student_statements.print` | page | 420 | print layout |
| `finance.student_statements.show` | page | 796 | detail view |
| `finance.transaction-fixes.index` | page | 308 | list/index page |
| `finance.transaction-fixes.show` | page | 206 | detail view |
| `finance.transport_fees.duplicate_preview` | page | 137 | duplicate preview |
| `finance.transport_fees.import` | page | 129 | import workflow |
| `finance.transport_fees.import_details` | page | 119 | import details |
| `finance.transport_fees.import_history` | page | 131 | import history |
| `finance.transport_fees.import_preview` | page | 508 | import preview |
| `finance.transport_fees.index` | page | 376 | list/index page |
| `finance.transport_fees.partials.duplicate_form` | partial | 96 | shared form partial |
| `finance.transport_fees.partials.flat_rate_form` | partial | 63 | shared form partial |
| `finance.transport_fees.partials.import_tabs` | partial | 95 | partial/include |
| `finance.vendors.create` | page | 11 | create form |
| `finance.vendors.edit` | page | 11 | edit form |
| `finance.vendors.index` | page | 22 | list/index page |
| `finance.vendors.partials.form` | partial | 10 | shared form partial |
| `finance.voteheads.create` | page | 23 | create form |
| `finance.voteheads.edit` | page | 23 | edit form |
| `finance.voteheads.form` | page | 91 | shared form partial |
| `finance.voteheads.import` | page | 187 | import workflow |
| `finance.voteheads.index` | page | 102 | list/index page |
| `finance.vouchers.index` | page | 26 | list/index page |
| `finance.vouchers.show` | page | 64 | detail view |

### `academics/` (150)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `academics.assessments.create` | page | 118 | create form |
| `academics.assessments.index` | page | 82 | list/index page |
| `academics.assessments.term` | page | 128 | term |
| `academics.assign_class_teacher` | page | 47 | assign class teacher |
| `academics.assign_teachers` | page | 197 | assign teachers |
| `academics.behaviours.create` | page | 32 | create form |
| `academics.behaviours.edit` | page | 32 | edit form |
| `academics.behaviours.index` | page | 56 | list/index page |
| `academics.behaviours.partials.form` | partial | 19 | shared form partial |
| `academics.cbc_strands.create` | page | 81 | create form |
| `academics.cbc_strands.edit` | page | 80 | edit form |
| `academics.cbc_strands.index` | page | 92 | list/index page |
| `academics.cbc_strands.show` | page | 90 | detail view |
| `academics.cbc_substrands.create` | page | 128 | create form |
| `academics.cbc_substrands.edit` | page | 129 | edit form |
| `academics.cbc_substrands.index` | page | 98 | list/index page |
| `academics.cbc_substrands.show` | page | 128 | detail view |
| `academics.class` | page | 27 | class |
| `academics.class_timetable` | page | 27 | class timetable |
| `academics.classrooms.create` | page | 103 | create form |
| `academics.classrooms.edit` | page | 115 | edit form |
| `academics.classrooms.index` | page | 196 | list/index page |
| `academics.competencies.create` | page | 166 | create form |
| `academics.competencies.edit` | page | 162 | edit form |
| `academics.competencies.index` | page | 127 | list/index page |
| `academics.competencies.show` | page | 86 | detail view |
| `academics.curriculum_assistant.index` | page | 139 | list/index page |
| `academics.curriculum_designs.create` | page | 73 | create form |
| `academics.curriculum_designs.edit` | page | 57 | edit form |
| `academics.curriculum_designs.index` | page | 117 | list/index page |
| `academics.curriculum_designs.review` | page | 98 | review |
| `academics.curriculum_designs.show` | page | 181 | detail view |
| `academics.diaries.index` | page | 249 | list/index page |
| `academics.diaries.partials.styles` | partial | 235 | partial/include |
| `academics.diaries.show` | page | 108 | detail view |
| `academics.exam_analytics.index` | page | 103 | list/index page |
| `academics.exam_groups.edit` | page | 66 | edit form |
| `academics.exam_groups.index` | page | 120 | list/index page |
| `academics.exam_marks.bulk_edit` | page | 323 | bulk edit |
| `academics.exam_marks.bulk_form` | page | 93 | shared form partial |
| `academics.exam_marks.edit` | page | 70 | edit form |
| `academics.exam_marks.index` | page | 78 | list/index page |
| `academics.exam_marks.matrix_edit` | page | 308 | matrix edit |
| `academics.exam_marks.partials.entry_audit` | partial | 84 | partial/include |
| `academics.exam_reports.class_sheet` | page | 549 | class sheet |
| `academics.exam_reports.class_sheet_pdf` | page | 183 | class sheet pdf |
| `academics.exam_reports.partials.analysis_filters` | partial | 248 | partial/include |
| `academics.exam_reports.partials.cbc_grade_badge` | partial | 10 | partial/include |
| `academics.exam_reports.partials.cbc_grade_styles` | partial | 152 | partial/include |
| `academics.exam_reports.partials.class_sheet_table` | partial | 180 | partial/include |
| `academics.exam_reports.partials.exam_report_print_css` | partial | 256 | partial/include |
| `academics.exam_reports.partials.most_improved_panel` | partial | 66 | partial/include |
| `academics.exam_reports.partials.report_letterhead` | partial | 85 | partial/include |
| `academics.exam_reports.student_insights` | page | 174 | student insights |
| `academics.exam_reports.subject_performance` | page | 94 | subject performance |
| `academics.exam_reports.teacher_performance` | page | 135 | teacher performance |
| `academics.exam_results.index` | page | 167 | list/index page |
| `academics.exam_results.publish_summary` | page | 61 | publish summary |
| `academics.exam_schedules.index` | page | 64 | list/index page |
| `academics.exam_types.index` | page | 139 | list/index page |
| `academics.exams.bulk_create` | page | 144 | bulk create |
| `academics.exams.create` | page | 33 | create form |
| `academics.exams.edit` | page | 36 | edit form |
| `academics.exams.grading.bulk` | page | 44 | bulk |
| `academics.exams.grading.duplicate` | page | 48 | duplicate |
| `academics.exams.grading.edit` | page | 63 | edit form |
| `academics.exams.grading.index` | page | 56 | list/index page |
| `academics.exams.index` | page | 391 | list/index page |
| `academics.exams.partials.form` | partial | 138 | shared form partial |
| `academics.exams.show` | page | 127 | detail view |
| `academics.exams.timetable` | page | 57 | timetable |
| `academics.extra_curricular_activities.create` | page | 193 | create form |
| `academics.extra_curricular_activities.edit` | page | 192 | edit form |
| `academics.extra_curricular_activities.index` | page | 129 | list/index page |
| `academics.extra_curricular_activities.show` | page | 120 | detail view |
| `academics.homework.create` | page | 119 | create form |
| `academics.homework.edit` | page | 122 | edit form |
| `academics.homework.index` | page | 62 | list/index page |
| `academics.homework.show` | page | 39 | detail view |
| `academics.homework_diary.index` | page | 114 | list/index page |
| `academics.homework_diary.mark` | page | 112 | mark |
| `academics.homework_diary.show` | page | 85 | detail view |
| `academics.homework_diary.submit` | page | 103 | submit |
| `academics.learning_areas.create` | page | 104 | create form |
| `academics.learning_areas.edit` | page | 102 | edit form |
| `academics.learning_areas.index` | page | 134 | list/index page |
| `academics.learning_areas.show` | page | 98 | detail view |
| `academics.lesson_plans.analytics` | page | 73 | analytics |
| `academics.lesson_plans.assign_homework` | page | 134 | assign homework |
| `academics.lesson_plans.create` | page | 146 | create form |
| `academics.lesson_plans.edit` | page | 89 | edit form |
| `academics.lesson_plans.index` | page | 129 | list/index page |
| `academics.lesson_plans.pdf` | page | 200 | PDF template |
| `academics.lesson_plans.review_queue` | page | 153 | review queue |
| `academics.lesson_plans.show` | page | 161 | detail view |
| `academics.portfolio_assessments.create` | page | 149 | create form |
| `academics.portfolio_assessments.edit` | page | 95 | edit form |
| `academics.portfolio_assessments.index` | page | 111 | list/index page |
| `academics.portfolio_assessments.show` | page | 72 | detail view |
| `academics.promote_students` | page | 47 | promote students |
| `academics.promotions.alumni` | page | 148 | alumni |
| `academics.promotions.index` | page | 125 | list/index page |
| `academics.promotions.show` | page | 190 | detail view |
| `academics.report_cards.bulk-print-pdf` | page | 24 | bulk print pdf |
| `academics.report_cards.edit` | page | 54 | edit form |
| `academics.report_cards.generate` | page | 108 | generate |
| `academics.report_cards.index` | page | 301 | list/index page |
| `academics.report_cards.partials.core` | partial | 312 | partial/include |
| `academics.report_cards.partials.notify-template-picker` | partial | 37 | partial/include |
| `academics.report_cards.partials.publish-modal` | partial | 80 | partial/include |
| `academics.report_cards.partials.school_stamp` | partial | 68 | partial/include |
| `academics.report_cards.pdf` | page | 19 | PDF template |
| `academics.report_cards.public` | page | 26 | public/unauthenticated page |
| `academics.report_cards.public_locked` | page | 34 | public locked |
| `academics.report_cards.show` | page | 86 | detail view |
| `academics.report_cards.skills.create` | page | 34 | create form |
| `academics.report_cards.skills.edit` | page | 34 | edit form |
| `academics.report_cards.skills.index` | page | 56 | list/index page |
| `academics.report_cards.skills.partials.form` | partial | 16 | shared form partial |
| `academics.report_cards.skills.pdf` | page | 118 | PDF template |
| `academics.schemes_of_work.create` | page | 260 | create form |
| `academics.schemes_of_work.edit` | page | 72 | edit form |
| `academics.schemes_of_work.index` | page | 121 | list/index page |
| `academics.schemes_of_work.pdf` | page | 108 | PDF template |
| `academics.schemes_of_work.show` | page | 102 | detail view |
| `academics.sections` | page | 27 | sections |
| `academics.skills.grades.index` | page | 107 | list/index page |
| `academics.streams.create` | page | 70 | create form |
| `academics.streams.edit` | page | 71 | edit form |
| `academics.streams.index` | page | 344 | list/index page |
| `academics.student_behaviours.create` | page | 77 | create form |
| `academics.student_behaviours.index` | page | 61 | list/index page |
| `academics.subjects.assign-teachers` | page | 272 | assign teachers |
| `academics.subjects.create` | page | 216 | create form |
| `academics.subjects.edit` | page | 248 | edit form |
| `academics.subjects.index` | page | 299 | list/index page |
| `academics.subjects.show` | page | 132 | detail view |
| `academics.teacher_assignments.edit` | page | 70 | edit form |
| `academics.teacher_assignments.index` | page | 64 | list/index page |
| `academics.teacher_assignments.partials.streams_form` | partial | 287 | shared form partial |
| `academics.teacher_timetable` | page | 27 | teacher timetable |
| `academics.timetable.classroom` | page | 231 | classroom |
| `academics.timetable.edit` | page | 122 | edit form |
| `academics.timetable.index` | page | 124 | list/index page |
| `academics.timetable.replicate` | page | 71 | replicate |
| `academics.timetable.run_editor` | page | 144 | run editor |
| `academics.timetable.substitutions` | page | 116 | substitutions |
| `academics.timetable.teacher` | page | 92 | teacher |
| `academics.timetable.teacher_load` | page | 89 | teacher load |
| `academics.timetable.whole_school` | page | 156 | whole school |

### `communication/` (43)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `communication.announcements.create` | page | 29 | create form |
| `communication.announcements.edit` | page | 30 | edit form |
| `communication.announcements.index` | page | 97 | list/index page |
| `communication.announcements.partials._form` | partial | 40 | shared form partial |
| `communication.app_adoption` | page | 88 | app adoption |
| `communication.app_issues` | page | 67 | app issues |
| `communication.bulk-progress` | page | 106 | bulk progress |
| `communication.compose` | page | 54 | compose |
| `communication.conversations` | page | 271 | conversations |
| `communication.delivery-report` | page | 82 | delivery report |
| `communication.delivery-reports-index` | page | 84 | delivery reports index |
| `communication.fee_reminder_automation` | page | 132 | fee reminder automation |
| `communication.job-show` | page | 89 | job show |
| `communication.logs` | page | 138 | logs |
| `communication.notes.create` | page | 191 | create form |
| `communication.notes.print` | page | 102 | print layout |
| `communication.parent_notification_blocks.form` | page | 69 | shared form partial |
| `communication.parent_notification_blocks.index` | page | 98 | list/index page |
| `communication.partials.document-send-modal` | partial | 160 | partial/include |
| `communication.partials.email-form` | partial | 478 | shared form partial |
| `communication.partials.exclude-student-modal` | partial | 151 | partial/include |
| `communication.partials.fee-balance-exclude-filters` | partial | 37 | partial/include |
| `communication.partials.flash` | partial | 48 | partial/include |
| `communication.partials.header` | partial | 12 | partial/include |
| `communication.partials.sms-form` | partial | 468 | shared form partial |
| `communication.partials.student-selector-modal` | partial | 238 | partial/include |
| `communication.partials.whatsapp-form` | partial | 544 | shared form partial |
| `communication.pending-jobs` | page | 160 | pending jobs |
| `communication.preview` | page | 672 | preview |
| `communication.send_email` | page | 26 | email template |
| `communication.send_sms` | page | 26 | send sms |
| `communication.send_whatsapp` | page | 29 | send whatsapp |
| `communication.sms-dlr-report` | page | 101 | sms dlr report |
| `communication.sms-dlr-upload` | page | 50 | sms dlr upload |
| `communication.templates.create` | page | 29 | create form |
| `communication.templates.edit` | page | 30 | edit form |
| `communication.templates.index` | page | 69 | list/index page |
| `communication.templates.partials.form` | partial | 53 | shared form partial |
| `communication.templates.partials.placeholder-selector` | partial | 161 | partial/include |
| `communication.templates.send_email` | page | 52 | email template |
| `communication.templates.send_sms` | page | 52 | send sms |
| `communication.wasender_sessions` | page | 93 | wasender sessions |
| `communication.whatsapp-progress` | page | 166 | whatsapp progress |

### `students/` (43)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `students.alumni` | page | 186 | alumni |
| `students.archived` | page | 238 | archived |
| `students.bulk` | page | 68 | bulk |
| `students.bulk-parse` | page | 78 | bulk parse |
| `students.bulk_assign_categories` | page | 129 | bulk assign categories |
| `students.bulk_assign_streams` | page | 198 | bulk assign streams |
| `students.bulk_preview` | page | 84 | bulk preview |
| `students.category_change_preview` | page | 117 | category change preview |
| `students.create` | page | 368 | create form |
| `students.duplicate_report` | page | 175 | duplicate report |
| `students.edit` | page | 37 | edit form |
| `students.enrollment_report` | page | 180 | enrollment report |
| `students.index` | page | 452 | list/index page |
| `students.parents_contact` | page | 131 | parents contact |
| `students.partials.action-dropdown` | partial | 37 | partial/include |
| `students.partials.alerts` | partial | 11 | partial/include |
| `students.partials.breadcrumbs` | partial | 27 | partial/include |
| `students.partials.communications_tab` | partial | 191 | partial/include |
| `students.partials.details_modal_content` | partial | 402 | partial/include |
| `students.partials.duplicate_matches` | partial | 39 | partial/include |
| `students.partials.empty-state` | partial | 8 | partial/include |
| `students.partials.form` | partial | 797 | shared form partial |
| `students.partials.kemis_learner_fields` | partial | 127 | partial/include |
| `students.partials.kemis_parent_identity_fields` | partial | 50 | partial/include |
| `students.records.academic.create` | page | 134 | create form |
| `students.records.academic.edit` | page | 134 | edit form |
| `students.records.academic.index` | page | 97 | list/index page |
| `students.records.academic.show` | page | 66 | detail view |
| `students.records.activities.create` | page | 170 | create form |
| `students.records.activities.edit` | page | 157 | edit form |
| `students.records.activities.index` | page | 83 | list/index page |
| `students.records.activities.show` | page | 77 | detail view |
| `students.records.disciplinary.create` | page | 136 | create form |
| `students.records.disciplinary.edit` | page | 118 | edit form |
| `students.records.disciplinary.index` | page | 85 | list/index page |
| `students.records.disciplinary.show` | page | 88 | detail view |
| `students.records.medical.create` | page | 133 | create form |
| `students.records.medical.edit` | page | 101 | edit form |
| `students.records.medical.index` | page | 81 | list/index page |
| `students.records.medical.show` | page | 66 | detail view |
| `students.show` | page | 1026 | detail view |
| `students.update_import` | page | 83 | update import |
| `students.update_import_preview` | page | 110 | update import preview |

### `website/` (38)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `website.admissions.index` | page | 17 | list/index page |
| `website.admissions.show` | page | 88 | detail view |
| `website.ai.index` | page | 30 | list/index page |
| `website.analytics.index` | page | 17 | list/index page |
| `website.assistant.index` | page | 29 | list/index page |
| `website.blogs._form` | partial | 10 | shared form partial |
| `website.blogs.create` | page | 14 | create form |
| `website.blogs.edit` | page | 11 | edit form |
| `website.blogs.index` | page | 15 | list/index page |
| `website.brand.index` | page | 89 | list/index page |
| `website.builder.show` | page | 117 | detail view |
| `website.calendar.index` | page | 32 | list/index page |
| `website.campaigns.index` | page | 17 | list/index page |
| `website.community.index` | page | 16 | list/index page |
| `website.conversion.index` | page | 46 | list/index page |
| `website.enquiries.index` | page | 15 | list/index page |
| `website.enquiries.show` | page | 20 | detail view |
| `website.events._form` | partial | 11 | shared form partial |
| `website.events.create` | page | 12 | create form |
| `website.events.edit` | page | 11 | edit form |
| `website.events.index` | page | 14 | list/index page |
| `website.faqs.index` | page | 20 | list/index page |
| `website.homepage.index` | page | 93 | list/index page |
| `website.meals.index` | page | 19 | list/index page |
| `website.media.index` | page | 131 | list/index page |
| `website.newsletter.index` | page | 10 | list/index page |
| `website.pages._form` | partial | 41 | shared form partial |
| `website.pages.create` | page | 33 | create form |
| `website.pages.edit` | page | 32 | edit form |
| `website.pages.index` | page | 62 | list/index page |
| `website.pages.preview` | page | 15 | preview |
| `website.partials.header` | partial | 14 | partial/include |
| `website.partials.section-edit-form` | partial | 41 | shared form partial |
| `website.seo.engine` | page | 32 | engine |
| `website.seo.index` | page | 29 | list/index page |
| `website.settings.edit` | page | 111 | edit form |
| `website.showcase.index` | page | 26 | list/index page |
| `website.testimonials.index` | page | 33 | list/index page |

### `dashboard/` (34)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `dashboard.admin` | page | 116 | dashboard |
| `dashboard.finance` | page | 101 | dashboard |
| `dashboard.parent` | page | 37 | dashboard |
| `dashboard.partials.absence_table` | partial | 34 | dashboard |
| `dashboard.partials.activity` | partial | 34 | dashboard |
| `dashboard.partials.alerts` | partial | 18 | dashboard |
| `dashboard.partials.announcements` | partial | 28 | dashboard |
| `dashboard.partials.attendance_chart` | partial | 9 | dashboard |
| `dashboard.partials.attendance_line` | partial | 50 | dashboard |
| `dashboard.partials.behaviour_widget` | partial | 49 | dashboard |
| `dashboard.partials.charts_js_bootstrap` | partial | 52 | dashboard |
| `dashboard.partials.enrolment_chart` | partial | 7 | dashboard |
| `dashboard.partials.exam_performance` | partial | 11 | dashboard |
| `dashboard.partials.exam_subject_avgs` | partial | 48 | dashboard |
| `dashboard.partials.filters` | partial | 138 | dashboard |
| `dashboard.partials.finance_donut` | partial | 27 | dashboard |
| `dashboard.partials.flash` | partial | 2 | dashboard |
| `dashboard.partials.invoice_table` | partial | 64 | dashboard |
| `dashboard.partials.kpis` | partial | 106 | dashboard |
| `dashboard.partials.outstanding_students` | partial | 62 | dashboard |
| `dashboard.partials.overview` | partial | 32 | dashboard |
| `dashboard.partials.quick_actions` | partial | 40 | dashboard |
| `dashboard.partials.recent_admissions` | partial | 47 | dashboard |
| `dashboard.partials.students` | partial | 41 | dashboard |
| `dashboard.partials.styles` | partial | 201 | dashboard |
| `dashboard.partials.summary` | partial | 24 | dashboard |
| `dashboard.partials.system_health` | partial | 15 | dashboard |
| `dashboard.partials.today_trips` | partial | 59 | dashboard |
| `dashboard.partials.transport_widget` | partial | 34 | dashboard |
| `dashboard.partials.upcoming` | partial | 17 | dashboard |
| `dashboard.student` | page | 30 | dashboard |
| `dashboard.supervisor` | page | 313 | dashboard |
| `dashboard.teacher` | page | 424 | dashboard |
| `dashboard.transport` | page | 66 | dashboard |

### `hr/` (34)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `hr.Profile_changes.index` | page | 135 | list/index page |
| `hr.Profile_changes.show` | page | 154 | detail view |
| `hr.access_lookups` | page | 345 | access lookups |
| `hr.analytics.index` | page | 422 | list/index page |
| `hr.payroll.advances.create` | page | 154 | create form |
| `hr.payroll.advances.edit` | page | 143 | edit form |
| `hr.payroll.advances.index` | page | 178 | list/index page |
| `hr.payroll.advances.show` | page | 287 | detail view |
| `hr.payroll.custom-deductions.create` | page | 192 | create form |
| `hr.payroll.custom-deductions.edit` | page | 141 | edit form |
| `hr.payroll.custom-deductions.index` | page | 197 | list/index page |
| `hr.payroll.custom-deductions.show` | page | 224 | detail view |
| `hr.payroll.deduction-types.create` | page | 164 | create form |
| `hr.payroll.deduction-types.edit` | page | 167 | edit form |
| `hr.payroll.deduction-types.index` | page | 141 | list/index page |
| `hr.payroll.deduction-types.show` | page | 166 | detail view |
| `hr.payroll.imports.budget` | page | 63 | budget |
| `hr.payroll.imports.budget_verify` | page | 102 | budget verify |
| `hr.payroll.partials.styles` | partial | 187 | partial/include |
| `hr.payroll.payslips._body` | partial | 163 |  body |
| `hr.payroll.payslips.pdf` | page | 30 | PDF template |
| `hr.payroll.payslips.show` | page | 40 | detail view |
| `hr.payroll.payslips.staff` | page | 59 | staff |
| `hr.payroll.periods.create` | page | 92 | create form |
| `hr.payroll.periods.index` | page | 126 | list/index page |
| `hr.payroll.periods.show` | page | 304 | detail view |
| `hr.payroll.records.index` | page | 171 | list/index page |
| `hr.payroll.records.pdf` | page | 126 | PDF template |
| `hr.payroll.records.show` | page | 337 | detail view |
| `hr.payroll.salary-structures.create` | page | 140 | create form |
| `hr.payroll.salary-structures.edit` | page | 125 | edit form |
| `hr.payroll.salary-structures.index` | page | 154 | list/index page |
| `hr.payroll.salary-structures.show` | page | 141 | detail view |
| `hr.reports.index` | page | 215 | list/index page |

### `staff/` (29)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `staff.archive` | page | 173 | archive |
| `staff.attendance.gate-logs` | page | 125 | gate logs |
| `staff.attendance.index` | page | 233 | list/index page |
| `staff.attendance.my-report` | page | 166 | my report |
| `staff.attendance.report` | page | 391 | report |
| `staff.create` | page | 56 | create form |
| `staff.documents.create` | page | 83 | create form |
| `staff.documents.index` | page | 164 | list/index page |
| `staff.documents.show` | page | 127 | detail view |
| `staff.edit` | page | 93 | edit form |
| `staff.index` | page | 452 | list/index page |
| `staff.leave_balances.create` | page | 83 | create form |
| `staff.leave_balances.index` | page | 133 | list/index page |
| `staff.leave_balances.show` | page | 132 | detail view |
| `staff.leave_requests.create` | page | 117 | create form |
| `staff.leave_requests.index` | page | 237 | list/index page |
| `staff.leave_requests.show` | page | 192 | detail view |
| `staff.leave_types.create` | page | 89 | create form |
| `staff.leave_types.edit` | page | 89 | edit form |
| `staff.leave_types.index` | page | 120 | list/index page |
| `staff.partials.form` | partial | 353 | shared form partial |
| `staff.partials.staff_bank_statutory` | partial | 37 | partial/include |
| `staff.profile` | page | 263 | profile |
| `staff.registrations.index` | page | 119 | list/index page |
| `staff.registrations.public_form` | page | 340 | shared form partial |
| `staff.registrations.show` | page | 157 | detail view |
| `staff.show` | page | 458 | detail view |
| `staff.upload` | page | 69 | upload |
| `staff.upload_verify` | page | 247 | upload verify |

### `components/` (28)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `components.badge` | component | 13 | badge |
| `components.button` | component | 26 | button |
| `components.card` | component | 14 | card |
| `components.data.actions` | component | 5 | actions |
| `components.data.filter-bar` | component | 10 | filter bar |
| `components.data.pagination` | component | 5 | pagination |
| `components.data.stat-card` | component | 10 | stat card |
| `components.data.table` | component | 8 | table |
| `components.divider` | component | 2 | divider |
| `components.feedback.alert` | component | 7 | alert |
| `components.feedback.confirmation-modal` | component | 14 | confirmation modal |
| `components.feedback.empty-state` | component | 8 | empty state |
| `components.feedback.flash` | component | 7 | flash |
| `components.feedback.loading` | component | 6 | loading |
| `components.form.checkbox` | component | 8 | shared form partial |
| `components.form.error` | component | 5 | shared form partial |
| `components.form.field` | component | 12 | shared form partial |
| `components.form.input` | component | 8 | shared form partial |
| `components.form.radio` | component | 8 | shared form partial |
| `components.form.select` | component | 13 | shared form partial |
| `components.form.textarea` | component | 7 | shared form partial |
| `components.icon-button` | component | 15 | icon button |
| `components.nav.breadcrumb` | component | 13 | breadcrumb |
| `components.nav.page-actions` | component | 3 | page actions |
| `components.nav.tabs` | component | 12 | tabs |
| `components.page-header` | component | 10 | page header |
| `components.section` | component | 11 | section |
| `components.student-search` | component | 210 | student search |

### `vendor/` (26)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `vendor.mail.html.button` | vendor | 25 | email template |
| `vendor.mail.html.footer` | vendor | 12 | email template |
| `vendor.mail.html.header` | vendor | 13 | email template |
| `vendor.mail.html.layout` | vendor | 59 | layout wrapper |
| `vendor.mail.html.message` | vendor | 28 | email template |
| `vendor.mail.html.panel` | vendor | 15 | email template |
| `vendor.mail.html.subcopy` | vendor | 8 | email template |
| `vendor.mail.html.table` | vendor | 4 | email template |
| `vendor.mail.text.button` | vendor | 2 | email template |
| `vendor.mail.text.footer` | vendor | 2 | email template |
| `vendor.mail.text.header` | vendor | 2 | email template |
| `vendor.mail.text.layout` | vendor | 10 | layout wrapper |
| `vendor.mail.text.message` | vendor | 28 | email template |
| `vendor.mail.text.panel` | vendor | 2 | email template |
| `vendor.mail.text.subcopy` | vendor | 2 | email template |
| `vendor.mail.text.table` | vendor | 2 | email template |
| `vendor.notifications.email` | vendor | 59 | email template |
| `vendor.pagination.bootstrap-4` | vendor | 47 | bootstrap 4 |
| `vendor.pagination.bootstrap-5` | vendor | 89 | bootstrap 5 |
| `vendor.pagination.default` | vendor | 47 | default |
| `vendor.pagination.semantic-ui` | vendor | 37 | semantic ui |
| `vendor.pagination.simple-bootstrap-4` | vendor | 28 | simple bootstrap 4 |
| `vendor.pagination.simple-bootstrap-5` | vendor | 30 | simple bootstrap 5 |
| `vendor.pagination.simple-default` | vendor | 20 | simple default |
| `vendor.pagination.simple-tailwind` | vendor | 26 | simple tailwind |
| `vendor.pagination.tailwind` | vendor | 107 | tailwind |

### `settings/` (21)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `settings.academic.create_term` | page | 86 | create term |
| `settings.academic.create_year` | page | 48 | create year |
| `settings.academic.edit_term` | page | 92 | edit term |
| `settings.academic.edit_year` | page | 50 | edit year |
| `settings.academic.index` | page | 172 | list/index page |
| `settings.academic.term_holidays` | page | 196 | term holidays |
| `settings.academic_reports` | page | 71 | academic reports |
| `settings.access_lookups` | page | 205 | access lookups |
| `settings.index` | page | 137 | list/index page |
| `settings.partials.branding` | partial | 241 | partial/include |
| `settings.partials.features` | partial | 41 | partial/include |
| `settings.partials.gallery` | partial | 59 | partial/include |
| `settings.partials.general` | partial | 48 | partial/include |
| `settings.partials.ids` | partial | 43 | partial/include |
| `settings.partials.modules` | partial | 94 | partial/include |
| `settings.partials.placeholders` | partial | 109 | partial/include |
| `settings.partials.regional` | partial | 34 | partial/include |
| `settings.partials.styles` | partial | 416 | partial/include |
| `settings.partials.system` | partial | 208 | partial/include |
| `settings.school_days.index` | page | 172 | list/index page |
| `settings.term_days.index` | page | 205 | list/index page |

### `pos/` (20)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `pos.discounts.create` | page | 86 | create form |
| `pos.discounts.index` | page | 137 | list/index page |
| `pos.orders.index` | page | 155 | list/index page |
| `pos.orders.show` | page | 167 | detail view |
| `pos.products.create` | page | 161 | create form |
| `pos.products.create-uniform` | page | 130 | create uniform |
| `pos.products.edit` | page | 167 | edit form |
| `pos.products.index` | page | 210 | list/index page |
| `pos.products.show` | page | 167 | detail view |
| `pos.public-links.create` | page | 53 | create form |
| `pos.public-links.index` | page | 80 | list/index page |
| `pos.shop.checkout` | page | 118 | checkout |
| `pos.shop.index` | page | 207 | list/index page |
| `pos.shop.order-confirmation` | page | 70 | order confirmation |
| `pos.teacher-requirements.index` | page | 74 | list/index page |
| `pos.teacher-requirements.show` | page | 66 | detail view |
| `pos.uniforms.backorders` | page | 68 | backorders |
| `pos.uniforms.index` | page | 77 | list/index page |
| `pos.uniforms.manage-sizes` | page | 64 | manage sizes |
| `pos.uniforms.show` | page | 97 | detail view |

### `inventory/` (19)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `inventory.items.create` | page | 82 | create form |
| `inventory.items.edit` | page | 89 | edit form |
| `inventory.items.index` | page | 126 | list/index page |
| `inventory.items.show` | page | 123 | detail view |
| `inventory.reports.receipts` | page | 100 | receipts |
| `inventory.reports.requirements` | page | 157 | requirements |
| `inventory.requirement-template-assignments.create` | page | 169 | create form |
| `inventory.requirement-template-assignments.edit` | page | 168 | edit form |
| `inventory.requirement-template-assignments.index` | page | 145 | list/index page |
| `inventory.requirement-templates.create` | page | 181 | create form |
| `inventory.requirement-templates.edit` | page | 173 | edit form |
| `inventory.requirement-templates.index` | page | 151 | list/index page |
| `inventory.requirement-types.index` | page | 145 | list/index page |
| `inventory.requisitions.create` | page | 145 | create form |
| `inventory.requisitions.index` | page | 117 | list/index page |
| `inventory.requisitions.show` | page | 134 | detail view |
| `inventory.student-requirements.collect` | page | 360 | collect |
| `inventory.student-requirements.index` | page | 162 | list/index page |
| `inventory.student-requirements.show` | page | 103 | detail view |

### `transport/` (17)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `transport.create` | page | 54 | create form |
| `transport.daily-list.index` | page | 153 | list/index page |
| `transport.daily-list.print` | page | 119 | print layout |
| `transport.daily-list.print-vehicle` | page | 159 | print vehicle |
| `transport.driver_change_requests.index` | page | 147 | list/index page |
| `transport.edit` | page | 57 | edit form |
| `transport.import.form` | page | 196 | shared form partial |
| `transport.import.log` | page | 161 | import workflow |
| `transport.import.preview` | page | 479 | import workflow |
| `transport.index` | page | 221 | list/index page |
| `transport.partials.styles` | partial | 412 | partial/include |
| `transport.show` | page | 33 | detail view |
| `transport.special_assignments.create` | page | 179 | create form |
| `transport.special_assignments.index` | page | 157 | list/index page |
| `transport.student_dropoffs.index` | page | 247 | list/index page |
| `transport.trip_attendance.create` | page | 142 | create form |
| `transport.trip_attendance.index` | page | 135 | list/index page |

### `reports/` (12)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `reports.class_reports.create` | page | 134 | create form |
| `reports.class_reports.index` | page | 78 | list/index page |
| `reports.heatmaps.show` | page | 76 | detail view |
| `reports.operations_facilities.create` | page | 96 | create form |
| `reports.operations_facilities.index` | page | 78 | list/index page |
| `reports.phone-normalization.index` | page | 100 | list/index page |
| `reports.staff_weekly.create` | page | 119 | create form |
| `reports.staff_weekly.index` | page | 78 | list/index page |
| `reports.student_followups.create` | page | 120 | create form |
| `reports.student_followups.index` | page | 78 | list/index page |
| `reports.subject_reports.create` | page | 149 | create form |
| `reports.subject_reports.index` | page | 78 | list/index page |

### `teacher/` (12)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `teacher.advances.create` | page | 75 | create form |
| `teacher.advances.index` | page | 92 | list/index page |
| `teacher.fee_clearance.index` | page | 117 | list/index page |
| `teacher.leave.create` | page | 181 | create form |
| `teacher.leave.index` | page | 151 | list/index page |
| `teacher.leave.show` | page | 125 | detail view |
| `teacher.salary.index` | page | 179 | list/index page |
| `teacher.salary.payslip` | page | 157 | payslip |
| `teacher.students.index` | page | 103 | list/index page |
| `teacher.students.show` | page | 215 | detail view |
| `teacher.transport.index` | page | 119 | list/index page |
| `teacher.transport.show` | page | 92 | detail view |

### `partials/` (11)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `partials.academic_term_options` | partial | 12 | partial/include |
| `partials.academic_year_term_filter_script` | partial | 88 | partial/include |
| `partials.academic_year_term_selects` | partial | 65 | partial/include |
| `partials.alerts` | partial | 9 | partial/include |
| `partials.country_code_options` | partial | 19 | partial/include |
| `partials.directory_export_modal` | partial | 290 | partial/include |
| `partials.email-form` | partial | 27 | shared form partial |
| `partials.password-fields` | partial | 152 | partial/include |
| `partials.sms-form` | partial | 27 | shared form partial |
| `partials.student_live_search` | partial | 43 | partial/include |
| `partials.student_search_modal` | partial | 179 | partial/include |

### `auth/` (10)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `auth.login` | page | 373 | login |
| `auth.open-play-store` | page | 36 | open play store |
| `auth.partials.app-download-cta` | partial | 33 | partial/include |
| `auth.passwords.change` | page | 48 | change |
| `auth.passwords.confirm` | page | 50 | confirm |
| `auth.passwords.email` | page | 88 | email template |
| `auth.passwords.reset` | page | 56 | reset |
| `auth.passwords.reset-otp` | page | 81 | reset otp |
| `auth.register` | page | 78 | register |
| `auth.verify` | page | 29 | verify |

### `errors/` (10)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `errors.401` | error | 6 | 401 |
| `errors.402` | error | 6 | 402 |
| `errors.403` | error | 6 | 403 |
| `errors.404` | error | 56 | 404 |
| `errors.419` | error | 6 | 419 |
| `errors.429` | error | 6 | 429 |
| `errors.500` | error | 6 | 500 |
| `errors.503` | error | 6 | 503 |
| `errors.layout` | error | 74 | layout wrapper |
| `errors.minimal` | error | 40 | minimal |

### `layouts/` (10)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `layouts.app` | page | 1226 | layout wrapper |
| `layouts.partials.branding-vars` | partial | 16 | partial/include |
| `layouts.partials.favicon` | partial | 20 | partial/include |
| `layouts.partials.header-search` | partial | 8 | partial/include |
| `layouts.partials.mobile-bottom-nav` | partial | 66 | partial/include |
| `layouts.partials.nav-admin` | partial | 1105 | partial/include |
| `layouts.partials.nav-section` | partial | 2 | partial/include |
| `layouts.partials.nav-senior-teacher` | partial | 706 | partial/include |
| `layouts.partials.nav-teacher` | partial | 392 | partial/include |
| `layouts.partials.nav-website-cms` | partial | 55 | partial/include |

### `attendance/` (9)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `attendance.at_risk` | page | 134 | at risk |
| `attendance.consecutive_absences` | page | 145 | consecutive absences |
| `attendance.edit` | page | 106 | edit form |
| `attendance.mark` | page | 506 | mark |
| `attendance.reason_codes.create` | page | 83 | create form |
| `attendance.reason_codes.edit` | page | 82 | edit form |
| `attendance.reason_codes.index` | page | 104 | list/index page |
| `attendance.reports` | page | 524 | reports |
| `attendance.student_analytics` | page | 180 | student analytics |

### `families/` (8)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `families.index` | page | 204 | list/index page |
| `families.integrity_missing_contacts` | page | 327 | integrity missing contacts |
| `families.integrity_report` | page | 260 | integrity report |
| `families.link` | page | 235 | link |
| `families.manage` | page | 344 | manage |
| `families.partials.families_hub_styles` | partial | 58 | partial/include |
| `families.partials.quick_contact_modal` | partial | 324 | partial/include |
| `families.populate-preview` | page | 142 | populate preview |

### `operations/` (7)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `operations.assets.create` | page | 76 | create form |
| `operations.assets.index` | page | 53 | list/index page |
| `operations.concerns.create` | page | 52 | create form |
| `operations.concerns.index` | page | 80 | list/index page |
| `operations.concerns.show` | page | 55 | detail view |
| `operations.visitors.create` | page | 68 | create form |
| `operations.visitors.index` | page | 66 | list/index page |

### `senior_teacher/` (7)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `senior_teacher.dashboard` | page | 487 | dashboard |
| `senior_teacher.fee_balances` | page | 217 | fee balances |
| `senior_teacher.partials.styles` | partial | 224 | partial/include |
| `senior_teacher.student_show` | page | 271 | student show |
| `senior_teacher.students` | page | 157 | students |
| `senior_teacher.supervised_classrooms` | page | 97 | supervised classrooms |
| `senior_teacher.supervised_staff` | page | 98 | supervised staff |

### `activities/` (6)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `activities.fees.attendance` | page | 101 | attendance |
| `activities.fees.index` | page | 56 | list/index page |
| `activities.fees.records` | page | 68 | records |
| `activities.fees.roster_print` | page | 48 | roster print |
| `activities.fees.show` | page | 79 | detail view |
| `activities.parent_requests.index` | page | 99 | list/index page |

### `student_assignments/` (6)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `student_assignments.bulk_assign` | page | 178 | bulk assign |
| `student_assignments.create` | page | 78 | create form |
| `student_assignments.edit` | page | 78 | edit form |
| `student_assignments.index` | page | 560 | list/index page |
| `student_assignments.partials.trip_options` | partial | 16 | partial/include |
| `student_assignments.show` | page | 67 | detail view |

### `swimming/` (6)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `swimming.attendance.create` | page | 166 | create form |
| `swimming.attendance.index` | page | 401 | list/index page |
| `swimming.payments.create` | page | 317 | create form |
| `swimming.settings.index` | page | 105 | list/index page |
| `swimming.wallets.index` | page | 451 | list/index page |
| `swimming.wallets.show` | page | 228 | detail view |

### `dropoffpoints/` (5)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `dropoffpoints.create` | page | 59 | create form |
| `dropoffpoints.edit` | page | 74 | edit form |
| `dropoffpoints.import` | page | 59 | import workflow |
| `dropoffpoints.index` | page | 137 | list/index page |
| `dropoffpoints.show` | page | 136 | detail view |

### `attendance_notifications/` (4)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `attendance_notifications.create` | page | 68 | create form |
| `attendance_notifications.edit` | page | 71 | edit form |
| `attendance_notifications.index` | page | 89 | list/index page |
| `attendance_notifications.notify` | page | 113 | notify |

### `emails/` (4)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `emails.credentials` | page | 6 | email template |
| `emails.generic` | page | 106 | email template |
| `emails.staff-welcome` | page | 14 | email template |
| `emails.system-alert` | page | 16 | email template |

### `events/` (4)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `events.calendar` | page | 82 | calendar |
| `events.create` | page | 154 | create form |
| `events.edit` | page | 157 | edit form |
| `events.show` | page | 109 | detail view |

### `family_update/` (4)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `family_update.admin.index` | page | 285 | list/index page |
| `family_update.partials.feedback` | partial | 73 | partial/include |
| `family_update.partials.media_picker` | partial | 34 | partial/include |
| `family_update.public_form` | page | 1037 | shared form partial |

### `trips/` (4)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `trips.assign` | page | 133 | assign |
| `trips.create` | page | 93 | create form |
| `trips.edit` | page | 104 | edit form |
| `trips.index` | page | 159 | list/index page |

### `documents/` (3)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `documents.create` | page | 169 | create form |
| `documents.index` | page | 126 | list/index page |
| `documents.show` | page | 161 | detail view |

### `driver/` (3)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `driver.index` | page | 110 | list/index page |
| `driver.transport-sheet` | page | 148 | transport sheet |
| `driver.trip` | page | 139 | trip |

### `online_admissions/` (3)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `online_admissions.index` | page | 228 | list/index page |
| `online_admissions.public_form` | page | 612 | shared form partial |
| `online_admissions.show` | page | 489 | detail view |

### `student_categories/` (3)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `student_categories.create` | page | 41 | create form |
| `student_categories.edit` | page | 42 | edit form |
| `student_categories.index` | page | 69 | list/index page |

### `vehicles/` (3)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `vehicles.create` | page | 77 | create form |
| `vehicles.edit` | page | 88 | edit form |
| `vehicles.index` | page | 156 | list/index page |

### `activity-logs/` (2)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `activity-logs.index` | page | 128 | list/index page |
| `activity-logs.show` | page | 89 | detail view |

### `admin/` (2)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `admin.senior_teacher_assignments.edit` | page | 271 | edit form |
| `admin.senior_teacher_assignments.index` | page | 313 | list/index page |

### `exports/` (2)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `exports.directory_table` | page | 68 | directory table |
| `exports.enrollment_by_class` | page | 79 | enrollment by class |

### `family/` (2)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `family.reports.portal` | page | 375 | portal |
| `family.reports.show` | page | 88 | detail view |

### `legal/` (2)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `legal.privacy` | page | 529 | privacy |
| `legal.terms` | page | 232 | terms |

### `parent/` (2)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `parent.diaries.index` | page | 44 | list/index page |
| `parent.diaries.show` | page | 80 | detail view |

### `pdf/` (2)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `pdf.partials.footer` | partial | 8 | PDF template |
| `pdf.partials.header` | partial | 25 | PDF template |

### `backup_restore/` (1)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `backup_restore.index` | page | 170 | list/index page |

### `gallery/` (1)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `gallery.index` | page | 183 | list/index page |

### `home/` (1)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `home` | page | 24 | home |

### `parents/` (1)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `parents.credentials.index` | page | 225 | list/index page |

### `sms_logs/` (1)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `sms_logs.index` | page | 37 | list/index page |

### `system-logs/` (1)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `system-logs.index` | page | 413 | list/index page |

### `users/` (1)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `users.force-password-change` | page | 110 | force password change |

### `welcome/` (1)

| View name | Kind | Lines | Inferred purpose |
|---|---|---:|---|
| `welcome` | page | 282 | welcome |
