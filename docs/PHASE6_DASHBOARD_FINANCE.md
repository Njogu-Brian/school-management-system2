# Phase 6 Dashboard and Finance IA

Date: 2026-09-09

## Dashboard redesign

The global admin dashboard is now an operational overview rather than a full report catalogue.

### Above the fold

- Active students
- Attendance today
- Collected fees or active staff, depending on role
- Outstanding fees or pending approvals, depending on role
- Critical operational alerts
- Permission-aware quick actions
- Finance snapshot for finance-capable roles
- Attendance trend summary

### Secondary detail retained

The following dashboard partials and data remain in the repository and continue to be available through their module dashboards/pages or can be reintroduced selectively later:

- Invoice table
- Absence table
- Exam performance
- Upcoming items
- Transport trips
- Overview
- Recent admissions
- Staff detail
- Recent activity
- Announcements
- Behaviour widget
- Enrolment chart
- System health

No controller query, KPI calculation, permission check, chart data source, report route, or underlying data was removed. `DashboardController::buildDashboardData()` remains the single data assembly path, avoiding duplicate dashboard queries.

### Query considerations

This phase changes the global dashboard view only. The controller still prepares the existing datasets, so no new query or N+1 behavior was introduced. Some secondary datasets remain prepared for compatibility with other role dashboards and existing partial consumers; query removal should be a separate measured optimization after route/view usage is profiled.

## Finance information architecture

The finance sidebar now uses two job-oriented internal labels:

### Student Billing

- Voteheads and fee structures
- Posting
- Optional and transport fees
- Discounts and fee concessions
- Invoices
- Credit/debit adjustments
- Payment plans
- Fee reminders
- Document settings
- Payments
- M-Pesa payment operations
- Bank statement payment matching
- Bank accounts and payment methods
- Swimming and activity fee charges
- Legacy finance imports

### Accounting

- Expenses
- Expense reports
- Payment vouchers
- Expense categories
- Statement analyzer and parsed transactions
- Chart of accounts
- Journal entries and manual journals
- Petty cash
- Fiscal periods
- Budgets
- General-ledger reports
- Vendors

### Finance reporting and advanced areas retained

- Accountant dashboard
- Student statements
- Fee balance reports
- Fee clearance
- Payment thresholds
- Balance brought forward
- Fees comparison imports
- System reports

These are classification and navigation-label changes only. All finance routes, controllers, middleware, calculations, imports, payment integrations, and permissions remain unchanged.

### Routes intentionally untouched

No finance route was removed, renamed, redirected, or aliased in this phase. Public payment routes, webhooks, M-Pesa APIs, invoice/payment resources, statement workflows, accounting write actions, and legacy imports remain exactly where they were.

## Validation notes

- Blade compilation and editor diagnostics were run after the dashboard changes.
- Frontend asset compilation and diff checks remain required before release.
- Browser viewport review should be performed at desktop, tablet, and phone widths; the dashboard uses responsive Bootstrap grids and the shared design-system stat-card component.