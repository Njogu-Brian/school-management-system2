---

### 14. Fee Reminders ✅

**Description:** Automated and manual email/SMS reminders for overdue fees and payment plan installments

**Features:**
- ✅ Automated reminder system via scheduled jobs
- ✅ Manual reminder creation and sending
- ✅ Multiple channels: Email, SMS, WhatsApp (enhanced January 2026)
- ✅ Installment-aware reminders (links to payment plan installments)
- ✅ Template support with placeholders (student name, amount, due date, remaining balance)
- ✅ Reminder rules: before_due, on_due, after_overdue
- ✅ Automatic cancellation when installment paid or plan completes
- ✅ Outstanding balance calculation (includes balance brought forward)
- ✅ Hashed ID for secure public access
- ✅ Status tracking: pending, sent, failed, cancelled

**Database:**
- `fee_reminders` table (enhanced with payment_plan_id, payment_plan_installment_id, reminder_rule, WhatsApp support)

**Files:**
- `app/Models/FeeReminder.php`
- `app/Http/Controllers/Finance/FeeReminderController.php`
- `app/Jobs/SendFeeRemindersJob.php`

---

### 15. Student Fee Statements ✅

**Description:** Generate comprehensive fee statements for students with PDF/CSV export

**Features:**
- ✅ Complete student ledger view
- ✅ Shows all invoices, payments, discounts, credit/debit notes
- ✅ Balance calculations (including balance brought forward from legacy data)
- ✅ Filter by academic year and term
- ✅ PDF export with professional formatting
- ✅ CSV export for spreadsheet analysis
- ✅ Print functionality (opens in new window)
- ✅ Send via SMS/Email functionality
- ✅ Legacy data support (pre-2026 statements)

**Files:**
- `app/Http/Controllers/Finance/StudentStatementController.php`
- `resources/views/finance/student_statements/show.blade.php`

---

