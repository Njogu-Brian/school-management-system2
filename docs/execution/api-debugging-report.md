# API Debugging Report — Settings Hub & Student 360 Academics

**Date:** 2026-06-04  
**Environment probed:** `https://erp.royalkingsschools.sc.ke/api`  
**Mobile app:** Admin Expo app (`mobile-app/apps/admin`)

---

## Executive summary

| Area | Symptom | Root cause | Fix |
|------|---------|------------|-----|
| **Settings Hub** (School, Academic, Grading, Roles) | All four sections show "Could not load …" | **404 — routes not deployed** to production | Deploy backend containing `ApiSettingsHubController` + `routes/api.php` settings group |
| **Student 360 → Academics** | "Could not load academic data" | **404 — Phase 0 assessment routes not deployed** | Deploy backend containing `ApiStudentAssessmentController` + assessment facade |

This is **not** a permissions issue, response-shape mismatch, or four independent mobile bugs. Production returns **HTTP 404** for every new Sprint 4 endpoint while older routes on the same host respond **200**.

**Smallest fix:** Push local commits to `origin/main`, then deploy to EC2. Production is running an older `main` that **does not include** the Settings Hub or assessment-read routes at all.

### Why production 404s (confirmed)

| Check | Result |
|-------|--------|
| `origin/main` contains `GET /api/settings/school` | **No** |
| `origin/main` contains `GET /api/students/{id}/academic-summary` | **No** |
| Local `HEAD` (11 commits ahead of `origin/main`) | **Has both** — from commit `6f506f38` onward |
| Production probe `GET /api/dashboard/stats` | **200** (older routes work) |
| Production probe `GET /api/settings/school` | **404** |

---

## Production probe results (unauthenticated)

| Endpoint | HTTP status | Notes |
|----------|-------------|-------|
| `GET /api/settings/school` | **404** | Settings Hub missing |
| `GET /api/students/1/academic-summary` | **404** | Phase 0 academics missing |
| `GET /api/students/1/assessment-history` | **404** | Phase 0 academics missing |
| `GET /api/students/1` | **200** | Existing student detail works |
| `GET /api/report-cards?student_id=1` | **200** | Report cards work (not the blocker) |

With a valid Bearer token, 404 would persist (route not registered). A 403 would only appear **after** the route exists.

---

## Step 1 — Screen → hook → endpoint trace

### Settings Hub (`SettingsScreen`)

**Gate (client):** `useCan('settings.view')` — if false, shows lock screen (not the error seen in screenshots).

**Gate (server):** `ApiSettingsHubController::assertSettingsAccess()` — Super Admin / Admin / Secretary **or** `settings.view`; else 403.

| Section | Hook | Query key | Endpoint | Params | Expected response |
|---------|------|-----------|----------|--------|-------------------|
| School | `useSchoolSettings` | `['settings','school']` | `GET /settings/school` | — | `{ success, data: SchoolSettingsRecord }` |
| Academic (years) | `useAcademicYearsSettings` | `['settings','academic-years']` | `GET /settings/academic-years` | — | `{ success, data: AcademicYearRecord[] }` |
| Academic (terms) | `useTermsSettings` | `['settings','terms', yearId\|'all']` | `GET /settings/terms` | `academic_year_id?` | `{ success, data: TermRecord[] }` |
| Academic (classes) | `useSettingsClasses` | `['settings','classes']` | `GET /settings/classes` | — | `{ success, data: SettingsClassroomRecord[] }` |
| Academic (streams) | `useSettingsStreams` | `['settings','streams', classId]` | `GET /settings/classes/{id}/streams` | — | `{ success, data: SettingsStreamRecord[] }` |
| Academic (subjects) | `useSettingsSubjects` | `['settings','subjects']` | `GET /settings/subjects` | — | `{ success, data: SettingsSubjectRecord[] }` |
| Grading | `useGradingSettings` | `['settings','grading']` | `GET /settings/grading` | — | `{ success, data: GradingSettingsRecord }` |
| Roles | `useRolesSettings` | `['settings','roles']` | `GET /settings/roles` | — | `{ success, data: RoleSettingsRecord[] }` |

**Failure UI:** Each section component shows "Could not load …" when its `useQuery` enters `isError` (axios rejects on 404/403/500).

### Student 360 Academics (`AcademicsTab`)

**Error condition:** `summaryQuery.isError || historyQuery.isError` → "Could not load academic data". Report-card queries failing alone do **not** trigger this banner.

| Hook | Query key | Endpoint | Params | Expected response |
|------|-----------|----------|--------|-------------------|
| `useStudentAcademicSummary` | `['students','academic-summary', id, scope]` | `GET /students/{id}/academic-summary` | `term_id?`, `academic_year_id?` | `{ success, data: AcademicSummaryRecord }` → `normalizeAcademicSummary` |
| `useStudentAssessmentHistory` | `['students','assessment-history', id, filters]` | `GET /students/{id}/assessment-history` | `page`, `per_page`, `type?`, `subject_id?`, `term_id?`, `academic_year_id?` | `{ success, data: PaginatedResponse<AssessmentHistoryRecord>, meta?: AssessmentHistoryMeta }` |
| `useStudentReportCards` | `['students','report-cards', id]` | `GET /report-cards` | `student_id`, `per_page` | `{ success, data: PaginatedResponse<ReportCardListRecord> }` |
| `useStudentReportCardDetail` | `['students','report-card', rcId]` | `GET /report-cards/{id}` | — | `{ success, data: ReportCardDetailRecord }` |

---

## Step 2 — Settings Hub Laravel audit (local codebase)

| Route | Exists locally? | Permission / middleware | Response shape | Potential issue on prod |
|-------|-----------------|-------------------------|----------------|-------------------------|
| `GET /api/settings/school` | ✓ | `auth:sanctum` + `assertSettingsAccess` | `{ success, data: { school_name, … } }` | **Not deployed → 404** |
| `GET /api/settings/academic-years` | ✓ | same | `{ success, data: AcademicYear[] }` | **Not deployed → 404** |
| `GET /api/settings/terms` | ✓ | same | `{ success, data: Term[] }` | **Not deployed → 404** |
| `GET /api/settings/classes` | ✓ | same | `{ success, data: Classroom[] }` | **Not deployed → 404** |
| `GET /api/settings/classes/{id}/streams` | ✓ | same | `{ success, data: Stream[] }` | **Not deployed → 404** |
| `GET /api/settings/subjects` | ✓ | same | `{ success, data: Subject[] }` | **Not deployed → 404** |
| `GET /api/settings/grading` | ✓ | same | `{ success, data: { schemes, exam_types } }` | **Not deployed → 404** |
| `GET /api/settings/roles` | ✓ | same | `{ success, data: Role[] }` | **Not deployed → 404** |

**Controller:** `app/Http/Controllers/Api/ApiSettingsHubController.php` — all methods call `assertSettingsAccess()` first.

**Mobile normalizers:** Settings hooks consume `res.data` directly (no extra normalizer). Laravel field names match `mobile-app/packages/core/src/types/settings.ts`.

---

## Step 3 — Student 360 Academics Laravel audit (local codebase)

| Endpoint | Status (local) | Permission | Actual shape (controller) | Expected shape (mobile) | Fix required |
|----------|----------------|------------|----------------------------|-------------------------|--------------|
| `GET /students/{id}/academic-summary` | Registered | `auth:sanctum`; teacher scope / guardian link via `assertUserCanAccessStudent` | `{ success, data: facade summary }` matches `AcademicSummaryRecord` | `AcademicSummaryRecord` | **Deploy route** |
| `GET /students/{id}/assessment-history` | Registered | same | `{ success, data: { data, current_page, … }, meta: { student_id, current_term_id } }` | `PaginatedResponse` + optional `meta` | **Deploy route** |
| `GET /report-cards?student_id=` | Registered (prod ✓) | existing auth | Paginated list | `PaginatedResponse<ReportCardListRecord>` | None |
| `GET /report-cards/{id}` | Registered (prod ✓) | existing auth | Detail object | `ReportCardDetailRecord` | None |

**Controller:** `app/Http/Controllers/Api/ApiStudentAssessmentController.php`  
**Service:** `AssessmentReadFacade` (Phase 0 read facade)

No TypeScript normalizer mismatch was found in code review; failures occur before JSON is parsed (404).

---

## Step 4 — Development API logging (implemented)

Added to `mobile-app/packages/core/src/api/client.ts` ( **`__DEV__` only** ):

- **Request:** `METHOD baseURL+path` + serialized params  
- **Response:** `STATUS url` + truncated body preview  
- **Error:** `STATUS url` + response body + message  

Metro console example after opening Settings:

```text
[API] → GET https://erp.royalkingsschools.sc.ke/api/settings/school
[API] ✗ 404 /settings/school { message: "...", body: ... }
```

---

## Step 5 — Admin Diagnostics screen (implemented)

- **Location:** Settings → **API Health (dev)** footer link (`__DEV__` only)  
- **Module:** `mobile-app/apps/admin/src/features/diagnostics/`  
- **Runner:** `runApiDiagnostics()` in `@erp/core`  
- **Probes:** Dashboard, all Settings endpoints, student detail, academic-summary, assessment-history, report-cards, staff detail, leave-balances, attendance-history  

Use after deploy to confirm all probes show ✓ Healthy.

---

## Deploy checklist (do this next)

### 1. Push backend to GitHub

Local branch is **11 commits ahead** of `origin/main` (Sprint 1–4 admin APIs). Push when ready:

```powershell
cd e:\school-management-system2\school-management-system2
git push origin HEAD:main
```

Key commits that fix these failures:

- `6f506f38` — `ApiSettingsHubController`, `ApiStudentAssessmentController`, `AssessmentReadFacade`, settings routes
- Later commits — Staff 360, People workspace (also not on prod yet)

### 2. Deploy on EC2

SSH to the server and run the existing deploy script (see `docs/EC2_DEPLOYMENT.md`):

```bash
cd /var/www/erp
git pull origin main
./scripts/deploy-ec2.sh
```

Or one-liner from your machine:

```powershell
ssh -i erp-key.pem ubuntu@13.245.211.78 'cd /var/www/erp && git pull origin main && ./scripts/deploy-ec2.sh'
```

After deploy, clear route cache if used:

```bash
php artisan route:clear
php artisan config:clear
php artisan route:cache
```

### 3. Verify after deploy

**Smoke script:**

```powershell
$env:ERP_EMAIL = 'your-admin@school.com'
$env:ERP_PASSWORD = 'your-password'
.\scripts\smoke-admin-api.ps1
```

Expected: all `GET /settings/*`, `academic-summary`, and `assessment-history` show **PASS**.

**In-app (dev build):** Settings → **API Health (dev)** → Re-run probes — all ✓ Healthy.

**Manual:** Settings Hub (all 4 tabs) + Student 360 → Academics tab.

---

## Hypothesis check

| Hypothesis | Verdict |
|------------|---------|
| `settings.view` permission missing | **Unlikely** — would be 403, not 404; Admin role bypasses permission |
| Single `ApiSettingsHubController` bug | **Unlikely** — all endpoints 404 on prod (not registered) |
| `academic-summary` exception (500) | **Unlikely on prod** — route absent (404) |
| Response shape mismatch | **Unlikely** — request never reaches controller on prod |
| **Routes not deployed** | **Confirmed** |

---

## Files touched in this debugging sprint

| File | Change |
|------|--------|
| `mobile-app/packages/core/src/api/client.ts` | `__DEV__` request/response/error logging |
| `mobile-app/packages/core/src/api/diagnostics.api.ts` | Health probe runner |
| `mobile-app/apps/admin/src/features/diagnostics/*` | Diagnostics UI |
| `mobile-app/apps/admin/src/features/settings/screens/SettingsScreen.tsx` | Dev-only diagnostics entry |
| `scripts/smoke-admin-api.ps1` | Extended probes (see below) |
| `docs/execution/api-debugging-report.md` | This report |

**No business-logic changes** to hooks, controllers, or normalizers.
