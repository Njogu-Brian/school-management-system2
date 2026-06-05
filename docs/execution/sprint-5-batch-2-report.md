# Sprint 5 Batch 2 — Online Admissions Mutations & Mobile Enrollment

**Date:** 2026-06-04  
**Scope:** Complete online admissions integration on Admin mobile — workflow mutations, enrollment form, document download, post-enroll navigation.

## Backend

### `OnlineAdmissionWorkflowService`
- Extracted shared workflow from web `OnlineAdmissionController`:
  - `updateStatus`, `addToWaitlist`, `reject`, `enroll`
- Enroll path mirrors web: parent linking, admission number, transport fee, welcome comms, fee posting.

### API routes (`/api/admissions/*`)
| Method | Path | Purpose |
|--------|------|---------|
| `PUT` | `/{id}/status` | Update status (pending, under_review, waitlisted, rejected) |
| `POST` | `/{id}/waitlist` | Add to waitlist with optional notes |
| `POST` | `/{id}/reject` | Reject application |
| `POST` | `/{id}/enroll` | Enroll → creates `Student`, returns student + updated application |
| `GET` | `/{id}/files/{field}` | Authenticated download (public passport / private docs) |

### Detail payload extensions
- `enrollment.drop_off_points`, `enrollment.trips`
- `documents[].download_path` for private file fetch

## Mobile (`@erp/core`, `@erp/ui`, `apps/admin`)

### API & hooks
- `admissionsApi`: `updateStatus`, `waitlist`, `reject`, `enroll`
- `useAdmissionActions`: mutations with admissions + students cache invalidation

### UI
- **Overview tab:** Quick actions — Under Review, Waitlist, Reject (with confirm)
- **Enrollment tab:** Class, stream, category, term, transport fields, residential area, **Enroll student** button
- **Documents tab:** Private document download via `expo-file-system` + `expo-sharing` (Bearer auth)
- **Post-enroll:** Alert with “View student” → navigates to Student 360

### Dependencies added (`apps/admin`)
- `expo-file-system`, `expo-sharing`

## Tests
- `AdmissionsApiTest`: status/waitlist/reject + enroll happy path

## Deploy note
After push, on EC2 run `git pull`, `composer install --no-dev`, `php artisan route:clear && php artisan route:cache`, reload php-fpm so mutation routes are live.

## Verification
```bash
cd mobile-app/apps/admin && npm run typecheck
DB_CONNECTION=sqlite php artisan test tests/Feature/Api/AdmissionsApiTest.php
```
