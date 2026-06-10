# Sprint 16 — Backend Unblock Phase

This phase closed the "backend-blocked backlog" from the Sprint 11–15 report by adding the
missing Laravel API endpoints and wiring them end-to-end into the admin app.

## New backend endpoints (`routes/api.php`, `auth:sanctum`)

| Endpoint | Controller | Purpose |
| --- | --- | --- |
| `GET /communication/templates/{id}` | `ApiCommunicationController@templateShow` | Template detail |
| `GET /communication/logs/{id}` | `ApiCommunicationController@logShow` | SMS log detail |
| `GET /communication/recipients` | `ApiCommunicationController@recipients` | Parent SMS contacts (whole school or per classroom, de-duped by phone, respects per-parent mute settings) |
| `GET /visitors/{id}` | `ApiVisitorsController@show` | Visitor detail |
| `GET /reports/weekly/{type}/{id}` | `ApiWeeklyReportsController@show` | Full weekly report body for all 5 report types |
| `GET /expenses` | `ApiExpensesController@index` | Paginated expense registry (status/search/date filters) |
| `GET /expenses/{id}` | `ApiExpensesController@show` | Expense detail with lines, categories, vouchers |
| `GET /cbc/learning-areas` | `ApiCbcController@learningAreas` | Active learning areas with strand counts |
| `GET /cbc/strands` | `ApiCbcController@strands` | Strands (filter by `learning_area_id`) |
| `GET /cbc/substrands` | `ApiCbcController@substrands` | Sub-strands (filter by `strand_id`) |
| `GET /cbc/substrands/{id}` | `ApiCbcController@substrandShow` | Sub-strand detail: outcomes, KIQs, values, PCLC, competencies + indicators |

## Core package (`@erp/core`)

- `communication.api`: `getTemplate`, `getLog`, `listRecipients` (+ `SmsRecipient` types).
- `operations.api`: `getVisitor`.
- `reports.api`: `getWeeklyReportDetail`, `listExpenses`, `getExpense` (+ `WeeklyReportDetail`, `ExpenseSummaryRecord`, `ExpenseDetailRecord`).
- New `cbc.api` module with full CBC types.
- New hooks: `useCommunicationTemplate`, `useCommunicationLog`, `useSmsRecipients`, `useVisitor`,
  `useWeeklyReportDetail`, `useInfiniteExpenses`, `useExpense`, `useCbcLearningAreas`,
  `useCbcStrands`, `useCbcSubstrands`, `useCbcSubstrand`.
- Query keys added for all of the above.

## Admin app changes

### Upgraded from client-side workarounds to real endpoints
- `SmsLogDetailScreen` — direct log fetch (was scanning first 100 list rows).
- `TemplateDetailScreen` — direct template fetch.
- `VisitorDetailScreen` — direct visitor fetch (+ badge number row).
- `WeeklyReportDetailScreen` — now renders the **full report body** (per-type field sets + notes)
  instead of the summary-only stub.

### New functionality
- **SMS recipient picker** (`SmsComposeScreen`): "Add parents by class" opens a bottom sheet with
  Whole school / per-class scope chips, shows matched contact counts, and merges de-duped parent
  phone numbers into the recipient field.
- **Expenses registry** (`ExpensesListScreen`): paginated, searchable, status-filterable list with
  amount + status badges. Linked from Reports hub, Expense reports screen, and the Finance
  dashboard "Accounting & reporting" section.
- **Expense detail** (`ExpenseDetailScreen`): overview, line items with qty/cost/tax, payment
  vouchers, and notes.
- **CBC curriculum browser** (Academics): `CbcCurriculumScreen` (learning areas) →
  `CbcStrandsScreen` (strands with expandable sub-strand accordion) → `CbcSubstrandScreen`
  (learning outcomes, key inquiry questions, core competencies, values, and competency cards with
  indicators). Entry point added to the Academics dashboard workspace grid.

### Navigation
- New routes registered in Academics and Reports stacks with deep links:
  - `academics/cbc`, `academics/cbc/:learningAreaId`, `academics/cbc/substrands/:substrandId`
  - `reports/expenses/all`, `reports/expenses/:expenseId`

## Verification
- `php -l` clean on all new/edited controllers; `php artisan route:list` confirms route registration.
- `npm run typecheck` (admin app) passes.
- No linter errors.

## Remaining backend-blocked items
- Template CRUD (create/edit from mobile), WhatsApp send channel.
- Requisition create, inventory stock adjustments, asset lifecycle writes, clinic visit CRUD.
- Expense create/approve workflow from mobile (read-only registry shipped this sprint).
- GL/journals, trial balance, P&L, balance sheet, budgets (no backend models/routes yet).
