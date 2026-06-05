# Legacy App Feature Parity — Admin Mobile

**Date:** 2026-06-04 (updated)  
**Goal:** Restore key features from `mobile-app/src/` (legacy staff/teacher app) in `mobile-app/apps/admin/` before QA testing.

---

## Parity status

| Feature | Legacy app | Admin app (now) | API |
|---------|------------|-----------------|-----|
| Staff clock in/out | `TeacherClockScreen` | `StaffClockScreen` + dashboard quick action | `POST /staff-attendance/clock-in\|out` |
| Staff team clock history | `StaffClockTeamScreen` | `StaffClockTeamScreen` (from Staff clock) | `GET /staff-attendance/clock-roster`, `staff/history` |
| Exam marks entry | `MarksEntryScreen` | `MarksEntryScreen` from exam detail | `POST /exam-marks/batch` |
| Marks matrix bulk entry | `MarksMatrixEntryScreen` | `MarksMatrixSetupScreen` → `MarksMatrixEntryScreen` | `POST /exam-marks/matrix/batch` |
| Transport — routes | `RoutesListScreen` | `TripsListScreen` / `TripDetailScreen` + CRUD | `GET/POST/PUT/DELETE /routes` |
| Transport — vehicles | Placeholder stub | `VehiclesListScreen` / `VehicleFormScreen` | `GET/POST/PUT/DELETE /vehicles` |
| Transport — teacher pickup | `TeacherTransportListScreen` | `TeacherTransportScreen` (full modals) | `GET/POST /teacher/transport/*` |
| Transport — teacher reassignment | Full modal | `TeacherTransportScreen` reassignment modal | `POST /teacher/transport/reassign` |
| Transport — driver roster | `DriverHomeScreen` | `DriverTripsScreen` / `DriverTripDetailScreen` | `GET /driver/trips` |
| Dark / light / auto theme | Settings toggle | Session & security → Appearance | Client-only |
| Branding (name, logo, colors) | `LoginScreen` + `ThemeContext` | `BrandingProvider` + `AppThemeProvider` | `GET /app-branding` |

---

## Architecture

- **Branding:** `packages/core` `BrandingProvider` fetches `/app-branding`; portal colors merge into `@erp/ui` `ThemeProvider`.
- **Theme persistence:** `themeStorage` (AsyncStorage) — light / dark / auto.
- **Clock:** Requires `expo-location` + configured geofence on Laravel.
- **Marks:** Single-subject from Academics → Exam detail; bulk matrix from Marks Matrix → Bulk entry.
- **Transport admin:** Vehicle and trip CRUD via new Sanctum APIs (`ApiVehicleController`, extended `ApiRouteController`).

---

## Test checklist

1. Login shows school name + logo from portal settings
2. Settings → Session → toggle dark/light/auto; verify palette on Dashboard, Operations, Academics
3. Dashboard → Staff clock → clock in/out with GPS
4. Staff clock → Team clock history → pick staff → view 90-day records
5. Academics → Marks Matrix → Bulk marks entry → select class/exam/stream → save batch
6. Academics → Exam → Enter marks → save single-subject batch
7. Operations → Teacher transport → parent pickup modal + vehicle/trip reassignment
8. Operations → Routes → Add trip / Vehicles → Add vehicle → edit/delete
9. Operations → Driver trips → open roster

---

## Remaining web-only (optional)

- Drop-point CRUD on mobile (stops managed on web portal)
- Student route assignment bulk import (web transport module)
- Geofence admin UI on mobile (API exists; configure via web or future settings screen)
