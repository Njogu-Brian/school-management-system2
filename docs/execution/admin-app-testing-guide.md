# Admin App — Testing Guide (Sprints 1–4)

**Purpose:** Verify the Admin mobile app and its Laravel APIs end-to-end before the next sprint.  
**Surfaces covered:** Auth, Dashboard, Settings Hub, Students 360, People / Staff 360.

---

## 1. Preflight (automated)

Run from repo root:

```powershell
# Mobile type safety
cd mobile-app/packages/core; npx tsc --noEmit
cd ../ui; npx tsc --noEmit
cd ../../apps/admin; npx tsc --noEmit

# Laravel routes registered
cd ../../../
php artisan route:list --path=staff
php artisan route:list --path=students
```

**Expected:** All `tsc` commands exit 0. Staff routes include `leave-balances` and `attendance-history`.

### API reachability

Production API (current `mobile-app/.env` default):

```powershell
# Should return 401 (invalid credentials), not connection error
Invoke-WebRequest -Uri "https://erp.royalkingsschools.sc.ke/api/login" `
  -Method POST -ContentType "application/json" `
  -Body '{"email":"probe@test.com","password":"x"}' -UseBasicParsing
```

### API smoke script (authenticated)

```powershell
$env:ERP_EMAIL = "your-admin@school.com"
$env:ERP_PASSWORD = "your-password"
# Optional: $env:ERP_STAFF_ID = "42"
.\scripts\smoke-admin-api.ps1
```

**Pass criteria:** `POST /login` plus all Staff 360 reads return success.

### PHPUnit (sqlite testing DB only)

Local MySQL + `RefreshDatabase` may fail on the full migration graph. For automated API tests use sqlite:

```env
# phpunit.xml or .env.testing
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

```powershell
php artisan test tests/Feature/Api/Staff360ApiTest.php
php artisan test tests/Feature/Api/StudentAssessmentReadTest.php
```

On MySQL-only setups these tests **skip** with an explicit message.

---

## 2. Run the Admin app (manual)

### 2.1 Environment

`mobile-app/.env` (already points at production):

```
EXPO_PUBLIC_API_BASE_URL=https://erp.royalkingsschools.sc.ke/api
```

For a **local Laravel** server instead:

```
EXPO_PUBLIC_API_BASE_URL=http://YOUR_LAN_IP:8000/api
```

Restart Expo after changing env vars (`npx expo start --clear`).

### 2.2 Start dev server

```powershell
cd mobile-app/apps/admin
npx expo start
```

- **Android:** Press `a` or scan QR with Expo Go (`com.schoolerp.admin` dev build if using custom dev client).
- **iOS:** Press `i` or Camera app → Expo Go.
- **Physical device:** Phone and PC must share the same network; use LAN IP for local API.

### 2.3 Test account

Use a real **Admin** or **Secretary** account with:

| Permission | Needed for |
| --- | --- |
| (login) | App access |
| `people.view` or `staff.view` | People workspace |
| `finance.view` | Payroll widget + Employment banking section |
| `academics.view` | Student 360 Academics tab |
| `settings.view` (if wired) | Settings Hub |

---

## 3. Manual QA matrix

### 3.1 Authentication

| # | Step | Expected |
| --- | --- | --- |
| A1 | Open app → Login with email/password | Lands on Dashboard |
| A2 | Wrong password | Error message, no crash |
| A3 | Kill app → reopen | Session persists (or login if expired) |
| A4 | Biometric prompt (if enabled) | Optional unlock flow |

### 3.2 Dashboard

| # | Step | Expected |
| --- | --- | --- |
| D1 | Dashboard loads | KPI widgets render or show skeleton → data |
| D2 | Pull to refresh (if available) | Stats refetch |
| D3 | Pending approvals widget | Count matches web inbox (approx.) |

### 3.3 Settings Hub (Sprint 4 Batch 1)

| # | Step | Expected |
| --- | --- | --- |
| S1 | Settings tab → open | School, Academic, Grading, Roles sections |
| S2 | School section | Read-only school name / contact fields |
| S3 | Academic years / terms | Lists load without error |
| S4 | Roles section | Role names listed (read-only) |

### 3.4 Students workspace

| # | Step | Expected |
| --- | --- | --- |
| ST1 | Students tab → registry | Search + list loads |
| ST2 | Open a student | Student 360 header + tabs |
| ST3 | Overview tab | Attendance %, fee widgets |
| ST4 | Attendance tab | Summary + trend |
| ST5 | Academics tab (if permitted) | Assessment history / report cards |
| ST6 | Fees tab (if `finance.view`) | Statement summary |
| ST7 | Family tab | Parent / guardian info |

### 3.5 People / Staff 360 (Sprint 4 Batches 2–4)

| # | Step | Expected |
| --- | --- | --- |
| P1 | People tab → Staff registry | List loads, search works |
| P2 | Filters | Department, category, status, gender, role chips filter server-side |
| P3 | Tap staff row | Staff 360 opens with header (photo, name, badge) |
| P4 | **Overview** | Widgets: employment, leave remaining, attendance %, optional payroll |
| P5 | **Employment** | Position, contract, identity, emergency; banking only with `finance.view` |
| P6 | **Leave** | Balance cards + leave request history |
| P7 | **Attendance** | Month summary + daily log (clock vs manual label) |
| P8 | Back navigation | Returns to registry |
| P9 | User without `people.view` | Access denied message |

### 3.6 Regression guards (read-only)

| # | Step | Expected |
| --- | --- | --- |
| R1 | Staff 360 — no Edit button | No create/edit flows |
| R2 | Leave tab | No approve/reject actions |
| R3 | Employment | No salary edit fields |

---

## 4. API ↔ UI mapping (Staff 360)

| UI data | Endpoint |
| --- | --- |
| Header / Employment identity | `GET /api/staff/{id}` |
| Leave balances | `GET /api/staff/{id}/leave-balances` |
| Leave history | `GET /api/leave-requests?staff_id=` |
| Attendance summary + log | `GET /api/staff/{id}/attendance-history` |
| Latest payroll (Overview) | `GET /api/payroll-records?staff_id=` |

---

## 5. Known issues / test notes

| Issue | Workaround |
| --- | --- |
| PHPUnit fails on local MySQL | Use sqlite testing DB or run smoke script against staging/production |
| `StudentAssessmentReadTest` calls `config()` before `parent::setUp()` | Fix pending; use `Staff360ApiTest` pattern |
| Admin without linked `staff` row | Staff 360 still works via new `attendance-history` (Batch 4) |
| Empty leave balances | HR may not have configured balances for active year — empty state is valid |
| Payroll widget hidden | Log in with `finance.view` permission |

---

## 6. Sign-off checklist

- [ ] Preflight: `tsc` passes (core, ui, admin)
- [ ] Smoke script: all Staff endpoints PASS
- [ ] Login + Dashboard verified on device
- [ ] Settings Hub read-only sections load
- [ ] Student 360 tabs spot-checked
- [ ] Staff registry search + filters work
- [ ] Staff 360 all four tabs load for at least one staff member
- [ ] RBAC: finance sections hidden for non-finance user
- [ ] No crashes on back navigation or tab switching

---

*Last updated: Sprint 4 Batch 4 testing pass.*
