# Admin App — Full Regression Test Matrix

Use this checklist after deploying backend + mobile (Sprints 8–12). Mark each row: **Pass** / **Fail** / **N/A**.

**Prerequisites:** Admin account with broad permissions; production or staging API with latest `routes/api.php` deployed.

---

## Authentication

| # | Test | Expected |
|---|------|----------|
| A1 | Login with email/password | Lands on Dashboard or first allowed tab |
| A2 | Logout | Returns to login; token cleared |
| A3 | Re-login | Session restores permissions and nav areas |
| A4 | Permission loading | Drawer shows only allowed workspaces |

---

## Dashboard

| # | Test | Expected |
|---|------|----------|
| D1 | KPI widgets load | Enrollment, attendance, finance tiles show data or empty state |
| D2 | Quick action → Students | Opens Student registry tab |
| D3 | Quick action → Finance | Opens Finance collections |
| D4 | Quick action → Admissions | Opens Admissions drawer |
| D5 | Quick action → Approvals | Opens Approvals drawer |
| D6 | Pending approvals panel | Lists items; View all → Approvals |
| D7 | Alerts section | Shows pending approval cards or empty state |

---

## Students

| # | Test | Expected |
|---|------|----------|
| S1 | Registry list + search | Paginated students load |
| S2 | Filters (class, gender, fee) | List narrows correctly |
| S3 | Student detail — Overview | Header + summary widgets |
| S4 | Tab — Attendance | Calendar/trend loads |
| S5 | Tab — Academics | Report card history (if permitted) |
| S6 | Tab — Fees | Statement loads (if `finance.view`) |
| S7 | Tab — Family | Parent/guardian data |
| S8 | Tab — Health | Allergies, immunization from `GET /students/{id}` |
| S9 | Tab — Transport | Trip from `GET /routes/{id}` or empty state |
| S10 | Tab — Requirements | Checklist from teacher requirements API |
| S11 | Tab — Documents | List from `GET /students/{id}/documents` |

---

## Staff (People)

| # | Test | Expected |
|---|------|----------|
| P1 | Staff registry | List loads |
| P2 | Staff detail — Overview | Header + widgets |
| P3 | Tab — Employment | HR fields |
| P4 | Tab — Leave | Balances + requests |
| P5 | Tab — Attendance | Monthly history |
| P6 | Tab — Payroll | `GET /payroll-records?staff_id=` |
| P7 | Tab — Documents | `GET /staff/{id}/documents` |
| P8 | Tabs — Performance/Training | Empty state placeholders |

---

## Admissions

| # | Test | Expected |
|---|------|----------|
| AD1 | Applications list | Status filters work |
| AD2 | Application detail | Full application data |
| AD3 | Waitlist action | Updates status |
| AD4 | Reject action | Requires reason |
| AD5 | Enroll action | Completes enrollment flow |

---

## Finance

| # | Test | Expected |
|---|------|----------|
| F1 | Dashboard KPIs | Uses `GET /finance/summary` when deployed |
| F2 | Billing list + invoice detail | Invoice opens |
| F3 | Collections + payment detail | Payment opens |
| F4 | Statements | Student picker + statement |
| F5 | Reconciliation queue | Unassigned transactions |

---

## Academics

| # | Test | Expected |
|---|------|----------|
| AC1 | Dashboard KPIs | Exam + lesson plan counts |
| AC2 | Exams list + detail | Exam metadata |
| AC3 | Marks + matrix | Class marks load |
| AC4 | Report cards + detail | Shared report card screen |
| AC5 | Assessments flow | Student search → history → detail |
| AC6 | Moderation | Redirects to Approvals workspace |

---

## Approvals

| # | Test | Expected |
|---|------|----------|
| AP1 | Pending filter | Leave + lesson plans + admissions |
| AP2 | Approved / Rejected filters | History items |
| AP3 | Source type filter | Leave / lesson plan / admissions |
| AP4 | Leave approve/reject | Actions complete |
| AP5 | Lesson plan approve/reject | Actions complete |
| AP6 | Admission item | Opens application in Admissions |

---

## Operations (new)

| # | Test | Expected |
|---|------|----------|
| O1 | Dashboard KPIs | `GET /operations/summary` |
| O2 | Transport trips list | `GET /routes` |
| O3 | Trip detail | Stops + vehicle/driver |

---

## Communication (new)

| # | Test | Expected |
|---|------|----------|
| C1 | Dashboard preview | Recent announcements |
| C2 | Announcements list | `GET /announcements` paginated |

---

## Reports (new)

| # | Test | Expected |
|---|------|----------|
| R1 | Hub KPIs | `GET /dashboard/stats` |
| R2 | Link → Finance statements | Navigates correctly |
| R3 | Link → Academics report cards | Navigates correctly |
| R4 | Link → Operations transport | Navigates correctly |

---

## Settings

| # | Test | Expected |
|---|------|----------|
| ST1 | School settings | `GET /settings/school` — **must not 404** |
| ST2 | Academic years / terms | Load read-only |
| ST3 | Classes / streams / subjects | Load read-only |
| ST4 | Grading schemes | Load read-only |
| ST5 | Roles | Load read-only |
| ST6 | API diagnostics (dev) | Probes green when backend deployed |

---

## Deep linking (smoke)

| URL path | Expected screen |
|----------|-----------------|
| `students/1/health` | Student 360 Health tab |
| `finance/invoices/1` | Invoice detail |
| `approvals` | Approvals home |
| `operations/transport` | Trips list |
| `communication/announcements` | Announcements list |

---

## Sign-off

| Role | Name | Date | Result |
|------|------|------|--------|
| QA / Admin tester | | | |
| Backend deploy | | | |
| Mobile build | | | |
