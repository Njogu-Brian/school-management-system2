# Legacy App Feature Parity — Admin Mobile

**Date:** 2026-06-04  
**Goal:** Restore key features from `mobile-app/src/` (legacy staff/teacher app) in `mobile-app/apps/admin/` before QA testing.

---

## Parity status

| Feature | Legacy app | Admin app (now) | API |
|---------|------------|-----------------|-----|
| Staff clock in/out | `TeacherClockScreen` | `StaffClockScreen` + dashboard quick action | `POST /staff-attendance/clock-in|out` |
| Exam marks entry | `MarksEntryScreen` | `MarksEntryScreen` from exam detail | `POST /exam-marks/batch` |
| Transport — routes | `RoutesListScreen` | `TripsListScreen` / `TripDetailScreen` | `GET /routes` |
| Transport — teacher pickup | `TeacherTransportListScreen` | `TeacherTransportScreen` | `GET/POST /teacher/transport/*` |
| Transport — driver roster | `DriverHomeScreen` | `DriverTripsScreen` / `DriverTripDetailScreen` | `GET /driver/trips` |
| Dark / light / auto theme | Settings toggle | Session & security → Appearance | Client-only |
| Branding (name, logo, colors) | `LoginScreen` + `ThemeContext` | `BrandingProvider` + `AppThemeProvider` | `GET /app-branding` |

---

## Architecture

- **Branding:** `packages/core` `BrandingProvider` fetches `/app-branding`; portal colors merge into `@erp/ui` `ThemeProvider`.
- **Theme persistence:** `themeStorage` (AsyncStorage) — light / dark / auto.
- **Clock:** Requires `expo-location` + configured geofence on Laravel.
- **Marks:** Batch entry per exam/class/subject from Academics → Exam detail.

---

## Still web-only (not in mobile API)

- Vehicle/trip CRUD admin screens (legacy `transport.api.ts` stubs)
- Full transport reassignment modal (teacher temporary reassign — API exists, UI simplified)
- Marks matrix bulk entry screen (API exists: `POST /exam-marks/matrix/batch`)

---

## Test checklist

1. Login shows school name + logo from portal settings
2. Settings → Session → toggle dark/light/auto
3. Dashboard → Staff clock → clock in/out with GPS
4. Academics → Exam → Enter marks → save batch
5. Operations → Teacher transport / Driver trips
6. All screens respect dark mode palette
