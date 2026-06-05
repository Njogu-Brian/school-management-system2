# Sprint 13 — Deferred Items Completion Report

**Date:** 2026-06-04  
**Scope:** Complete all deferred Admin App items on **Laravel backend + web portals**; wire mobile admin app as API consumer only.

---

## Architecture principle

| Layer | Role |
|-------|------|
| **Laravel (`routes/api.php`, `routes/web.php`)** | Source of truth — business logic, storage, PDF generation |
| **Web portals** | Existing modules + new Operations visitor/asset screens |
| **Mobile (`mobile-app/`)** | React Native frontend consuming Sanctum APIs only |

---

## Laravel API endpoints added

| Endpoint | Controller | Notes |
|----------|------------|-------|
| `GET …/documents/{id}/download` | `ApiStudentDocumentsController`, `ApiStaffDocumentsController` | Streams files from public/private disks |
| `GET /payroll-records/{id}/payslip/download` | `ApiPayslipController` | Wraps `Hr\PayslipController` PDF |
| `GET /staff/{id}/performance-reviews` | `ApiStaffPerformanceController` | Filled `PerformanceReview` model |
| `GET /staff/{id}/training-records` | `ApiStaffTrainingController` | Filled `TrainingRecord` model |
| `GET /students/{id}/medical-records` | `ApiMedicalRecordsController` | Wraps student clinic records |
| `GET /inventory/items` | `ApiInventoryController` | Low-stock filter supported |
| `GET/POST /requisitions/*` | `ApiRequisitionController` | Approve/reject delegates to web controller |
| `GET/POST /communication/*` | `ApiCommunicationController` | Templates, logs, SMS send |
| `POST/PUT/DELETE /announcements` | `ApiAnnouncementController` | Full CRUD + push on publish |
| `GET /reports/weekly` | `ApiWeeklyReportsController` | Aggregates 5 weekly report models |
| `GET /reports/expenses/summary` | `ApiExpenseReportsController` | Mirrors web expense report |
| `GET /reports/board-pack` | `ApiBoardPackController` | Executive composite summary |
| `GET/POST /visitors` | `ApiVisitorsController` | Check-in / check-out |
| `GET /assets` | `ApiFixedAssetsController` | Fixed asset register |

**Extended:** `ApiOperationsSummaryController` (real inventory, facilities, visitors, assets counts), `ApiApprovalsController` (requisitions in unified inbox).

---

## Web portal additions (greenfield)

| Route prefix | Screens |
|--------------|---------|
| `/operations/visitors` | Index, create, check-out |
| `/operations/assets` | Index, register asset |

**Migrations:** `visitor_logs`, `fixed_assets`

---

## Mobile wiring

| Workspace | Changes |
|-----------|---------|
| **Staff 360** | Performance, Training, Documents download, Payslip PDF download |
| **Student 360** | Documents download, Health tab + clinic records API |
| **Operations** | Inventory, Requisitions, Visitors screens; dashboard KPIs |
| **Communication** | SMS compose screen; templates API |
| **Reports** | Board pack, expense summary, weekly reports widgets |
| **Dashboard** | Overview / Approvals / Alerts segmented tabs |

**Core package:** `reports.api.ts`, `staff360.api.ts`, `downloadFile` utility, extended hooks.

---

## Deploy checklist

1. Run migrations: `php artisan migrate`
2. Deploy Laravel (API + web views)
3. Smoke-test Sanctum endpoints with admin token
4. Ship mobile build pointing at production API

---

## Completion estimates (post-Sprint 13)

| Workspace | ~% |
|-----------|-----|
| Students / Student 360 | **92%** |
| Staff 360 | **88%** |
| Finance | **88%** |
| Approvals | **85%** |
| Operations | **75%** |
| Communication | **70%** |
| Reports | **72%** |
| Settings | **38%** (unchanged — deploy verification) |

**Overall v1 Admin App:** ~**92%** after production deploy + regression.

---

## Remaining (post-v1)

- Full announcement create/edit UI on mobile (API ready)
- Requisition approve UI with line-item quantities on mobile
- Visitor check-in form on mobile (API ready; list screen shipped)
- Settings hub production 404 verification
- Asset detail screen on mobile
