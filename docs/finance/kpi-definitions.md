# Finance KPI definitions (mobile + API)

Authoritative source for admin Outstanding / Fee balances: `StudentFeeStatementService::activeOverdueArrearsSummary()`  
Surfaced by `GET /finance/summary` and `GET /finance/fee-balances`.

| Term | Definition | Scope |
|------|------------|--------|
| **Outstanding** (dashboard primary) | Sum of open invoice balances that are **due/overdue** | Active students only (not archived / alumni). All academic years. |
| **Overdue** | Same as outstanding above | Same |
| **Students in arrears** | Distinct students with overdue open balance &gt; 0 | Same |
| **Upcoming** (`upcoming_balance_year`) | Open balances not yet due for the current term’s academic year | Year-statement roster (`schoolOutstandingSummary`) |
| **Year outstanding** (`outstanding_balance_year`) | Due fees for students on the current academic-year fee roster | Placement + fee roster; excludes upcoming |
| **Outstanding active** (`outstanding_balance_active`) | Sum of all open invoice balances for active students | Includes upcoming + overdue |
| **Payment** | Posted fee receipt (`payments` row, completed / reversed) | Ledger |
| **Transaction** | Inbound bank or M-Pesa C2B row awaiting / after reconciliation | Channel, not ledger |

## Cache

- `finance.active_overdue.v1` — 2 minutes; cleared by `forgetSchoolOutstandingCache()`
- `finance.school_outstanding.v3.{year}` — 5 minutes; same forget helper

## UI labels

Admin dashboard shows `finance_scope_label`: **Active students · overdue fees**.
