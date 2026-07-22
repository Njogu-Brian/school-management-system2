# Users App — backend contract gaps

Tracked for `apps/users`. Shells are wired; these items still limit depth or Play Store polish.

## 1. Student identity on `/user`

- [`User.studentId`](../../packages/core/src/types/user.ts) is typed and Student Home/Homework/Results use it when present.
- Backend should return reliable `student_id` from `GET /user` / `AuthApiController::formatUserForApi`.
- Until then, Student Home shows an explicit “not linked” empty state.

## 2. Driver trip lifecycle — IMPLEMENTED

- Tables: `trip_runs`, `trip_run_locations`
- API: start/stop/boarding/location/vehicle + live track for parents/admin
- Mobile: boarding checklist, active trip GPS ping, parent Track bus, admin Live fleet

**Deploy before QA:** run migration `2026_07_22_140000_create_trip_runs_and_locations_tables` on production.

## 3. Digital diary

- Mobile API: `GET /diaries`, `GET /diaries/students/{id}`, `POST .../entries` (multipart).
- Deploy diary controller/routes to production before Play Store QA.

## 4. Transport change requests

- Parent temporary/permanent requests use `POST /transport/special-assignments` with `activate: false`.
- Permanent = no `end_date`; school must approve.

## 5. Push / deep links

- Segment Expo/FCM topics for `users` vs `admin` binaries.
- Deep-link payload into scheme `royalkingsusers://`.
- Optional: push parent when child boards / trip starts (not yet wired).

## 6. EAS / Play Store

- `apps/users/app.config.ts` still has placeholder `EAS_PROJECT_ID`.
- Create EAS project, then `npm run build:production` from `apps/users`.

## 7. Teacher phase-2 (web parity still deferred)

- Senior teacher supervised classrooms/staff/fees
- Requirements collect action
- Student behaviour logging
- Richer lesson plan editor (mobile now has create draft + submit; full web fields still richer)
