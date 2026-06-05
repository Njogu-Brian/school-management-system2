# Sprint 5 — Batch 1 Report: Admissions Workspace Foundation

**Status:** Complete  
**Scope:** Replace Admissions placeholder with dashboard KPIs, applications list, and Admissions 360 detail (6 tabs). New read-only Laravel APIs over `online_admissions`. No enroll/reject mutations in this batch.  
**Source of truth:** `docs/admissions/01-admissions-audit.md`

---

## 1. Executive summary

The audit confirmed **no REST API** for the online admissions queue (web-only `OnlineAdmissionController`). Batch 1 adds three read endpoints and wires the Admin mobile Admissions drawer to live data.

| Deliverable | Status |
|-------------|--------|
| Admissions Dashboard (5 KPI cards) | ✓ |
| Applications list (search, status filters, infinite scroll, pull-to-refresh) | ✓ |
| Admissions 360 (Overview, Student, Parents, Documents, Timeline, Enrollment) | ✓ |
| TanStack Query hooks | ✓ |
| RBAC (`admissions.view` + role fallback) | ✓ |
| PHPUnit feature test | ✓ |
| Typecheck (`apps/admin`) | ✓ |

**Out of scope (by design):** enroll / reject / waitlist mutations, interviews, application fees, Finance / Communication / Reports / Operations.

---

## 2. Backend APIs added

| Method | Route | Controller | Purpose |
|--------|-------|------------|---------|
| GET | `/api/admissions/stats` | `ApiAdmissionsController@stats` | KPI counts by `application_status` |
| GET | `/api/admissions` | `ApiAdmissionsController@index` | Paginated list + `search`, `status`, `waitlist_only` |
| GET | `/api/admissions/{id}` | `ApiAdmissionsController@show` | Full detail + documents + timeline + enrollment context |

**Access:** `auth:sanctum` + Super Admin / Admin / Secretary **or** `admissions.view`.

**Reused domain logic from:** `OnlineAdmissionController::index` / `show` (filters, relations, enrollment term options).

**Model change:** `OnlineAdmission::preferredClassroom()` relationship added.

### 2.1 List query parameters

| Param | Notes |
|-------|-------|
| `page`, `per_page` | Default 25, max 100 |
| `status` | `pending`, `under_review`, `waitlisted`, `enrolled`, `rejected` |
| `search` | Name or parent phone/email |
| `waitlist_only` | Orders by `waitlist_position` |

### 2.2 Detail payload highlights

- **Student / parent** scalar fields from `online_admissions`
- **documents** — passport (public `view_url`), private docs flagged `is_private`
- **timeline** — synthesized from `application_date`, `review_date`, status (no separate audit table)
- **enrollment** — placement, transport, term options, categories, classrooms (read-only context for Enrollment tab)

---

## 3. Mobile architecture

### 3.1 `@erp/core`

| Module | Files |
|--------|-------|
| Types | `types/admissions.ts` |
| API | `api/admissions.api.ts` |
| Normalizers | `admissions/normalize.ts` |
| Query keys | `queryKeys.admissions.*` |
| Hooks | `useAdmissionsStats`, `useInfiniteApplicationList`, `useApplicationDetail` |

### 3.2 `@erp/ui`

| Package | Components |
|---------|------------|
| `admissions/` | `ApplicationSearchBar`, `ApplicationFilters`, `ApplicationListItem`, `ApplicationStatusBadge` |
| `admissions360/` | `Admissions360Layout`, `Admissions360Header`, `ApplicationFieldSection`, `ApplicationTimeline`, `ApplicationDocumentList` |

### 3.3 `apps/admin`

| Screen | Description |
|--------|-------------|
| `AdmissionsWorkspaceScreen` | Dashboard KPI grid + `FlatList` applications registry |
| `ApplicationDetailScreen` | Admissions 360 with 6 tabs |
| `AdmissionsStackNavigator` | Workspace → Detail stack in drawer |

**Patterns mirrored:** Student Registry (`useInfiniteApplicationList`, `ScreenContainer scroll={false}`), Student/Staff 360 tab layout.

---

## 4. RBAC

| Gate | Behavior |
|------|----------|
| Client | `useCan('admissions.view')` on workspace + detail |
| Nav | `AREA_VIEW_PERMISSIONS.admissions` (unchanged) |
| Server | Role middleware equivalent: Super Admin / Admin / Secretary OR `admissions.view` |
| Fallback presets | Secretary, Receptionist, Leadership include `admissions.view` |

---

## 5. Verification

```powershell
cd mobile-app/apps/admin
npx.cmd tsc --noEmit
```

```powershell
$env:ERP_EMAIL = 'admin@school.com'
$env:ERP_PASSWORD = '***'
.\scripts\smoke-admin-api.ps1
# Expect PASS: GET /admissions/stats, GET /admissions
```

**Manual (phone):** Drawer → Admissions → KPI cards filter list → tap application → all 6 tabs load.

**Deploy note:** Push backend + `php artisan route:cache` on EC2 (same pattern as Settings Hub deploy).

---

## 6. Known gaps (Batch 2+)

| Gap | Audit ref | Planned |
|-----|-----------|---------|
| Enroll / reject / waitlist API | §3.7 #6–8 | Batch 2 mutations |
| `student_id` FK after enroll | §8.1 | Backend schema |
| Private document download in-app | §6 | Signed URL or authenticated stream |
| Application fee / interviews | §1.9–1.11 | Future modules |

---

## 7. File manifest (key paths)

**Backend**

- `app/Http/Controllers/Api/ApiAdmissionsController.php`
- `app/Models/OnlineAdmission.php` (preferredClassroom)
- `routes/api.php`
- `tests/Feature/Api/AdmissionsApiTest.php`

**Mobile**

- `mobile-app/packages/core/src/types/admissions.ts`
- `mobile-app/packages/core/src/api/admissions.api.ts`
- `mobile-app/packages/core/src/query/hooks/useAdmissions.ts`
- `mobile-app/packages/ui/src/admissions/*`
- `mobile-app/packages/ui/src/admissions360/*`
- `mobile-app/apps/admin/src/features/admissions/**`
- `mobile-app/apps/admin/src/navigation/AdmissionsStackNavigator.tsx`
