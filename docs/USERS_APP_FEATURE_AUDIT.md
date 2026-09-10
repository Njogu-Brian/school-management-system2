# Users App — Functional & Security Inventory (Phase 7A)

**Date:** 2026-09-09  
**Scope:** `mobile-app/apps/users`, shared `mobile-app/packages/core`, and Laravel APIs that serve the Users App  
**Status:** Inventory only — no application code changed in Phase 7A

---

## 1. Executive summary

The Users Expo app is a multi-identity shell (`UsersRootNavigator` → `RoleBasedNavigator`) that already has a partial **Home / Work** mode model (`AppModeContext`, `can_home_mode` / `can_work_mode`). Parent, teacher, student, and driver experiences exist; staff self-service is largely hosted on the teacher “More” shell; admin/director **work** belongs in the Admin app.

**Critical confirmed bug:** when a parent submits a co-curricular join/leave request, `ParentCoCurricularService::notifyAdminsOfRequest()` fans out to **every** user with roles `Super Admin`, `Admin`, `Secretary`, `Senior Teacher`, or `Director` — **not** scoped to the child’s classroom, campus, or class teacher. This matches the report: *“A parent switched the activity of their child and a senior teacher received a notification.”*

**Other high-risk themes:**

| Theme | Finding |
|--------|---------|
| Mode cache isolation | Mode switch remounts navigators but does **not** invalidate React Query / persisted cache by mode |
| Parent absence reporting | **Not implemented** (read-only attendance calendar only) |
| Messaging | Diary is the only parent↔staff thread; no separate parent-admin channel |
| Phone validation | Backend uses custom `PhoneNumberService` (not libphonenumber); mobile mostly empty-check only |
| Tests | Virtually no Users-app mobile tests; backend notification recipient tests missing for activity change |

---

## 2. Architecture overview

| Layer | Path | Behavior |
|--------|------|----------|
| Entry | `mobile-app/apps/users/App.tsx` | Providers + root navigator |
| Root gate | `.../navigation/UsersRootNavigator.tsx` | School → auth → `canAccessApp(user,'users')` → AccessDenied → biometrics/PIN → `AppModeProvider` + `RoleBasedNavigator` |
| Role shell | `.../navigation/RoleBasedNavigator.tsx` | Dual + `mode==='home'` → Parent; Admin/Director with `parentId`/`canHomeMode` → Parent; Teacher/Senior/Supervisor → Teacher; Parent/Guardian → Parent; Student; Driver |
| Roles | `mobile-app/packages/core/src/config/roles.ts` | `USERS_APP_ROLES` vs `ADMIN_APP_ROLES` |
| Access helpers | `.../auth/roleUtils.ts` | Directors always Users-eligible; other admin roles only if linked parent |
| Auth payload | `AuthApiController::formatUserForApi` | `role`, `permissions`, `parent_id`, `staff_id`, `teacher_id`, `can_home_mode`, `can_work_mode`, … |
| Mode | `AppModeContext.tsx` + `appModeStorage.ts` | Persist `@erp` app mode; dual identity = `canWork && canHome` |

---

## 3. PARENT feature map

**Navigator:** `ParentTabNavigator.tsx` — tabs: **Home / Children / Fees / Academic / More**  
**Stack types:** `parentStackTypes.ts`

UI pattern (common): skeletons → `EmptyState` (+ Retry) → content; toasts via `showError` / `showSuccess` / `confirmAction`.

### 3.1 Home

| Field | Detail |
|--------|--------|
| **Screen** | `features/parent/screens/ParentScreens.tsx` → `ParentHomeScreen` |
| **Route** | Tab `ParentHomeTab` → `ParentHome` |
| **APIs** | `useInfiniteStudentList`, `useStudentStats` (≤4 kids), `useUnreadNotificationCount`, `useStudentReportCards` |
| **Backend auth** | Student list scoped via `User::shouldScopeAsParent()` / `accessibleStudentIds()` |
| **Models** | `Student` ↔ `ParentInfo` via `parent_id`; `User.parent_id` |
| **Current UI** | Hero, school-fees snapshot, quick actions, school actions, child results snapshot |
| **Loading** | `SkeletonListRows` / fee “…” |
| **Empty** | Implicit when no children / no published cards (results return `null`) |
| **Error** | Query errors partially surfaced; logout available |
| **Tests** | None (mobile) |
| **Privacy risks** | Aggregated fee balances for first 4 children only; incomplete family picture |
| **Gap** | Attendance, Transport, Homework, Settings buried or missing from primary Home tiles; Diary/Notifications live under More |

### 3.2 Children

| Field | Detail |
|--------|--------|
| **Screens** | `ParentChildrenScreen`; hub `ChildHubScreen.tsx`; profile `ChildProfileScreen.tsx` |
| **Routes** | `ParentChildrenTab` → `ChildrenList` → `ChildHub` / `ChildProfile` / … |
| **APIs** | `useInfiniteStudentList`; `useStudentDetail`; profile review `GET/PUT /parent/profile-review` |
| **Backend auth** | `canAccessStudent` |
| **UI / states** | Empty “No children linked”; missing-student EmptyState |
| **Tests** | None |
| **Privacy** | Medical/allergy/emergency/Kemis fields editable |

### 3.3 Attendance

| Field | Detail |
|--------|--------|
| **Screen** | `ChildAttendanceScreen.tsx` |
| **Route** | `ChildAttendance` `{ studentId }` (Home/Children/Academic stacks) |
| **APIs** | `GET /students/{id}/attendance-calendar`, trend endpoints |
| **Backend auth** | Student access check on `ApiStudentController` |
| **Models** | `Attendance`, `AttendanceReasonCode`, `AttendanceRecipient` |
| **UI** | Calendar + trend; skeleton / empty month |
| **Tests** | None for parent path |
| **Privacy** | Child-scoped if API enforces `canAccessStudent` |
| **Gap** | **No parent absence reporting API or UI** |

### 3.4 Fees / Payments / Statements / Invoices / Wallet

| Feature | Screen(s) | Route | API |
|---------|-----------|-------|-----|
| Fees hub | `ParentFeesScreen` | `ParentFeesTab` → `FeesHome` | student stats, `financeApi.listInvoices`, payment link |
| Statements | `StudentStatementScreen` | `StudentStatement` | `GET /students/{id}/statement` |
| Invoices | Fees list + `ParentInvoiceDetailScreen` | `InvoiceDetail` | `GET /invoices`, `/invoices/{id}` |
| Payments | Detail only `ParentPaymentDetailScreen` | `PaymentDetail` | `GET /payments/{id}` |
| M-Pesa | `MpesaPromptScreen` | `MpesaPrompt` | `POST /students/{id}/mpesa/prompt` |
| Wallet | `wallet/ParentWallet*` | `WalletHome`, TopUp, SavingPlans | `/parent-wallet*` |

| Cross-cutting | Detail |
|---------------|--------|
| **Backend auth** | Statement/show use parent access; invoice index relies on `student_id` + show auth — verify filters carefully |
| **Models** | `Invoice`, `Payment`, `ParentWallet`, `ParentWalletSavingPlan`, `OptionalFee`, `FeeStructure` |
| **Loading / empty / error** | Present on wallet; fees hub mixed |
| **Tests** | Limited / none for parent mobile path |
| **Privacy** | Balances, invoices, payment links; wallet phone used for STK with weak client validation |
| **UX gap** | Fees, invoices, statements, wallet are split across Fees tab + More → Wallets; Home only shows aggregate due |

### 3.5 Academic / results

| Field | Detail |
|--------|--------|
| **Screens** | `ParentAcademicScreen`, `ChildResultsScreen`, `ParentReportCardDetailScreen`, `ChildAcademicProgressSection` |
| **Routes** | `ParentAcademicTab` → `AcademicHome`; `ChildResults`; `ReportCardDetail` |
| **APIs** | report cards, assessment history |
| **Auth** | Student-scoped |
| **UI** | Tab exists; Home shows small published-card snapshot only |
| **Tests** | None |

### 3.6 Transport

| Field | Detail |
|--------|--------|
| **Screens** | `TransportScreen`, `LiveBusTrackScreen` |
| **Routes** | `Transport`, `LiveBusTrack` `{ studentId }` |
| **APIs** | `/parent/transport-options`, live bus `/transport/live/students/{id}` |
| **Privacy** | Live location / trip PII |
| **Home access** | Not a primary Home tile today (reachable via child hub / stacks) |

### 3.7 Diary (messaging)

| Field | Detail |
|--------|--------|
| **Screens** | Parent `DiaryListScreen`; shared `DiaryChatScreen` |
| **Routes** | `DiaryList`, `DiaryChat` `{ studentId }` — often via **More** |
| **APIs** | `GET/POST /diaries…` (`ApiDiaryController`) |
| **Auth** | Parents → accessible children; teachers → assigned/supervised classrooms; admin/secretary/academic admin → all |
| **Model** | One thread per student (`StudentDiary` + `DiaryEntry`); `author_type` parent\|teacher\|admin |
| **Notify** | Teacher/admin posts → parents; **parent posts do not push to teachers** |
| **Channel separation** | **No** separate parent-admin vs parent-teacher conversations — shared per-student thread |
| **Tests** | None |
| **Privacy** | Free text + attachments; admins can see all threads |

### 3.8 Homework

| Field | Detail |
|--------|--------|
| **Screen** | `ChildHomeworkScreen` + `useChildHomework` |
| **Route** | `ChildHomework` |
| **APIs** | `GET /assignments` filtered; diary status/complete |
| **Auth** | Guardian required for complete/uncomplete |
| **Home** | Not a primary Home tile |

### 3.9 Notifications

| Field | Detail |
|--------|--------|
| **Screen** | `features/notifications/screens/NotificationsListScreen.tsx` |
| **Route** | Shared `Notifications` on parent stacks (often via More) |
| **APIs** | `/notifications`, unread-count, read, acknowledge, delete, preferences, device-tokens |
| **Model** | Laravel `notifications` on `User`; Expo tokens in `user_device_tokens` |
| **Push** | `UsersPushNotifications.tsx` + `usePushNotifications`; channel `parent-alerts` |
| **Read/unread** | Supported via API |
| **Gap** | Push enable list omits admin/director roles even when using Users as parent |
| **Deep links** | Typed against parent stack — risky when opened from teacher shell |

### 3.10 Profile / Settings

| Field | Detail |
|--------|--------|
| **Profile** | `MyProfileScreen` + `ParentAccountProfileSection` when `parentId` |
| **Settings** | `SettingsScreen` (theme, PIN, legal, logout) |
| **Parent data** | `GET/PUT /parent/profile-review` — father/mother/guardian phones, WhatsApp, email, student medical/Kemis |
| **Phone validation** | Client: mostly non-empty; server: `PhoneNumberService` (country-length heuristics, not libphonenumber) |

### 3.11 Also present (parent)

- Co-curricular join/leave: `CoCurricularHubScreen`, `CoCurricularChildScreen` → `POST /students/{id}/co-curricular`
- Requirements, Announcements, Concerns

---

## 4. TEACHER feature map

**Navigator:** `TeacherNavigator.tsx` — tabs: **Home / Classes / Attendance / Activities / More**

| Feature | Screens | Entry | APIs / notes |
|---------|---------|-------|--------------|
| Home | `TeacherHomeScreen` | Home tab | classrooms, unread, quick actions |
| Attendance | `MarkAttendanceScreen` | Attendance tab | mark attendance; offline drafts |
| Transport | `TeacherTransportScreen` | More | `/teacher/transport/*` |
| Diary | shared diary | More / Academics | same diary API |
| Homework | `AssignmentsHub`, `CreateAssignment` | More | `/assignments` |
| Academic/marks | Marks hub/entry/matrix, report cards, lesson plans | More / Academics | exams, moderation (senior) |
| Activities | `ActivitiesHub`, `ActivityAttendance` | Activities tab | activity attendance (not class SMS) |
| Notifications / Profile / Settings | shared | More | |
| More | `TeacherMoreHubScreen` | More | academics, requirements, clock, leave, advances, payslips, transport, diary, concerns, **AppModeSwitch** |

Teacher Diary/Homework/Marks are **not** top-level tabs.

---

## 5. STAFF feature map

No dedicated staff-only navigator. HR self-service under teacher More / `features/me`:

| Feature | Screen | API |
|---------|--------|-----|
| Profile | `MyProfileScreen` | staff detail/update, documents |
| Leave | `MyLeaveListScreen`, `LeaveApplyScreen` | `/leave-requests`, leave types, balances |
| Clock | `StaffClockScreen` | `/staff-attendance/*` |
| Payslips / Advances | `MyPayslips*`, `MyAdvancesScreen` | payroll / advances |
| Notifications | shared | |

Pure secretary/accountant without parent link → **AccessDenied** on Users app.

Leave index: admins all; supervisors subordinates; others own staff. **No leave push** in leave controller.

---

## 6. ADMIN / DIRECTOR / SUPERADMIN

| Role | Users app | Admin app |
|------|-----------|-----------|
| Director | Always allowed; parent shell if linked child, else AccessDenied “link a child” | Full admin |
| Admin / Super Admin / other `ADMIN_APP_ROLES` | Only if `parentId` / `canHomeMode` → **ParentTabNavigator** | Full admin; optional thin `AdminParentHomeScreen` when `mode==='home'` |
| Work in Users | **Not available** — comment: work stays in Admin app |

Primary role in API prefers staff roles over Parent for dual accounts (`resolvePrimaryRoleName`).

---

## 7. DUAL ROLE — current behavior

| Case | Current handling |
|------|------------------|
| **Teacher + Parent** | `userHasDualIdentity`; `AppModeSwitch` on Parent More + Teacher More; Home → `ParentTabNavigator`; Work → Teacher navigator. Remount on switch. |
| **Staff + Parent** | Same binary if `staffId` ⇒ work; non-teacher staff may still land on teacher shell for self-service or be denied if role not in Users teacher set — **verify per role** |
| **Director/Admin/Superadmin + Parent** | Users app: **always parent shell** when linked (no work mode inside Users). Admin app: thin Home + switch back to admin work. |

### Home / Work coexistence & conflicts

1. Shared AsyncStorage mode across Users and Admin apps  
2. Navigator remount clears stack but **not** React Query / persisted prefixes (`students`, `finance`, `notifications`)  
3. Admin dual Home ≠ full Users parent experience  
4. Push role allow-list may skip admin-as-parent  
5. Notifications deep-link typing assumes Parent stack  
6. No mode-scoped query key namespace  

---

## 8. Cross-cutting models & logic

### 8.1 Parent ↔ student

- `ParentInfo` (`parent_info`); `Student.parent_id`; `User.parent_id`  
- Access: `User::accessibleStudentIds()` / `canAccessStudent()` — direct children + siblings sharing `family_id`  
- **Implication:** parent access is relationship-based, not “has Parent role”

### 8.2 Attendance

- Teacher mark: `POST /attendance/mark` → parent notify + SMS when absent today  
- Parent calendar: read-only  
- **No** parent-initiated absence/excuse API

### 8.3 Diary / homework

- Diary: per-student thread; author_type distinguishes sender; no channel isolation  
- Homework: classroom-scoped; parents complete for own child

### 8.4 Notifications stack

| Piece | Implementation |
|--------|----------------|
| Persistence | Laravel DB notifications on `User` |
| Push | `ExpoPushService` via `AppChannelNotifyService` / `ParentAppNotifyService` |
| Preferences | Stored JSON; **not enforced** on all push paths |
| Announcements | Broad fan-out (all tokens / broad staff roles) — privacy concern |

### 8.5 Phone numbers

- `PhoneNumberService` — length-by-country; comment to integrate libphonenumber later  
- `MpesaGateway::isValidKenyanPhone` for wallet/M-Pesa (Kenya-specific)  
- Mobile: empty checks only on many screens  

### 8.6 Activity switching (parent)

- Co-curricular join/leave requests (`ParentActivityChangeRequest`), not a global “selected activity” context  
- Child selection is per-route `studentId` (no global active-child store)

---

## 9. Reported issue — activity change → senior teacher notification

### 9.1 Investigation results

| Question | Answer |
|----------|--------|
| **1. What notification?** | Title: **“Activity change to confirm”**; body: `"{child} — parent asked to {join\|leave} {activity} (Term {n} {year})."`; payload `type: parent_activity_request`, `request_id`, `student_id` |
| **2. What triggered it?** | Parent `POST /api/students/{student}/co-curricular` → `ApiParentCoCurricularController::store` → `ParentCoCurricularService::requestChange()` → `notifyAdminsOfRequest()` |
| **3. Who received it?** | Every `User` with Spatie role in `['Super Admin','Admin','Secretary','Senior Teacher','Director']` |
| **4. Audience buckets** | **Not** class-teacher-only. Includes **all Senior Teachers**, all Admins/Secretaries/Directors/Super Admins. **Not** all teachers, all staff, all parents, or students (except parents get a separate scoped “Activity change sent” via `ParentAppNotifyService`) |
| **5. Backend logic** | Hard-coded `whereHas('roles', whereIn(...))` then `AppChannelNotifyService::notifyUsers` — DB + Expo push |
| **6. Relationship-scoped?** | **No** |

Parent confirmation path (`notifyActivityChangeSubmitted`) **is** correctly scoped to users with the same `parent_id`.

Web approval: `ParentActivityRequestController` (web), not mobile approvals API.

### 9.2 Recommendation — correct staff audience

Based on existing relationships (`classrooms.class_teacher_id`, `classroom_teacher`, senior supervision helpers, web approval by office roles):

**Should receive staff alert (default):**

1. **Office approvers** who already process parent activity requests on web: `Admin`, `Secretary`, `Super Admin` (and `Director` if they approve in this school’s workflow)  
2. **Child’s class teacher** (homeroom via `classrooms.class_teacher_id` → `Staff` → `User`) — informational / pastoral awareness  
3. **Senior Teacher / academic supervisor** **only if** they supervise the child’s classroom (use explicit supervision assignment — **not** “all Senior Teacher role holders”)

**Must not receive:**

- Unrelated Senior Teachers / teachers  
- Unrelated parents, students, or all staff  
- Role-only fan-out lists

**Also:** add a feature test that a Senior Teacher **outside** the child’s classroom does **not** receive `parent_activity_request`.

> Note: `User::getSupervisedClassroomIds()` currently returns **all classrooms** for primary Senior Teacher users — any “supervise classroom” recipient rule must use a tighter assignment source or fix that helper separately (architecture stop condition if redesign is required).

---

## 10. Privacy & security risk register

| ID | Risk | Severity |
|----|------|----------|
| P1 | Co-curricular staff notify is role-wide (Senior Teacher) | **Critical** |
| P2 | Mode switch without cache isolation (finance/students persist) | High |
| P3 | Diary has no parent-admin vs parent-teacher channel separation | Medium–High |
| P4 | Announcement push/in-app broad fan-out | Medium |
| P5 | Notification preferences not enforced on all push paths | Medium |
| P6 | Weak/client-Kenya phone checks for STK / WhatsApp fields | Medium |
| P7 | Live bus location on device | Medium |
| P8 | Medical/emergency data in profile review | Medium |
| P9 | Staff bank/tax IDs on profile screens | Medium |
| P10 | Push disabled for admin-as-parent roles | Medium (availability) |
| P11 | Deep links typed as Parent stack from other shells | Medium |
| P12 | Invoice list parent-scoping must be verified | Medium |
| P13 | No parent absence API → future feature must not invent parallel attendance without auth | — |
| P14 | Senior Teacher “supervise all classrooms” helper may over-scope future fixes | High (architecture) |

---

## 11. Backend changes required (for later phases)

| Priority | Change |
|----------|--------|
| P0 | Fix `notifyAdminsOfRequest` recipient resolution (relationship-scoped) + tests |
| P0 (7C) | Design/extend parent absence reporting API with `canAccessStudent`, calendar rules, recipient rules — **do not invent a second attendance system without inspecting mark/excuse fields** |
| P1 (7D) | Mode-aware cache invalidation contract; confirm admin Work routes to Admin app only |
| P1 | Enforce notification preferences on push |
| P2 | Separate diary oversight permissions for admin vs teacher (policy review) |
| P2 | International phone validation (libphonenumber or extend `PhoneNumberService` safely) |
| P2 | Clarify/fix Senior Teacher classroom supervision scope if used for notifications |

---

## 12. Proposed phases (post-7A)

| Phase | Focus |
|-------|--------|
| **7B** | Parent Home redesign — direct tiles for attendance, transport, diary, homework, academics, school fees hub, notifications, settings; child selector; coherent fees area |
| **7C** | Parent child absence reporting (extend existing attendance; server auth; relationship-scoped notify; tests) |
| **7D** | Harden dual-role Home/Work (cache/nav/deep-link/push isolation; admin Work → Admin app; tests) |
| **7E** | Fix co-curricular notification audience (P0 bug) if not bundled earlier |
| **7F** | Diary channel / messaging privacy; notification preference enforcement |
| **7G** | Phone validation internationalization (client + server) |
| **7H** | Staff-only work shell polish; leave notifications if product requires |

---

## 13. Feature checklist matrix (compact)

| Area | Screen | Nav | API | Auth | Tests | Privacy note |
|------|--------|-----|-----|------|-------|--------------|
| Parent Home | ParentScreens | Home tab | students/stats/notifs | parent scope | none | fee aggregate |
| Children | Children/Hub/Profile | Children tab | students, profile-review | canAccessStudent | none | medical |
| Attendance | ChildAttendance | child routes | attendance-calendar | student access | none | read-only |
| Fees hub | ParentFeesScreen | Fees tab | invoices, stats | mixed | none | balances |
| Statements | StudentStatement | stack | statement | student access | none | financial |
| Invoices | InvoiceDetail | stack | invoices | show auth | none | financial |
| Payments | PaymentDetail | stack | payments | show auth | none | no list |
| Wallet | ParentWallet* | Fees/More | parent-wallet | parent user | none | STK phone |
| Academic | ParentAcademic* | Academic tab | report cards | student access | none | results |
| Transport | Transport/LiveBus | stack | parent transport | student access | none | location |
| Diary | DiaryList/Chat | More/Home stack | diaries | parent/teacher/admin | none | shared thread |
| Homework | ChildHomework | stack | assignments | guardian | none | child scope |
| Notifications | NotificationsList | shared | notifications | self | none | deep links |
| Profile/Settings | MyProfile/Settings | More | profile-review, auth | self | none | PII |
| Teacher Home | TeacherHome | Home | classrooms | teacher | none | class scope |
| Teacher Attendance | MarkAttendance | tab | attendance/mark | teacher | none | class |
| Teacher More | TeacherMoreHub | More | me/* | staff | none | HR PII |
| Staff leave | MyLeave* | More | leave-requests | own/supervisor | none | HR |
| Admin dual | AccessDenied / Parent / Admin app | role utils | /user | role+parent | none | work not in Users |
| Co-curricular | CoCurricular* | Home/More | co-curricular | canAccessStudent | unit only | **staff notify bug** |

---

## 14. Architecture findings (important)

1. Dual-mode scaffolding exists but is incomplete for security boundaries (cache/push/deep links).  
2. Parent shell is mature; core school-life actions are unevenly exposed on Home vs More.  
3. School Fees exists as multiple screens without a single coherent parent “Fees” journey on Home.  
4. Messaging = Diary only; does not meet separate parent-teacher vs parent-admin channel rule.  
5. Activity-change staff notifications violate relationship-scoped notification rule (confirmed).  
6. Parent absence reporting must extend attendance carefully — no existing parent write path.  
7. Senior Teacher supervision helper may return all classrooms — dangerous for recipient logic.  
8. Mobile Users app has **no** automated feature tests today.

---

## 16. Phase 7B–7D implementation notes (2026-09-09)

### 7B Parent Home
- Redesigned `ParentHomeScreen` with child selector, school-life tiles (attendance, results, fees, transport, diary, homework, notifications, settings), coherent School Fees entry to existing Fees hub.
- Catalog: `packages/core/src/parent/homeActions.ts`; selected child: `selectedChild.ts` + storage.

### 7C Parent absence reporting
- Extends `Attendance` (excused absent) via `ParentAbsenceService` + `ApiParentAttendanceController`.
- Routes: `GET/POST /api/students/{student}/attendance-absence`, `GET /api/attendance/reason-codes`.
- Staff notify: class teacher (assignments), explicit senior classroom assignments, office roles — **not** all Senior Teachers.
- Mobile: `ReportAbsenceScreen` from Attendance → Report absence; history on `ChildAttendanceScreen`.
- Feature tests: `tests/Feature/Api/ParentAbsenceApiTest.php` (sqlite).

### 7D Home/Work mode
- `invalidateQueriesForAppMode` on switch; clears selected child when entering Work.
- Switcher labels **Home | Work**; remount key on `RoleBasedNavigator`.
- Admin/Director Work in Users app → `OpenAdminAppScreen` (Admin app deep link), not teacher UI.

### Still open (not in 7B–7D)
- Co-curricular `notifyAdminsOfRequest` role-wide fan-out (P0 from §9) — proposed Phase 7E.
- Diary channel separation; phone libphonenumber; notification preference enforcement.
