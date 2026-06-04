# 09 — Reporting Audit

> Inventory of every report surface (academic, finance, operational, management/HR), plus the reports a school leadership/board would expect that are **missing**.

---

## Shared reporting infrastructure

| Component | Role |
|-----------|------|
| `ReportGenerationService` | Builds `ReportCard` summaries from `ExamMark` |
| `ReportCardBatchService` | Full report-card PDF payload (marks, skills, attendance, branding) |
| `PDFExportService` | Generic DomPDF wrapper |
| `ExcelExportService` | Generic Excel wrapper (via jobs) |
| `AttendanceReportService` / `AttendanceAnalyticsService` | Attendance summaries, at-risk, trends |
| `App\Services\Academics\ExamReports\*` | `AnalyticsService`, `TrendsService`, `ClassSheetBuilder`, `SchoolWideTeacherRankingService` |
| Exports | `ClassSheetExport`, `ClassSheetsWorkbookExport`, `TermWorkbookExport`, `ArrayExport` |

---

## 1. Academic reports

| Report | Shows | Format | Controller/Service |
|--------|-------|--------|--------------------|
| Class mark sheet | Marks/ranks by exam/subject/type/term | Screen + Excel + PDF | `ExamReportsController` (+ `ClassSheetExport`) |
| Term workbook | All classes' term sheets | Excel | `ExamReportsController::exportTermWorkbook` |
| Teacher performance | Class/school teacher rankings | Screen | `ExamReportsController`; `SchoolWideTeacherRankingService` |
| Subject performance | Subject averages/spread | Screen | `ExamReportsController` |
| Student insights | Strengths/weaknesses | Screen | `ExamReportsController` |
| Exam analytics (legacy) | Avg/min/max, grade dist, top/bottom | Screen | `ExamAnalyticsController` |
| Trends / Insights / Mastery | Series performance, narrative, top/bottom subjects | **API/JSON only** | `ApiExamReportsController` (`TrendsService`/`AnalyticsService`) |
| Report card (per student / PDF / public) | Term summary, subjects, skills, remarks | Screen + PDF | `ReportCardController` + `ReportCardBatchService` |
| Term assessment rollup | Class term assessment grid | Screen | `ReportCardController::termAssessment` |
| Attendance records / report | Daily marks, totals, gender | Screen | `AttendanceController` + `AttendanceReportService` |
| At-risk / consecutive absences / student analytics | Below-threshold, N-day absences, trends | Screen | `AttendanceController` + `AttendanceAnalyticsService` |
| Weekly class / subject / staff reports | Narrative ops forms | Screen (CRUD) | `ClassReportController`, `SubjectReportController`, `StaffWeeklyController` |
| Student follow-up | At-risk/behavior flags | Screen | `StudentFollowupController` |
| Assessment heatmap | Avg % by class×subject (campus/week) | Screen | `HeatmapController` |
| Scheme of work / Lesson plan | CBC documents | PDF/Excel | `SchemeOfWorkController`, `LessonPlanController` |
| Lesson plan analytics | Expected vs submitted per teacher | Screen | `LessonPlanController::analytics` |
| Academic report forms | Admin-defined questionnaires | API | `ApiAcademicReportsController` |

## 2. Finance reports

| Report | Shows | Format | Controller |
|--------|-------|--------|-----------|
| Student / family statement | Invoices, payments, allocations, concessions, running balance | Screen + PDF + CSV | `StudentStatementController` |
| Fee balance / defaulters | Invoiced/paid/balance, BBF, plan status | Screen + CSV + PDF | `FeeBalanceController` |
| Fee clearance | Cleared vs pending per term | Screen + PDF by class/stream | `FeeClearanceReportController` |
| Fees comparison import | External vs system (read-only) | Screen | `FeesComparisonImportController` |
| Expense report | Lines + category/vendor summaries | Screen + CSV + PDF | `ExpenseReportController` |
| Accountant dashboard | Overdue plans, upcoming installments, high-risk balances | Screen | `AccountantDashboardController` |
| Finance dashboard | Collections, outstanding, KPIs | Screen | `DashboardController::financeDashboard` |
| Payment plan agreement | Installment agreement | PDF | `FeePaymentPlanController` |
| M-Pesa / C2B dashboards | STK/link/paybill stats, allocation status | Screen | `MpesaPaymentController` |
| Bank statement reconciliation | Imported lines + matching | Screen + PDF | `BankStatementController` |
| Transaction fix audit | Applied/reversed fixes | Screen + CSV | `TransactionFixAuditController` |
| Invoice register export | Filtered invoices | CSV | `InvoiceController::exportCsv` |
| Payment receipt | Single receipt | PDF | `PaymentController` |
| Payslip | Earnings/deductions | Screen + PDF | `PayslipController` |

## 3. Operational reports

| Report | Shows | Format | Controller |
|--------|-------|--------|-----------|
| Daily transport list | Present students with transport by vehicle/class | Screen + Excel + PDF | `DailyTransportListController` |
| Transport dashboard | Trips/vehicles summary | Screen | `DashboardController::transportDashboard` |
| Swimming – unpaid / wallet / revenue | Unpaid sessions, balances, revenue vs sessions | Screen | `SwimmingReportController` |
| Operations & facilities (weekly) | Facility status/issues/actions | Screen (CRUD) | `OperationsFacilityController` |
| Staff attendance report | Date-range, summary, geofence map | Screen | `StaffAttendanceController` |
| Phone normalization audit | Phone change log | Screen | `PhoneNormalizationReportController` |
| Family integrity | Duplicate phones/emails, missing contacts | Screen | `FamilyIntegrityReportController` |
| Library / Inventory | Lists/workflow only — **no analytics report controller** | Screen | `BookBorrowingController`, `RequisitionController` |

## 4. Management / HR reports

| Report | Shows | Format | Controller |
|--------|-------|--------|-----------|
| Staff directory / department / category | HR rosters with filters | Excel | `StaffReportController` |
| New hires / terminations / turnover | Hiring & attrition | Excel | `StaffReportController` |
| HR analytics dashboard | Headcount, dept/category, leave, attendance, mix | Screen | `HRAnalyticsController` |
| Payroll period / records | Period totals, per-staff lines | Screen | `PayrollPeriodController`, `PayrollRecordController` |
| Staff weekly | Lessons missed, schemes updated, class control | Screen (CRUD) | `StaffWeeklyController` |
| Admin / role dashboards | Students, attendance, fees, exams, transport KPIs | Screen | `DashboardController` |

`app/Models/Reports/`: `ClassReport`, `SubjectReport`, `StudentFollowup`, `StaffWeekly`, `OperationsFacility`.

---

## 5. Missing reports (no matching classes found)

| Expected capability | Status | Note |
|---------------------|--------|------|
| **Enrollment / intake trends** | ❌ | `enrollment_year`/`term` used for fees only, not analytics |
| **Retention / attrition / alumni cohort** | ❌ | alumni flag exists; no cohort analysis |
| **P&L / Balance sheet / Cash flow** | ❌ | no GL financial statements |
| **Budget vs actual** | ❌ | no budget model |
| **Fee collection forecasting** | ❌ | only overdue/upcoming (no statistical forecast) |
| **Teacher workload report** | ⚠️ | partial via lesson-plan analytics |
| **Curriculum coverage / scheme compliance** | ❌ | no automated CBC coverage % |
| **KNEC / national assessment reports** | ❌ | no KNEC export |
| **CBC compliance / portfolio reporting** | ❌ | models exist; no board-ready export |
| **Board / governance dashboard** | ❌ | admin dashboard is operational, not trustee-level |
| **Cohort analysis (intake vs outcomes)** | ❌ | — |
| **Library utilization / overdue analytics** | ❌ | CRUD only |
| **Inventory valuation / consumption** | ❌ | requisitions only |
| **Exam trends/mastery on web** | ⚠️ | API-only (no web route) |

---

## 6. Recommendations (reporting strategy → [`10-future-state.md`](./10-future-state.md) §12)

1. **Build an Analytics layer** (warehouse or read-replica + BI) feeding role dashboards: executive/board, finance, academic, operations, HR.
2. **Leadership/board pack:** enrollment & retention trends, fee-collection rate & forecast, academic performance trends, staff attendance/turnover, risk register — exportable PDF.
3. **Financial statements** once GL exists (trial balance, P&L, BS, cash flow, budget vs actual).
4. **CBC compliance reports:** curriculum coverage %, performance-level distributions by strand, portfolio completeness, KNEC submission packs.
5. **Self-service report builder** (saved filters, scheduled email delivery, Excel/PDF) to reduce bespoke controllers.
6. **Promote API-only reports** (trends/insights/mastery) to web + mobile parity.
7. **Standardize export** (one export service, consistent branding, async for large jobs).
