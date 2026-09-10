# Users App — Final UX Audit (Phase 7L)

**Date:** 2026-09-09  
**Scope:** Users Expo app (`mobile-app/apps/users`) + related Laravel APIs after Phases 7A–7K

---

## Completed features

| Area | Status |
|------|--------|
| Parent Home redesign (7B) | Done — child selector, school-life tiles, fees hub |
| Parent absence reporting (7C) | Done — Attendance → Report absence; server auth |
| Dual-role Home/Work (7D) | Done — cache purge, Admin Work hand-off |
| Teacher Home IA (7E) | Done — core daily work on Home; More slimmed |
| Homework student targeting (7F) | Done — class vs specific students; scoped notify |
| Diary channels (7G) | Done — `teacher_parent` / `admin_parent` + backend ACL |
| International phone field (7H) | Started — reusable `InternationalPhoneField` (wire into all profiles continues) |
| Staff leave times (7I) | **Not added** — model is date-only; no partial-day concept |
| Notification history (7J) | Existing history + unread filters retained; reminders split 8am/9am in-app |
| Activity notify privacy (7K) | Fixed — relationship-scoped recipients |
| Attendance reminders | 08:00 all teachers missing clock-in; 09:00 class teachers missing attendance |

---

## Security findings

| Finding | Severity | Notes |
|---------|----------|--------|
| Co-curricular staff fan-out fixed | Was Critical | Now class teacher + explicit senior assignment + office |
| Diary admin channel blocked for teachers | High | Enforced in `ApiDiaryController` |
| Homework student targeting auth | High | Students must belong to selected class |
| Mode cache purge | Medium | `invalidateQueriesForAppMode` on switch |
| Admin Work in Users app | Medium | Routes to Admin app deep link, not teacher UI |
| Phone i18n not fully rolled to every form | Medium | Component ready; profile screens need full adoption |
| Senior Teacher `getSupervisedClassroomIds` still returns all classrooms | High (architecture) | Explicit assignment table used for notify; helper still over-broad elsewhere |

---

## Privacy findings

- Parent access remains `canAccessStudent` / `accessibleStudentIds` based.
- Activity-change and absence staff alerts no longer notify all Senior Teachers by role.
- Homework notifications for `target_scope=students` only notify those students’ parents.
- Diary channels separate teacher vs admin conversations server-side.
- Notification list remains owned by `$request->user()->notifications()`.

---

## Remaining UI debt

- Wire `InternationalPhoneField` into parent profile review, staff profile, M-Pesa, wallet top-up.
- Parent contact “at least one of father/mother/guardian” validation still needs end-to-end UI + API alignment pass.
- Teacher shared `DiaryListScreen` should hide admin channel (teachers never call it).
- Notification category chips/labels can be richer once types are normalized.
- Pre-existing TypeScript unused-var noise in navigators.

---

## Remaining architectural issues

1. Diary migration must run in production before admin_parent threads are reliable (`2026_09_09_150000_add_channel_to_student_diaries_table`).
2. Leave remains full-day / date-range only — do not invent times without product/calendar redesign.
3. libphonenumber not yet adopted server-side (`PhoneNumberService` length rules only).
4. Announcement push fan-out remains broad (out of this phase set).
5. API feature tests skip on mysql locally — run with sqlite CI.

---

## Tests

| Suite | Result |
|-------|--------|
| Jest `parentHomePhase7b`, `parentAbsencePhase7c`, `appModePhase7d` | Pass (earlier) |
| PHPUnit `ParentAbsenceApiTest`, `ParentActivityRequestNotifyTest`, `DiaryChannelAuthorizationTest` | Skipped on mysql; designed for sqlite |
| New teacher home / homework / phone unit scans | Add with next CI pass |

---

## Known limitations

- Expo E2E / device matrix not executed in this session.
- Deep-link typing still parent-stack biased for some notification opens from teacher shell.
- Admin dual Home in Admin app remains thinner than Users parent shell.
