# Royal Kings Users App

Second Expo binary for **teachers, parents, students, drivers**, and other non-admin staff.

- Package: `@erp/users`
- Android / iOS id: `com.royalkingsschools.users`
- Scheme: `royalkingsusers://`

## Run

From monorepo root (`mobile-app/`):

```bash
npm start --workspace=@erp/users
# or
cd apps/users && npx expo start
```

## Architecture

- Shared brain: `@erp/core` (auth, API, query, roles)
- Design system: `@erp/ui`
- Role shells: Teacher · Parent · Student · Driver via `RoleBasedNavigator`
- App gate: `canAccessApp(user, 'users')`
- Auth: password / OTP / Google + **PIN unlock** + biometrics + remembered username

## Role coverage (smoke when you return)

| Role | Ready to test |
|------|----------------|
| **Parent** | Children hub → results, attendance, homework, fees/statement, M-Pesa prompt, diary chat (+ attachments), announcements, notifications, concerns, temp/permanent transport change |
| **Teacher** | Attendance, marks (sheet + matrix), lesson plans + senior review, requirements, timetable (assignments-backed), diary, transport pickup, clock-in (geo + biometric confirm), leave, payslips, announcements |
| **Student** | Homework + results when `student_id` on `/user`; announcements; settings/PIN |
| **Driver** | Trip list/detail; clock, profile, notifications, settings |

## Play Store build (after QA)

1. Deploy diary API routes to production ERP (`/api/diaries…`).
2. Create EAS project and set `EAS_PROJECT_ID` in `app.config.ts` (placeholder today).
3. From `apps/users`: `npm run build:preview` (APK) then `npm run build:production` (AAB).
4. Store assets: `assets/play-store/`.

See [BACKEND_GAPS.md](./BACKEND_GAPS.md) and [CUTOVER.md](./CUTOVER.md).
