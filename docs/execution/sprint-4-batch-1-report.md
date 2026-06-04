# Sprint 4 — Batch 1 Report: Settings Hub Foundation

**Status:** Complete  
**Scope:** Read-only Settings Hub in the Admin App (School, Academic, Grading, Roles). No Finance, Communication, or Integrations.  
**Verification:** `tsc --noEmit` passes for `packages/core`, `packages/ui`, and `apps/admin`.

---

## 1. API audit summary (pre-implementation)

| Domain | Laravel mobile API before Batch 1 | Notes |
| --- | --- | --- |
| School settings | `GET /app-branding` only (public, unauthenticated) | Name, logo, colors — no contact/regional fields |
| Academic years | **None** | Web: `settings` + `AcademicYearTermsController` |
| Terms | **None** | Web: nested under academic years |
| Classes | `GET /classes` | Teacher-scoped for teacher roles |
| Streams | `GET /classes/{id}/streams` | Teacher-scoped |
| Subjects | `GET /classes/{id}/subjects` only | Class-scoped homework helper; no global catalog |
| Grading | Partial: `GET /marks/matrix/context` → `exam_types` | No grading schemes/bands |
| Roles | **None** | Web: `hr/roles` (Spatie) |
| User permissions | `GET /user` → `permissions[]` | Current user only, not role catalog |

**Conclusion:** A small read-only **Settings Hub API** was required for mobile; registry/class list endpoints were duplicated in an admin-scoped form where teacher filtering was wrong for settings.

---

## 2. APIs created (Laravel)

All under `auth:sanctum`, prefix `/api/settings/`, controller `ApiSettingsHubController`.

| Method | Path | Purpose |
| --- | --- | --- |
| GET | `/settings/school` | School identity, branding colors, modules, regional |
| GET | `/settings/academic-years` | All academic years |
| GET | `/settings/terms` | Terms (`?academic_year_id=` optional) |
| GET | `/settings/classes` | All classrooms (admin settings; not teacher-scoped) |
| GET | `/settings/classes/{classId}/streams` | Streams for a class |
| GET | `/settings/subjects` | Full subject catalog |
| GET | `/settings/grading` | Grading schemes + bands + exam types |
| GET | `/settings/roles` | Roles with permission names (read-only) |

**Access:** Super Admin, Admin, Secretary (parity with web settings), or Sanctum permission `settings.view`.

**Files:**

- `app/Http/Controllers/Api/ApiSettingsHubController.php` (new)
- `routes/api.php` (routes block added)

---

## 3. APIs reused (conceptually / not called directly by mobile)

| Existing endpoint | Relationship |
| --- | --- |
| `GET /app-branding` | Logic mirrored in `/settings/school` (authenticated, richer) |
| `GET /classes` | Superseded for settings by `/settings/classes` (full list) |
| `GET /classes/{id}/streams` | Superseded by `/settings/classes/{id}/streams` |
| `GET /marks/matrix/context` | Exam types now included in `/settings/grading` |
| `GET /user` | Still used for session RBAC; not role catalog |

Mobile **only** calls the new `/api/settings/*` routes for hub data.

---

## 4. Mobile files created

| Path | Purpose |
| --- | --- |
| `packages/core/src/types/settings.ts` | DTO types |
| `packages/core/src/api/settings.api.ts` | API client |
| `packages/core/src/query/hooks/useSettingsHub.ts` | TanStack Query hooks |
| `packages/ui/src/settings/*` | `SettingsHubLayout`, `SettingCard`, headers |
| `apps/admin/src/features/settings/sections/*` | Four section panels |
| `apps/admin/src/features/settings/screens/SettingsScreen.tsx` | Hub shell |

---

## 5. Mobile files modified

| Path | Change |
| --- | --- |
| `packages/core/src/query/queryKeys.ts` | `queryKeys.settings.*` |
| `packages/core/src/api/index.ts` | Export settings API |
| `packages/core/src/types/index.ts` | Export settings types |
| `packages/core/src/query/index.ts` | Export hooks |
| `packages/ui/src/index.ts` | Export settings UI |
| `apps/admin/src/features/settings/index.ts` | Section exports |

---

## 6. UI implementation (IA §5 Settings Hub)

| Requirement | Implementation |
| --- | --- |
| Settings Hub Layout | `SettingsHubLayout` — horizontal section nav + scrollable content |
| Settings Navigation | Chips: School · Academic · Grading · Roles (Finance/Communication/Integrations omitted) |
| School Settings Section | `SchoolSettingsSection` — read-only `SettingCard` list |
| Academic Settings Section | Years, terms, classes, subjects summary + class/stream drill-down |
| Grading Settings Section | Schemes, bands preview, exam types |
| Roles & Permissions | Expandable read-only role cards + permission list |

**RBAC:** `useCan('settings.view')` gates the screen; drawer area already requires `settings.view` via `AREA_VIEW_PERMISSIONS`.

---

## 7. Query architecture

```text
SettingsScreen
  activeSection state → mounts one section component
  SchoolSettingsSection     → useSchoolSettings()
  AcademicSettingsSection   → useAcademicYearsSettings()
                            → useTermsSettings()
                            → useSettingsClasses()
                            → useSettingsSubjects()
                            → useSettingsStreams(classId)  // on class tap
  GradingSettingsSection    → useGradingSettings()
  RolesSettingsSection      → useRolesSettings()
```

**Query keys** (`queryKeys.settings`):

- `school()`
- `academicYears()`
- `terms(academicYearId?)`
- `classes()`
- `streams(classId)`
- `subjects()`
- `grading()`
- `roles()`

Queries run only while their section component is mounted (tab switch unmounts inactive sections).

---

## 8. Caching strategy

| Query | staleTime | Rationale |
| --- | --- | --- |
| All settings hub queries | 120 s (2 min) | Config changes infrequently; reduces repeat tab switches |
| No `gcTime` override | Default | TanStack Query cache retained for back navigation |

Refetch: per-section **Retry** on error. No pull-to-refresh in Batch 1.

---

## 9. Risks and limitations

| Risk | Mitigation / note |
| --- | --- |
| **Read-only only** | Lock icon on cards; roles section states edits are web-only |
| **No PUT/PATCH APIs** | Batch 2+ for inline edit; web portal remains source of truth |
| **Large class/subject lists** | Academic section shows first 12 classes; full list on web |
| **Permission `settings.view` on server** | May not exist on all Spatie seeds — Super Admin/Admin/Secretary still pass |
| **Teacher using `/classes` elsewhere** | Unchanged; settings uses dedicated admin-scoped endpoints |
| **Branches / school days / holidays** | Not in Batch 1 scope |
| **Finance branding keys** | School colors read from `finance_*_color` settings (same as portal) |

---

## 10. Out of scope (confirmed)

- Finance, Communication, Integrations sections  
- Setting edit / upload logo on mobile  
- Branch management  
- School days / holiday calendar UI  
- Role permission editing  

---

## 11. Suggested follow-ups (Batch 2+)

1. `PUT /api/settings/school` (subset) for mobile edit where safe.  
2. Term/year picker with filtered `useTermsSettings(yearId)`.  
3. Search-within-settings across cards.  
4. Pull-to-refresh invalidating `queryKeys.settings.all`.  
5. Align Spatie seed with `settings.view` for non-admin roles that need read-only hub.
