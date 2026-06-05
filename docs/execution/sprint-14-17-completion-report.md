# Sprints 14–17 — Mobile Admin Phase 1 Completion Report

**Date:** 2026-06-05  
**Scope:** Communication, Operations, Staff 360, and Reports Hub — mobile admin v1 feature completion.

---

## Summary

Implemented end-to-end mobile screens wired to existing Laravel Sanctum APIs. TypeScript check passes (`npm run typecheck` in `mobile-app/apps/admin`).

| Workspace | Pre-sprint | Post-sprint (est.) |
|-----------|------------|-------------------|
| Communication | ~70% | **~90%** |
| Operations | ~75% | **~92%** |
| Staff 360 | ~88% | **~95%** |
| Reports Hub | ~72% | **~88%** |
| **Overall Admin v1** | ~92% | **~96%** |

---

## Files added

### Shared utilities
- `mobile-app/apps/admin/src/features/shared/utils/formatters.ts`
- `mobile-app/apps/admin/src/features/shared/utils/feedback.ts`

### Communication (Sprint 14)
- `screens/AnnouncementDetailScreen.tsx`
- `screens/AnnouncementFormScreen.tsx`
- `screens/SmsHistoryScreen.tsx`
- `screens/SmsLogDetailScreen.tsx`
- `screens/TemplatesListScreen.tsx`
- `screens/TemplateDetailScreen.tsx`

### Operations (Sprint 15)
- `screens/VisitorDetailScreen.tsx`
- `screens/VisitorCheckInScreen.tsx`
- `screens/AssetsListScreen.tsx`
- `screens/AssetDetailScreen.tsx`
- `screens/RequisitionDetailScreen.tsx`

### Staff 360 (Sprint 16)
- `screens/PerformanceReviewDetailScreen.tsx`
- `screens/TrainingRecordDetailScreen.tsx`

### Reports (Sprint 17)
- `screens/BoardPackScreen.tsx`
- `screens/ExpenseReportsScreen.tsx`
- `screens/WeeklyReportsListScreen.tsx`
- `screens/WeeklyReportDetailScreen.tsx`

---

## Files modified

### `@erp/core`
- `packages/core/src/api/communication.api.ts` — extended types
- `packages/core/src/api/operations.api.ts` — visitors check-in/out, assets, requisition approve/reject
- `packages/core/src/api/staff360.api.ts` — performance/training show endpoints
- `packages/core/src/query/hooks/useCommunication.ts` — infinite queries, CRUD hooks
- `packages/core/src/query/hooks/useOperations.ts` — infinite queries, mutations
- `packages/core/src/query/hooks/useStaffPerformance.ts` — detail hooks
- `packages/core/src/query/queryKeys.ts` — new cache keys

### Admin app screens (enhanced)
- `CommunicationDashboardScreen.tsx`
- `AnnouncementsListScreen.tsx`
- `SmsComposeScreen.tsx`
- `OperationsDashboardScreen.tsx`
- `VisitorsListScreen.tsx`
- `RequisitionsListScreen.tsx`
- `ReportsHubScreen.tsx`
- `PerformanceTab.tsx`, `TrainingTab.tsx`
- `StaffDetailScreen.tsx`

### Navigation
- `communicationStackTypes.ts`, `CommunicationStackNavigator.tsx`
- `operationsStackTypes.ts`, `OperationsStackNavigator.tsx`
- `reportsStackTypes.ts`, `ReportsStackNavigator.tsx`
- `peopleStackTypes.ts`, `PeopleStackNavigator.tsx`
- Feature `index.ts` barrels (communication, operations, reports, people)

---

## APIs used

### Communication
| Method | Path | Mobile usage |
|--------|------|--------------|
| GET | `/announcements` | List, dashboard KPIs, infinite scroll |
| GET | `/announcements/{id}` | Detail |
| POST | `/announcements` | Create / draft |
| PUT | `/announcements/{id}` | Edit, publish/unpublish |
| DELETE | `/announcements/{id}` | Delete |
| GET | `/communication/templates` | Template list + compose |
| GET | `/communication/logs` | SMS history, filters, pagination |
| POST | `/communication/sms` | Compose send |

### Operations
| Method | Path | Mobile usage |
|--------|------|--------------|
| GET | `/operations/summary` | Dashboard KPIs |
| GET | `/visitors` | List, filters, pagination |
| POST | `/visitors` | Check-in |
| POST | `/visitors/{id}/checkout` | Check-out |
| GET | `/assets` | Asset list |
| GET | `/assets/{id}` | Asset detail |
| GET | `/requisitions` | List, status filter |
| GET | `/requisitions/{id}` | Detail + line items |
| POST | `/requisitions/{id}/approve` | Approve (partial qty) |
| POST | `/requisitions/{id}/reject` | Reject with reason |

### Staff 360
| Method | Path | Mobile usage |
|--------|------|--------------|
| GET | `/payroll-records?staff_id=` | Payslip list |
| GET | `/payroll-records/{id}/payslip/download` | PDF download/share |
| GET | `/staff/{id}/performance-reviews` | Performance list |
| GET | `/staff/{id}/performance-reviews/{id}` | Performance detail |
| GET | `/staff/{id}/training-records` | Training list |
| GET | `/staff/{id}/training-records/{id}` | Training detail |

### Reports
| Method | Path | Mobile usage |
|--------|------|--------------|
| GET | `/reports/board-pack` | Board pack dashboard |
| GET | `/reports/expenses/summary` | MTD/QTD/YTD expense breakdown |
| GET | `/reports/weekly` | Weekly reports list + detail cards |

---

## Screens created / registered

### Communication stack (10 screens)
CommunicationDashboard · AnnouncementsList · AnnouncementDetail · AnnouncementForm · SmsCompose · SmsHistory · SmsLogDetail · TemplatesList · TemplateDetail

### Operations stack (12 screens)
OperationsDashboard · TripsList · TripDetail · InventoryList · RequisitionsList · **RequisitionDetail** · VisitorsList · **VisitorDetail** · **VisitorCheckIn** · **AssetsList** · **AssetDetail**

### Reports stack (5 screens)
ReportsHub · **BoardPack** · **ExpenseReports** · **WeeklyReportsList** · **WeeklyReportDetail**

### People stack (+2 screens)
StaffRegistry · StaffDetail · **PerformanceReviewDetail** · **TrainingRecordDetail**

---

## Outstanding gaps (API / backend dependent)

| Feature | Gap |
|---------|-----|
| Announcement audience targeting | Model has no audience field — web-only concept |
| Announcement scheduled publish date | Uses `active` + `expires_at` only |
| Admin list of draft/inactive announcements | `GET /announcements` returns active non-expired only |
| SMS template CRUD | Read-only API; web `CommunicationTemplateController` |
| SMS schedule send | No API |
| SMS class/parent/staff recipient picker | API accepts raw phone numbers only |
| SMS export logs | No export endpoint |
| SMS retry failed | No retry endpoint |
| Visitor show by ID | Detail resolves from list cache |
| Asset assign/reassign/return | No mutation API |
| Training certificate download | No authenticated download route |
| Performance PDF export | No API |
| Board pack PDF / print | No mobile export API |
| Expense PDF/Excel export | Web-only routes |
| Weekly report full body | Index metadata only; no detail endpoint |
| Academic/HR board-pack sections | Not in `GET /reports/board-pack` payload |
| Toast library | Uses `Alert.alert` per existing admin convention |

---

## Verification

```powershell
cd mobile-app\apps\admin
npm.cmd run typecheck   # PASS
npx.cmd expo start -c   # Manual UI test against production API
```

**Smoke test:** Login → Communication (create announcement, SMS history) → Operations (check-in visitor, approve requisition) → People → Staff 360 (performance/training detail, payslip download) → Reports (board pack, expenses, weekly list).

---

## Deploy note

No new Laravel migrations. Ensure EC2 has latest code with `route:cache` after pull (Sprint 13 APIs already deployed).
