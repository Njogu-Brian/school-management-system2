# Sprints 9–12 — Backend APIs + Remaining Workspaces

**Date:** 2026-06-05  
**Scope:** Complete backend APIs (Sprint 9), Operations workspace (Sprint 10), Communication workspace (Sprint 11), Reports hub (Sprint 12) for single-pass regression testing.

**Build health:** `tsc --noEmit` passes in `packages/core`, `packages/ui`, `apps/admin`.

---

## Executive summary

| Sprint | Deliverable | Status |
|--------|-------------|--------|
| **9** | Document APIs, finance summary, unified approvals | **Done** |
| **10** | Operations summary + Operations workspace MVP | **Done** |
| **11** | Communication workspace (announcements) | **Done** |
| **12** | Reports hub with cross-module deep-links | **Done** |

### Updated completion estimates

| Workspace | Post Sprint 8 | Post Sprint 9–12 |
|-----------|---------------|------------------|
| Students | ~88% | **~90%** |
| Student 360 | ~82% | **~90%** (documents live) |
| Staff 360 | ~75% | **~85%** (documents live) |
| Finance | ~82% | **~88%** (`/finance/summary`) |
| Approvals | ~78% | **~85%** (unified API + fallback) |
| Operations | 0% | **~55%** (transport MVP) |
| Communication | 0% | **~45%** (read-only announcements) |
| Reports | 0% | **~50%** (hub + deep-links) |
| Settings | ~38% | **~38%** (UI done; deploy backend) |
| Dashboard | ~68% | **~72%** |

**Overall Admin App v1 readiness: ~85–90%** for internal school rollout after production deploy + regression.

---

## Sprint 9 — Backend APIs

### New Laravel routes (`routes/api.php`)

| Method | Path | Controller |
|--------|------|------------|
| GET | `/students/{id}/documents` | `ApiStudentDocumentsController` |
| GET | `/staff/{id}/documents` | `ApiStaffDocumentsController` |
| GET | `/finance/summary` | `ApiFinanceSummaryController` |
| GET | `/operations/summary` | `ApiOperationsSummaryController` |
| GET | `/approvals` | `ApiApprovalsController` |
| GET | `/approvals/{compositeId}` | `ApiApprovalsController` |
| POST | `/approvals/{compositeId}/approve` | `ApiApprovalsController` |
| POST | `/approvals/{compositeId}/reject` | `ApiApprovalsController` |

### Mobile integration

| API | Consumer |
|-----|----------|
| Student/staff documents | `DocumentsTab` (Student 360 + Staff 360) |
| `/finance/summary` | `fetchFinanceDashboardKpis()` (preferred; falls back to composed KPIs) |
| `/approvals` | `fetchApprovalItems()` (preferred; falls back to client merge) |
| `/operations/summary` | Operations dashboard KPIs |

---

## Sprint 10 — Operations workspace

### Screens

| Screen | API |
|--------|-----|
| `OperationsDashboardScreen` | `GET /operations/summary` |
| `TripsListScreen` | `GET /routes` |
| `TripDetailScreen` | `GET /routes/{id}` |

### Navigation

- `OperationsStackNavigator` wired in drawer
- Deep links: `operations`, `operations/transport`, `operations/transport/:tripId`

**Deferred:** Inventory, clinic, visitors, security (no mobile APIs).

---

## Sprint 11 — Communication workspace

### Screens

| Screen | API |
|--------|-----|
| `CommunicationDashboardScreen` | `GET /announcements` (preview) |
| `AnnouncementsListScreen` | `GET /announcements` |

**Deferred:** SMS send, templates, delivery reports (web-only today).

---

## Sprint 12 — Reports hub

### Screens

| Screen | Behavior |
|--------|----------|
| `ReportsHubScreen` | KPIs from `GET /dashboard/stats` + deep-links to Finance, Academics, Operations |

**Deferred:** Unified board-pack PDFs, weekly ops reports API.

---

## Deploy checklist

1. **Deploy Laravel backend** with new API routes (same commit as mobile).
2. **Verify Settings Hub** routes (`GET /settings/*`) return 200 on production.
3. **Run regression matrix:** [`sprint-9-12-regression-test-matrix.md`](./sprint-9-12-regression-test-matrix.md)
4. **Build admin mobile** against deployed API host.

---

## Remaining Play Store blockers

| Priority | Item |
|----------|------|
| P0 | Production deploy of all new + settings API routes |
| P1 | SMS/communication compose APIs |
| P1 | Inventory/facilities operations APIs |
| P2 | Payslip PDF, performance reviews, training records |
| P2 | Dashboard segmented tabs, branch switcher |

---

*Sprints 9–12 completion report.*
