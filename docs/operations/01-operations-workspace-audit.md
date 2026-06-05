# 01 — Operations Workspace Audit (Laravel ERP → Admin App)

**Status:** Complete (read-only discovery)  
**Sprint:** Operations Workspace Discovery  
**Scope:** Campus operations oversight — transport fleet, inventory & requisitions, fixed assets (gap), clinic/health, visitor management (gap), security/audit, facilities condition reporting. **Not** driver GPS capture (Driver App), **not** teacher pickup entry (Staff App), **not** library/POS/hostel (adjacent modules — referenced where they touch Operations IA).  
**No application code** was written for this exercise.

**Primary sources:** [`docs/system-audit/02-module-inventory.md`](../system-audit/02-module-inventory.md) (§D Operations), [`docs/system-audit/05-business-processes.md`](../system-audit/05-business-processes.md) (§10, §14–16), [`docs/system-audit/10-future-state.md`](../system-audit/10-future-state.md) (§11 Transport, Clinic, Visitors, Assets), [`docs/prd/02-MASTER-PRODUCT-BACKLOG.md`](../prd/02-MASTER-PRODUCT-BACKLOG.md) (R6/E21–E25, E18), [`docs/admin-app/02-admin-information-architecture.md`](../admin-app/02-admin-information-architecture.md) (§Operations, §J7–J8), [`docs/admin-app/03-admin-ui-specifications.md`](../admin-app/03-admin-ui-specifications.md), [`docs/admin-app/01-admin-discovery.md`](../admin-app/01-admin-discovery.md) (§17–20), `routes/api.php`, `routes/web.php`, API/web controllers under `app/Http/Controllers/`, `TransportAssignmentService`, legacy `mobile-app/src/api/transport.api.ts`, `apps/admin` Operations placeholder.

---

## Executive summary

The ERP has **mature web modules for Transport and Inventory** (assignments, trips, requisitions, student requirements) but **thin Sanctum REST coverage** oriented to **Driver** and **Teacher** field workflows — not to an **Admin oversight workspace**. **Clinic, Visitors, Assets, and Security incidents are largely missing** as first-class modules; health data lives under **Student records**; audit logs are **web-only Super Admin** screens.

| Workspace area | Backend readiness | Admin App today |
|----------------|-------------------|-----------------|
| **Dashboard** | Weak — no `/operations/summary` or `/transport/summary`; partial compose from `/routes`, `/library/books` | Placeholder tab (`OperationsScreen`) |
| **Transport** | Partial read — `GET /routes`, `GET /routes/{id}`, `GET /teacher/transport/vehicles`; assignments/exceptions **web-only** | Legacy monolith Driver/Teacher screens; `@erp/admin` empty |
| **Inventory** | Partial — `GET /teacher/requirements/*` (student collection); stock/requisitions **web-only** | Not started |
| **Assets** | ❌ Missing — no fixed-asset register (E18) | Not started |
| **Clinic** | ⚠️ Partial — `student_medical_records` + student health fields on `GET /students/{id}`; no visit workflow API | Student 360 Health tab planned `(new)` |
| **Visitor Management** | ❌ Missing — no tables/controllers (E25) | Not started |
| **Security** | ⚠️ Partial — `activity_logs` + `system_logs` web UI; no incident module; demo Security role | Not started |
| **Facilities** *(audit §7, not in workspace tree)* | ⚠️ Partial — `operations_facilities` weekly report (web); teacher submit only | Not started |

**Recommendation:** Ship Operations Workspace **read-only MVP** for **Transport trip registry** and **student-scoped requirements/health drill-down** by porting existing APIs into `@erp/core` + `@erp/admin` (mirror Finance/Academics). **Phase 2** adds thin REST wrappers for inventory items, requisitions, medical records, activity logs, and transport assignment registries already implemented on web controllers. **Phase 3** (R6 backlog) builds Visitors, Clinic visits, Assets, and Security incidents as new domain modules.

**Admin App principle (IA):** *Configure, approve, oversee, report* — daily pickup marking, trip boarding, and requirement collection at the classroom door stay in **Staff App**; fleet editing and store issuance stay on **web** until APIs exist.

**IA vs sprint tree:** Full IA lists Procurement, Library under Operations. This sprint tree follows the product brief:

```
Operations
├── Dashboard
├── Transport
├── Inventory
├── Assets
├── Clinic
├── Visitor Management
└── Security
```

Library and Procurement are **out of this workspace** but noted as adjacent; Facilities is audited below and maps to **Reports → Operations** until a dedicated nav item is added.

---

# 1. Cross-cutting Operations API inventory

All routes below are under `auth:sanctum` unless noted. Response envelope: `{ success, data, message? }`; paginated lists use `data.data`, `current_page`, `last_page`, `total`.

| Endpoint | Controller | Methods | Primary actor | Mobile-ready | Admin oversight fit |
|----------|------------|---------|---------------|--------------|---------------------|
| `/routes` | `ApiRouteController::index` | GET | Any authenticated | ✅ Yes | Trip/route registry (backed by `trips` table) |
| `/routes/{id}` | `ApiRouteController::show` | GET | Any authenticated | ✅ Yes | Trip detail + stops |
| `/routes/{id}/fee-clearance-roster` | `ApiFeeClearanceController::tripRoster` | GET | Finance/transport gate | ✅ Yes | Fee clearance roster per trip |
| `/driver/trips` | `ApiDriverTransportController::index` | GET | Driver (own staff) | ✅ Yes | **Not** admin — driver-scoped |
| `/driver/trips/{trip}` | `ApiDriverTransportController::show` | GET | Driver (own staff) | ✅ Yes | Roster for date — driver-scoped |
| `/teacher/transport/students` | `ApiTeacherTransportController::students` | GET | Teacher/Admin | ✅ Yes | Class transport roster + pickups |
| `/teacher/transport/vehicles` | `ApiTeacherTransportController::vehicles` | GET | Teacher/Admin | ✅ Yes | Vehicle + trip picklists |
| `/teacher/transport/pickups` | `ApiTeacherTransportController::markCollectedByParent` | POST | Teacher | ✅ Yes | **Out of Admin MVP** (Staff capture) |
| `/teacher/transport/pickups/{id}` | `ApiTeacherTransportController::cancelPickup` | DELETE | Teacher | ✅ Yes | **Out of Admin MVP** |
| `/teacher/transport/reassign` | `ApiTeacherTransportController::temporaryReassignment` | POST | Teacher | ✅ Yes | **Out of Admin MVP** |
| `/teacher/requirements/students` | `ApiTeacherRequirementsController::students` | GET | Teacher/Admin | ✅ Yes | Student picker for requirements |
| `/teacher/requirements/students/{id}/templates` | `ApiTeacherRequirementsController::templatesForStudent` | GET | Teacher/Admin | ✅ Yes | Per-student requirement status |
| `/teacher/requirements/collect` | `ApiTeacherRequirementsController::collect` | POST | Teacher/Admin | ✅ Yes | **Out of Admin MVP** (capture) |
| `/library/books` | `ApiLibraryController::index` | GET | Any authenticated | ✅ Yes | Adjacent — catalog read only |
| `/students` | `ApiStudentController::index` | GET | Admin/Teacher | ✅ Yes | `trip_id`, `drop_off_point_id`, health flags |
| `/students/{id}` | `ApiStudentController::show` | GET | Admin/Teacher | ✅ Yes | Transport assignment IDs + allergies/immunization |
| `/dashboard/stats` | `ApiDashboardController::stats` | GET | Role-shaped | ⚠️ Partial | No operations tiles today |

**Legacy monolith gap:** `mobile-app/src/api/transport.api.ts` calls `/vehicles`, `/trips`, `/student-route-assignments`, `/transport/summary` — **these routes do not exist** in `routes/api.php` (stale client). Only `/routes` (trips) and `/driver/trips` are implemented.

**Services (shared):** `TransportAssignmentService` (assignment resolution), `ExpoPushService` (reassign notifications), `PhoneNumberService` (student formatting).

---

# 2. Transport audit

## 2.1 Purpose & business process

Per [`05-business-processes.md`](../system-audit/05-business-processes.md) §14: Transport admin assigns students to trips/drop-points; drivers operate daily rosters; teachers verify parent pickups; special assignments and driver-change requests handle exceptions. Outputs: assignments, trip attendance, daily lists. **Pain points:** no live GPS/ETA, basic pickup verification, legacy `routes` table removed (trips are canonical).

## 2.2 Existing APIs

| Endpoint | Controller | Notes |
|----------|------------|-------|
| `GET /routes` | `ApiRouteController` | Paginated trips as “routes”; search by trip name, vehicle, driver |
| `GET /routes/{id}` | `ApiRouteController` | Trip + `drop_points` from `trip_stops` |
| `GET /routes/{id}/fee-clearance-roster` | `ApiFeeClearanceController` | Finance-adjacent clearance list |
| `GET /driver/trips` | `ApiDriverTransportController` | Driver-only; date range ≤ 31 days |
| `GET /driver/trips/{trip}` | `ApiDriverTransportController` | Students on trip for `date` param |
| `GET /teacher/transport/students` | `ApiTeacherTransportController` | Morning/evening legs via `TransportAssignmentService` |
| `GET /teacher/transport/vehicles` | `ApiTeacherTransportController` | All vehicles + all trips (admin allowed) |
| `POST /teacher/transport/pickups` | `ApiTeacherTransportController` | Creates `student_daily_pickups` + own-means special assignment |
| `POST /teacher/transport/reassign` | `ApiTeacherTransportController` | Temporary `transport_special_assignments` |

## 2.3 Existing controllers (web)

| Controller | Responsibility |
|------------|----------------|
| `TransportController` | Transport dashboard (counts, alerts) |
| `VehicleController` | Vehicle CRUD |
| `TripController` | Trip CRUD |
| `DropOffPointController` | Drop-off points + import |
| `StudentAssignmentController` | Term assignments, bulk assign |
| `Transport\TripAttendanceController` | Per-trip daily attendance |
| `Transport\DriverChangeRequestController` | Driver swap approvals |
| `Transport\TransportSpecialAssignmentController` | Exception assignments |
| `Transport\TransportImportController` | Assignment CSV import |
| `Transport\DailyTransportListController` | Printable/excel daily lists |
| `Driver\DriverController` | Driver web home + transport sheet |
| `Teacher\TransportController` | Teacher web transport UI |
| `Finance\TransportFeeController` | Transport fee catalog (Finance domain) |

**Middleware:** `role:Super Admin|Admin|Secretary|Driver|Senior Teacher` on `transport.*` web prefix.

## 2.4 Existing database tables

| Table | Purpose |
|-------|---------|
| `vehicles` | Fleet registry (number, capacity, driver link, documents) |
| `trips` | Trip templates (name, direction, `day_of_week` JSON, vehicle, driver) |
| `trip_stops` | Ordered stops → `drop_off_points` |
| `drop_off_points` | Named pickup/drop locations |
| `student_assignments` | Term-level morning/evening trip + drop-point per student |
| `transport_special_assignments` | Temporary overrides (`own_means`, `vehicle`, `trip`); status workflow |
| `driver_change_requests` | Pending → approved/rejected driver swaps |
| `trip_attendances` | Daily boarding records per trip |
| `student_daily_pickups` | Parent pickup handover records |
| `route_vehicle` | Legacy pivot (module inventory notes legacy tables) |

**Student denormalized fields:** `students.trip_id`, `students.drop_off_point_id`, `students.drop_off_point_other` (enrollment default).

## 2.5 Existing permissions

| Permission (seeders) | Where defined | Notes |
|---------------------|---------------|-------|
| `transport.view`, `transport.manage` | `Comprehensive2025Seeder` | Modern coarse pair |
| `transport.view/create/edit/delete`, `manage transport` | `RolesAndPermissionsSeeder` | Legacy CRUD set |
| `transport.index` | `RolesAndPermissionsSeeder` | Driver dashboard gate |
| `transport.vehicles`, `transport.routes`, `transport.trips` | `PermissionSeeder` | Granular (underused) |
| `operations.view` | `@erp/core` `AdminPermission` only | **Not seeded in Laravel** — client-side gate |

**Roles:** Driver role seeded; **Transport Manager role not seeded** (backlog E4.2.1). Web access uses role strings (`Admin`, `Secretary`, `Driver`, `Senior Teacher`), not `permission:` middleware on transport routes.

## 2.6 Mobile readiness

| Capability | Ready | Gap |
|------------|-------|-----|
| Trip list as routes | ✅ `GET /routes` | `students_count` always `null`; no assignment aggregate |
| Trip detail + stops | ✅ | No live status / GPS |
| Driver daily roster | ✅ Driver-scoped | Admin cannot list all drivers' trips via this API |
| Teacher class roster | ✅ | Admin can call; teacher filter optional |
| Vehicle/trip picklists | ✅ `/teacher/transport/vehicles` | Not a full vehicle registry (no documents/expiry) |
| Assignment CRUD | ❌ Web only | No `/student-assignments` API |
| Special assignments / driver changes | ❌ Web only | Approval queues not exposed |
| Trip attendance history | ❌ Web only | — |
| Transport summary KPIs | ❌ | UI spec `(new)` `GET /transport/summary` |

## 2.7 Missing APIs

| Priority | Endpoint / capability | Purpose |
|----------|----------------------|---------|
| **P0** | `GET /transport/summary` | Dashboard KPIs (vehicles, trips, unassigned students, active specials) |
| **P0** | `GET /vehicles` + `GET /vehicles/{id}` | Fleet registry read |
| **P0** | `GET /trips` (alias or extend `/routes`) | Admin trip list with filters (`driver_id`, `day_of_week`, `has_driver`) |
| **P1** | `GET /student-assignments` | School-wide assignment registry |
| **P1** | `GET /transport/special-assignments?status=` | Exception queue |
| **P1** | `GET /transport/driver-change-requests?status=` | Approval queue |
| **P1** | `GET /trips/{id}/attendance?date=` | Read-only attendance history |
| **P2** | `PUT /transport/special-assignments/{id}/approve` | Moderation (wrap web) |
| **P2** | Live tracking / ETA (R6/E21) | Parent/Driver ecosystem |

---

# 3. Inventory audit

## 3.1 Purpose & business process

Per §10: Staff raise **requisitions** → approver → fulfiller issues stock (`InventoryTransaction` out). Parallel track: **student requirements** (templates per class/term, collection against templates). **Pain points:** no PO/vendor procurement, no stock valuation reports, no Store Keeper role.

## 3.2 Existing APIs

| Endpoint | Controller | Notes |
|----------|------------|-------|
| `GET /teacher/requirements/students` | `ApiTeacherRequirementsController` | Paginated students + `is_new_joiner`, `can_teacher_receive` |
| `GET /teacher/requirements/students/{id}/templates` | `ApiTeacherRequirementsController` | Templates + collected quantities for current term |
| `POST /teacher/requirements/collect` | `ApiTeacherRequirementsController` | Writes `student_requirements` + `item_receipts` |

**No API** for `inventory_items`, `inventory_transactions`, or `requisitions`.

## 3.3 Existing controllers (web)

| Controller | Responsibility |
|------------|----------------|
| `Inventory\InventoryItemController` | Stock CRUD, adjust stock, low-stock filter |
| `Inventory\RequirementTypeController` | Requirement type catalog |
| `Inventory\RequirementTemplateController` | Templates per class/term |
| `Inventory\RequirementTemplateAssignmentController` | Template ↔ class links |
| `Inventory\StudentRequirementController` | Collection UI (admin office for new joiners) |
| `Inventory\RequisitionController` | Requisition workflow (pending → approved → fulfilled/rejected) |
| `Inventory\AcademicYearTermsController` | Helper for template term picker |

**Middleware:** `role:Super Admin|Admin|Secretary|Teacher|teacher|Senior Teacher` on `inventory.*`.

## 3.4 Existing database tables

| Table | Purpose |
|-------|---------|
| `inventory_items` | SKU-like stock (quantity, min level, unit cost, location) |
| `inventory_transactions` | Stock in/out/adjust audit |
| `inventory_types` | Type taxonomy |
| `requirement_types` | Named requirement kinds (e.g. uniform item) |
| `requirement_templates` | Per-class/term quantities, `student_type` (new/existing/both) |
| `requirement_template_classroom` (pivot) | Multi-class templates |
| `student_requirements` | Per-student collection progress |
| `item_receipts` | Line-level receipt history |
| `requisitions` | Internal stock requests |
| `requisition_items` | Line items on requisitions |

**Distinction from Assets:** `inventory_items` tracks **consumable/issuable stock**, not depreciable fixed assets (see §5).

## 3.5 Existing permissions

| Permission | Seeder | Notes |
|------------|--------|-------|
| `inventory.view`, `inventory.manage` | `Comprehensive2025Seeder` | Only modern inventory permissions |
| — | — | No `requisitions.approve` granular permission; gated by role middleware |

## 3.6 Mobile readiness

| Capability | Ready | Gap |
|------------|-------|-----|
| Student requirement status | ✅ Per-student API | No school-wide “pending collections” index |
| Requirement collection write | ✅ | Admin MVP read-only |
| Stock catalog / low stock | ❌ | Web only |
| Requisition queue | ❌ | Web only |
| Template management | ❌ | Web only |

## 3.7 Missing APIs

| Priority | Endpoint | Purpose |
|----------|----------|---------|
| **P0** | `GET /inventory/items?low_stock=1` | Store registry + alerts |
| **P0** | `GET /inventory/items/{id}` | Item detail + recent transactions |
| **P0** | `GET /requisitions?status=` | Approval/fulfillment queue |
| **P0** | `GET /requisitions/{id}` | Detail |
| **P1** | `GET /inventory/summary` | Dashboard KPIs (low stock count, open requisitions) |
| **P1** | `GET /student-requirements?status=pending` | School-wide collection backlog |
| **P2** | `POST /requisitions/{id}/approve` etc. | Wrap web workflow |
| **P2** | Procurement/PO (E16/E23) | Future |

---

# 4. Assets audit

## 4.1 Purpose & business process

Backlog **E18 — Assets** (R5/R6): fixed-asset register with category, location, custodian, value, QR tag, transfers, disposal, depreciation. **Today:** not implemented. [`02-module-inventory.md`](../system-audit/02-module-inventory.md) explicitly states **Inventory ≠ fixed assets**.

## 4.2 Existing APIs

**None.**

## 4.3 Existing controllers (web)

**None** dedicated to fixed assets. Closest overlap:

- `Inventory\InventoryItemController` — consumable stock, not asset lifecycle
- `Finance` modules — no asset register (accounting/GL also missing)

## 4.4 Existing database tables

**None** for fixed assets. No `assets`, `asset_categories`, `asset_transfers`, or `depreciation_schedules` tables in migrations.

## 4.5 Existing permissions

**None.** Future: `assets.view`, `assets.manage` per E18.

## 4.6 Mobile readiness

❌ **Not ready** — greenfield module.

## 4.7 Missing APIs (entire module)

| Priority | Capability | Backlog |
|----------|------------|---------|
| **P2** | `GET /assets` registry | E18.1.1 |
| **P2** | Transfers, disposal, depreciation jobs | E18 |
| **P3** | QR tag scan + audit trail | E18 |

**Workspace implication:** **Assets** section ships as **guided empty state** linking to backlog until E18 schema + APIs exist. Do not repurpose `inventory_items` as a substitute — different lifecycle and accounting treatment.

---

# 5. Clinic audit

## 5.1 Purpose & business process

IA journey **J8:** Nurse logs clinic visit → symptoms/treatment/medication → parent notified → flags on Student 360 Health tab. **Today:** per-student **medical records** CRUD under Students; no standalone clinic module, no visit entity, no Nurse role ([`01-admin-discovery.md`](../admin-app/01-admin-discovery.md) §18).

## 5.2 Existing APIs

| Endpoint | Data exposed | Notes |
|----------|--------------|-------|
| `GET /students/{id}` | `has_allergies`, `allergies_notes`, `is_fully_immunized`, `emergency_contact_*`, `preferred_hospital`, `blood_group` | Profile-level health flags only |
| — | No `student_medical_records` | Records are web-only |

**UI spec (new):** `GET /students/{id}/clinic` — **not implemented**.

## 5.3 Existing controllers (web)

| Controller | Responsibility |
|------------|----------------|
| `Students\MedicalRecordController` | CRUD nested under `students/{student}/medical-records` |

**Routes:** `students.medical-records.*` (index, create, store, show, edit, update, destroy).

**Middleware:** Inherited from student records group (Admin/Secretary/Teacher access patterns).

## 5.4 Existing database tables

| Table / columns | Purpose |
|-----------------|---------|
| `student_medical_records` | Typed records: vaccination, checkup, medication, incident, certificate, other |
| `students` | Allergies, immunization flag, emergency contacts, preferred hospital |
| `parents` | `emergency_medical_contact_*` (migration 2025_12_25) |
| `online_admissions` | Medical fields on intake |

**No** `clinic_visits`, `medication_schedules`, or `immunization_registry` tables.

## 5.5 Existing permissions

No `clinic.view` or `medical_records.*` in seeders. Access follows **student record** role gates. **Nurse role** not seeded (E4.2.1, E24).

## 5.6 Mobile readiness

| Capability | Ready | Gap |
|------------|-------|-----|
| Student health profile flags | ✅ Via `GET /students/{id}` | Not a clinic visit log |
| Medical record history | ❌ | Web only, student-nested |
| Log visit / notify parent | ❌ | No workflow |
| School-wide clinic dashboard | ❌ | No aggregate API |

## 5.7 Missing APIs

| Priority | Endpoint | Purpose |
|----------|----------|---------|
| **P0** | `GET /students/{id}/medical-records` | Student 360 Health tab (read) |
| **P1** | `GET /clinic/visits?date=` | School visit log (new table or type filter on records) |
| **P1** | `POST /clinic/visits` | Nurse visit capture (E24.1.1) |
| **P1** | `GET /clinic/summary` | Dashboard KPIs |
| **P2** | Medication schedules, immunization due | E24 |

---

# 6. Visitor management audit

## 6.1 Purpose & business process

Per §16: **Not implemented.** Backlog **E25** — check-in/out, host notification, QR badge, blacklist, gate pass, incident reporting. Receptionist role should land in Operations(Visitors) per IA.

## 6.2 Existing APIs

**None.**

## 6.3 Existing controllers (web)

**None.**

## 6.4 Existing database tables

**None.** No `visitors`, `visitor_logs`, `gate_passes`, or `visitor_blacklist` migrations.

## 6.5 Existing permissions

**None.** Demo `security` role referenced in discovery docs only.

## 6.6 Mobile readiness

❌ **Not ready** — greenfield (E25).

## 6.7 Missing APIs (entire module)

| Priority | Capability | Backlog |
|----------|------------|---------|
| **P2** | `POST /visitors/check-in`, `POST /visitors/{id}/check-out` | E25.1.1 |
| **P2** | `GET /visitors?on_site=1` | Front-desk board |
| **P2** | Blacklist + gate pass | E25.1.2 |

**Workspace implication:** **Visitor Management** ships as **empty state + IA copy** until E25; do not stub with `activity_logs`.

---

# 7. Security audit

## 7.1 Purpose & business process

IA positions **Security** as incident log + audit/security center sibling to Visitors. Platform audit/backup also mirrors in Settings. **Today:** `activity_logs` (user actions on models) and Laravel `system_logs`; backup/restore Super Admin web UI. **No** campus incident entity, no Security Officer workflow ([`01-admin-discovery.md`](../admin-app/01-admin-discovery.md) §20).

## 7.2 Existing APIs

**None** for activity/system logs or incidents.

## 7.3 Existing controllers (web)

| Controller | Responsibility |
|------------|----------------|
| `ActivityLogController` | Filterable activity log viewer |
| `SystemLogController` | App log viewer, clear, download |
| `BackupRestoreController` | Backup create/restore/schedule (Settings-adjacent) |

**Middleware:** `activity-logs.*` → `role:Super Admin|Admin`; `system-logs.*` → Super Admin patterns.

**Passive capture:** `ActivityLogger` middleware on web group; explicit `ActivityLog::log()` in POS, inventory views, etc.

## 7.4 Existing database tables

| Table | Purpose |
|-------|---------|
| `activity_logs` | user_id, action, model morph, old/new values, route, IP |
| `audit_logs` | Platform audit (per DB audit doc) |
| `backup_settings` | Scheduled backup config |

**No** `security_incidents` or `gate_passes` tables.

## 7.5 Existing permissions

Implicit **Super Admin / Admin** role gates only. No `security.view` or `audit.view` in permission seeders. `@erp/core` defines `OPERATIONS_VIEW` + `RolePreset.SECURITY_OFFICER` but without backend permissions.

## 7.6 Mobile readiness

❌ **Not ready** for mobile audit center. Read-only log tail would require new authenticated API with pagination and PII policy.

## 7.7 Missing APIs

| Priority | Endpoint | Purpose |
|----------|----------|---------|
| **P1** | `GET /audit/activity-logs` | Security center read (paginated, filtered) |
| **P1** | `GET /audit/activity-logs/{id}` | Detail |
| **P2** | `GET /security/incidents` | Campus incidents (new module, overlaps E25) |
| **P2** | `POST /security/incidents` | Log incident |
| **P3** | `GET /system/backup-status` | DR posture (read-only for admins) |

---

# 8. Facilities audit

*(Requested audit area §7; not a top-level item in the sprint workspace tree — treated as cross-cutting **facilities condition reporting** and future optional section.)*

## 8.1 Purpose & business process

Weekly **Operations & Facilities** condition reports submitted by teachers/supervisors: campus area, status (Good/Fair/Poor), issues, responsible person, resolution flag. Lives under **Reports** web UI today, not a full CMMS.

## 8.2 Existing APIs

**None.**

## 8.3 Existing controllers (web)

| Controller | Responsibility |
|------------|----------------|
| `Reports\OperationsFacilityController` | List/create weekly facility reports |

**Routes:** `reports.operations-facilities.*` (index, create, store). Also linked from teacher nav.

## 8.4 Existing database tables

| Table | Purpose |
|-------|---------|
| `operations_facilities` | week_ending, campus (lower/upper), area, status, issue_noted, action_needed, responsible_person, resolved, notes |

## 8.5 Existing permissions

No dedicated permission; gated by report/teacher routes. Not part of `operations.view` taxonomy.

## 8.6 Mobile readiness

❌ Web form only.

## 8.7 Missing APIs

| Priority | Endpoint | Purpose |
|----------|----------|---------|
| **P2** | `GET /facilities/reports?week_ending=` | Read weekly reports |
| **P2** | `POST /facilities/reports` | Submit from mobile |
| **P3** | Work-order / CMMS | Future-state beyond weekly log |

**Mapping:** Until promoted to Operations nav, link from **Security** or **Reports → Operations** in Admin App.

---

# 9. Role & workflow summary

| Role (target) | Operations home (IA) | System today |
|---------------|----------------------|--------------|
| Transport Manager | Transport | **Role not seeded**; Admin/Secretary use web transport |
| Store Keeper | Inventory | **Role not seeded**; Admin/Secretary on inventory web |
| Nurse | Clinic | **Role not seeded**; medical records under Students |
| Receptionist | Visitors | **Module missing** |
| Security Officer | Security | Demo only; activity logs Admin-only |
| Driver | Driver app / trips | Driver role + `/driver/trips` API |
| Teacher | Pickup/requirements | `/teacher/transport/*`, `/teacher/requirements/*` |

**Approval queues (fragmented, web-only):** `driver_change_requests`, `transport_special_assignments` (pending paths), `requisitions` — all candidates for unified Approvals inbox (IA §8) but **no API** today.

---

# 10. Operations dashboard KPIs

## 10.1 Proposed KPIs (Admin Operations Dashboard)

| KPI | Ideal source | Available today | MVP composition |
|-----|--------------|-----------------|-----------------|
| Active trips / routes | Trip count with driver | ⚠️ Partial | Client count `GET /routes?per_page=100` |
| Trips without driver | Transport dashboard logic | ❌ | Needs `/transport/summary` |
| Students unassigned | TransportController query | ❌ | Needs summary API |
| Active special assignments | `transport_special_assignments` | ❌ | Web only |
| Low stock items | `inventory_items` | ❌ | Web only |
| Open requisitions | `requisitions` pending/approved | ❌ | Web only |
| Pending requirement collections | `student_requirements` | ❌ | Per-student API only |
| Visitors on site | Visitor module | ❌ | Empty state |
| Open incidents | Security module | ❌ | Empty state |
| Facilities issues (unresolved) | `operations_facilities` | ❌ | Web only |
| Library overdue *(adjacent)* | `book_borrowings` | ❌ | Web only |

## 10.2 Recommended dedicated endpoint (post-MVP)

`GET /operations/summary` returning:

```json
{
  "transport": { "trips": 12, "trips_without_driver": 2, "students_unassigned": 45, "special_assignments_active": 3 },
  "inventory": { "low_stock": 7, "requisitions_pending": 4 },
  "clinic": { "visits_today": null },
  "visitors": { "on_site": null },
  "security": { "open_incidents": null },
  "facilities": { "unresolved_issues": 5 }
}
```

**No such endpoint exists** — mirror Academics/Finance client composition first, then add thin aggregator on Laravel.

---

# 11. Read-only MVP design

Replace `OperationsScreen` placeholder with **OperationsStackNavigator** (mirror `FinanceStackNavigator` / `AcademicsStackNavigator`).

## 11.1 Scope

- **Read-only** oversight — no pickup POST, requisition approve, assignment import, or medical record write in Admin MVP.
- **Student drill-down** for Transport (assignment IDs on profile), Requirements (`/teacher/requirements/...`), Health flags (`GET /students/{id}`) — link to Student 360 when built.
- **Empty states** for Assets, Visitors, Security incidents with backlog references (E18, E25).
- **Defer** Library, Procurement, POS, Hostel sub-workspaces (IA siblings, out of sprint tree).

## 11.2 Workspace tree (MVP)

```
Operations (drawer / More)
└── OperationsStackNavigator
    ├── OperationsDashboardScreen
    ├── TransportStack (or section screens)
    │   ├── TripsListScreen          → GET /routes
    │   └── TripDetailScreen         → GET /routes/{id}
    ├── InventoryStack
    │   ├── RequirementsStudentsScreen → GET /teacher/requirements/students
    │   └── StudentRequirementsScreen  → GET /teacher/requirements/students/{id}/templates
    ├── AssetsScreen                   → empty state (E18)
    ├── ClinicStack
    │   ├── ClinicStudentsScreen       → GET /students (health flags)
    │   └── StudentHealthScreen        → GET /students/{id} (profile health fields)
    ├── VisitorManagementScreen        → empty state (E25)
    └── SecurityScreen                 → empty state + link note for audit (API blocked)
```

## 11.3 Screen → API / service / permission map (MVP)

| Screen | APIs (MVP) | Services | Tables | RBAC gate |
|--------|------------|----------|--------|-----------|
| **Dashboard** | `GET /routes` (count), optional `GET /library/books` (count) | Client aggregation | `trips`, `vehicles` | `operations.view` |
| **Trips list** | `GET /routes` | — | `trips`, `trip_stops`, `vehicles` | `operations.view` + `transport.view` |
| **Trip detail** | `GET /routes/{id}` | — | `trips`, `trip_stops`, `drop_off_points` | `transport.view` |
| **Vehicles panel** *(optional sub-panel)* | `GET /teacher/transport/vehicles` | — | `vehicles`, `trips` | `transport.view` |
| **Requirements students** | `GET /teacher/requirements/students` | — | `students`, `requirement_templates` | `operations.view` + `inventory.view` |
| **Student requirements** | `GET /teacher/requirements/students/{id}/templates` | — | `student_requirements`, `item_receipts` | `inventory.view` |
| **Assets** | — | — | — | `operations.view` |
| **Clinic students** | `GET /students?search=` | — | `students` (health columns) | `operations.view` + `students.view` |
| **Student health** | `GET /students/{id}` | — | `students`, `parents` | `students.view` |
| **Visitor Management** | — | — | — | `operations.view` |
| **Security** | — | — | `activity_logs` (blocked) | `operations.view` |

**Server permission reality:** Laravel may not emit `operations.view` — map from `transport.view` OR `inventory.view` until E4 permission harmonization; client already uses `operations.view` in `@erp/core`.

## 11.4 Data layer (`@erp/core` — proposed)

```
types/operations.ts
operations/normalize.ts
api/operationsWorkspace.api.ts    // routes, teacher transport vehicles, teacher requirements
operations/fetchOperationsDashboard.ts
query/hooks/useOperationsWorkspace.ts
queryKeys.operations.*
```

Port patterns from legacy `transport.api.ts` but **only** endpoints that exist in `routes/api.php`. Drop stale `/vehicles`, `/transport/summary` client methods or guard behind feature flags.

## 11.5 Caching (TanStack Query)

| Key | staleTime | Notes |
|-----|-----------|-------|
| `operations.dashboard` | 60s | Composed KPIs |
| `operations.routes(filters)` | 45s | Trip list |
| `operations.routes.{id}` | 60s | Detail |
| `operations.requirements.students` | 45s | Class/search filters in key |
| `operations.requirements.{studentId}` | 60s | Invalidate on external collect (Staff App) |

## 11.6 MVP completion estimate

| Section | Without backend changes | Blocked on backend |
|---------|-------------------------|-------------------|
| Dashboard | ~20% (route count only) | All real operations KPIs |
| Transport | ~45% (trips list/detail, vehicle picklist) | Assignments, exceptions, attendance, summary |
| Inventory | ~30% (student requirements read) | Stock catalog, requisitions, low stock |
| Assets | ~0% (empty state only) | Entire E18 module |
| Clinic | ~25% (student health profile) | Medical records, visit log |
| Visitor Management | ~0% (empty state) | Entire E25 module |
| Security | ~0% (empty state) | Activity log API, incidents |

**Overall read-only MVP:** ~**22%** implementable immediately; ~**78%** needs thin read APIs or greenfield modules.

---

# 12. Full workspace design

Target end-state after backend Phase 2 (read wrappers) + Phase 3 (R6 modules).

## 12.1 Workspace tree (full)

```
Operations
├── Dashboard                 KPIs, alerts, quick links
├── Transport
│   ├── Trips                 Registry, detail, stops, driver/vehicle
│   ├── Vehicles              Fleet, documents/expiry
│   ├── Assignments           Student ↔ trip/drop-point
│   ├── Exceptions            Special assignments + driver-change queue
│   └── Attendance            Daily boarding history (read)
├── Inventory
│   ├── Stock                 Items, low stock, transactions
│   ├── Requisitions          Raise → approve → fulfill (read first)
│   └── Requirements          Templates + school-wide collection progress
├── Assets                    Fixed-asset register (E18)
├── Clinic
│   ├── Visits                School visit log
│   └── Student health        Profiles + medical record history
├── Visitor Management        Check-in/out, on-site board, blacklist
└── Security
    ├── Incidents             Campus safety log
    └── Audit center          Activity log (read); backup status link
```

## 12.2 Screen → API / service / permission map (full)

| Screen | APIs (full) | Services | Tables | Permissions |
|--------|-------------|----------|--------|-------------|
| **Dashboard** | `GET /operations/summary`, `/transport/summary`, `/inventory/summary` | Aggregator service | Cross-domain | `operations.view` |
| **Trips list** | `GET /trips` or `/routes` + filters | — | `trips` | `transport.view` |
| **Trip detail** | `GET /routes/{id}`, `/trips/{id}/attendance` | `TransportAssignmentService` | `trips`, `trip_attendances` | `transport.view` |
| **Vehicles** | `GET /vehicles`, `GET /vehicles/{id}` | — | `vehicles` | `transport.view` |
| **Assignments** | `GET /student-assignments` | `TransportAssignmentService` | `student_assignments` | `transport.view` |
| **Exceptions** | `GET /transport/special-assignments`, `/transport/driver-change-requests` | — | `transport_special_assignments`, `driver_change_requests` | `transport.manage` |
| **Stock list** | `GET /inventory/items` | — | `inventory_items` | `inventory.view` |
| **Stock detail** | `GET /inventory/items/{id}` | — | `inventory_transactions` | `inventory.view` |
| **Requisitions** | `GET /requisitions`, show, approve POST | — | `requisitions`, `requisition_items` | `inventory.view` / approver |
| **Requirements registry** | `GET /student-requirements` | — | `student_requirements` | `inventory.view` |
| **Assets registry** | `GET /assets` (new) | Depreciation service | `assets` (new) | `assets.view` |
| **Clinic visits** | `GET /clinic/visits`, `POST` visit | Notification service | `clinic_visits` (new) | `clinic.view` |
| **Medical records** | `GET /students/{id}/medical-records` | — | `student_medical_records` | `clinic.view` |
| **Visitors on site** | `GET /visitors?on_site=1` | — | `visitor_logs` (new) | `visitors.view` |
| **Check-in/out** | `POST /visitors/check-in` etc. | Host notify | `visitors` (new) | `visitors.manage` |
| **Activity log** | `GET /audit/activity-logs` | — | `activity_logs` | `security.view` |
| **Incidents** | `GET/POST /security/incidents` | — | `security_incidents` (new) | `security.manage` |

---

# 13. What can be implemented immediately (no backend changes)

| Deliverable | Uses existing API | Notes |
|-------------|-------------------|-------|
| Operations stack navigator + section shells | — | Mirror Finance/Academics wiring in drawer |
| Trips list + detail (read) | `GET /routes`, `GET /routes/{id}` | Label as “Trips” in UI (API name is legacy “routes”) |
| Vehicles & trips picklist panel | `GET /teacher/transport/vehicles` | Admin role allowed in `canUseApi()` |
| Student transport IDs on profile | `GET /students/{id}` → `trip_id`, `drop_off_point_id` | Shallow assignment hint; not resolved legs |
| Requirements student list + detail | `GET /teacher/requirements/students`, templates | School-wide list works for Admin (no teacher filter) |
| Clinic student search + health flags | `GET /students`, `GET /students/{id}` | Allergies, immunization, emergency contacts |
| Assets / Visitors / Security placeholders | — | IA copy + backlog IDs (E18, E25) |
| RBAC gate `operations.view` | Client-only today | Also accept `transport.view` / `inventory.view` from API user permissions when synced |
| Fee clearance roster (cross-link) | `GET /routes/{id}/fee-clearance-roster` | Optional Finance ↔ Transport link |

**Cannot ship without backend:**

- Fleet registry with documents/expiry, assignment registry, exception approval queues, trip attendance history
- Inventory stock levels, low-stock alerts, requisition workflow
- Medical record list/history, clinic visit log, parent notification
- Any visitor or incident surface
- Activity/audit log viewer in mobile
- Facilities weekly reports
- Operations dashboard KPIs (meaningful)
- Fixed assets (entire module)

---

# 14. Missing APIs (consolidated priority)

| Priority | Endpoint / module | Unblocks |
|----------|-------------------|----------|
| **P0** | `GET /transport/summary` | Dashboard + Transport landing |
| **P0** | `GET /vehicles`, `GET /vehicles/{id}` | Fleet oversight |
| **P0** | `GET /inventory/items`, `GET /inventory/items/{id}` | Stock registry |
| **P0** | `GET /requisitions`, `GET /requisitions/{id}` | Inventory approvals view |
| **P0** | `GET /students/{id}/medical-records` | Clinic / Student 360 Health |
| **P1** | `GET /operations/summary` | Operations dashboard |
| **P1** | `GET /student-assignments` | Transport assignments screen |
| **P1** | `GET /transport/special-assignments`, `GET /transport/driver-change-requests` | Exception queues |
| **P1** | `GET /student-requirements?status=` | School requirements backlog |
| **P1** | `GET /audit/activity-logs` | Security center read |
| **P2** | Clinic visits CRUD (E24) | Clinic module |
| **P2** | Visitors module (E25) | Visitor Management |
| **P2** | `GET /facilities/reports` | Facilities read |
| **P2** | Assets module (E18) | Assets workspace |
| **P2** | `GET /trips/{id}/attendance` | Transport attendance read |
| **P3** | Live GPS, QR pickup (E21/R7) | Parent/Driver ecosystem |

---

# 15. RBAC recommendations

1. **Seed Laravel permissions** to match `@erp/core`: `operations.view`, `transport.view`, `transport.manage`, `inventory.view`, `inventory.manage`, `clinic.view`, `visitors.view`, `security.view`, `assets.view`.
2. **Harmonize seeders** — today `RolesAndPermissionsSeeder`, `PermissionSeeder`, and `Comprehensive2025Seeder` disagree; Admin App cannot rely on consistent permission names until E4.1.2.
3. **Sub-gates** inside Operations workspace: Transport section requires `transport.view`; Inventory requires `inventory.view`; Clinic requires `clinic.view` or `students.view`; Security audit requires `security.view` (Super Admin subset).
4. **Role presets** in `@erp/core` (`RolePreset.TRANSPORT_MANAGER`, `NURSE`, `STORE_KEEPER`, `SECURITY_OFFICER`) are defined but need matching Laravel roles (E4.2.1).
5. **Move transport web routes** from `role:` middleware to `permission:transport.view` when permission-first enforcement lands.

---

# 16. Adjacent modules (out of workspace tree, IA reference)

| Module | Web | API | Note |
|--------|-----|-----|------|
| **Library** | `library.*` full CRUD | `GET /library/books` only | Overdue analytics missing; Librarian role missing |
| **Procurement** | Requisitions only | — | PO/vendor is backlog E16 |
| **POS / Shop** | `pos.*`, public shop | `/public/shop/*` | Retail, not store inventory |
| **Hostel / Mess** | `hostel.*` | — | Fees not invoiced |
| **Swimming / Activities** | Dedicated controllers | Partial finance hooks | Extracurricular ops |

These remain in **Reports → Operations** or future expansion of the Operations drawer per [`02-admin-information-architecture.md`](../admin-app/02-admin-information-architecture.md).

---

# 17. References

| Artifact | Path |
|----------|------|
| Module inventory (Operations §D) | [`docs/system-audit/02-module-inventory.md`](../system-audit/02-module-inventory.md) |
| Business processes (§10, §14–16) | [`docs/system-audit/05-business-processes.md`](../system-audit/05-business-processes.md) |
| Future state (Transport, Clinic, Visitors, Assets) | [`docs/system-audit/10-future-state.md`](../system-audit/10-future-state.md) |
| Backlog R6/E21–E25, E18 | [`docs/prd/02-MASTER-PRODUCT-BACKLOG.md`](../prd/02-MASTER-PRODUCT-BACKLOG.md) |
| Admin IA (Operations tree, J7–J8) | [`docs/admin-app/02-admin-information-architecture.md`](../admin-app/02-admin-information-architecture.md) |
| UI specs (dashboard tiles, Student 360 health/transport) | [`docs/admin-app/03-admin-ui-specifications.md`](../admin-app/03-admin-ui-specifications.md) |
| Admin discovery (Clinic, Visitors, Security) | [`docs/admin-app/01-admin-discovery.md`](../admin-app/01-admin-discovery.md) |
| Finance workspace audit (pattern) | [`docs/finance/01-finance-workspace-audit.md`](../finance/01-finance-workspace-audit.md) |
| Academics workspace audit (pattern) | [`docs/academics/01-academics-workspace-audit.md`](../academics/01-academics-workspace-audit.md) |
| API routes | `routes/api.php` |
| Web transport | `routes/web.php` (`transport.*`) |
| Web inventory | `routes/web.php` (`inventory.*`) |
| Assignment resolution | `app/Services/TransportAssignmentService.php` |
| Legacy transport client (stale endpoints) | `mobile-app/src/api/transport.api.ts` |
| Admin placeholder | `mobile-app/apps/admin/src/features/operations/screens/OperationsScreen.tsx` |

---

*End of Operations Workspace Audit.*
