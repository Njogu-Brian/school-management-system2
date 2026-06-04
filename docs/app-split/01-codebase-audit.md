# STEP 1 — Codebase Audit & Complete Inventory

> Source of truth: `mobile-app/` (Expo + React Native). All paths relative to `mobile-app/src` unless noted.

## 0. Tech stack & platform facts

| Area | Detail |
|------|--------|
| Runtime | Expo SDK `~54.0`, React Native `0.81.5`, React `19.1.0`, TypeScript `5.3` |
| Navigation | `@react-navigation/native` v6 — bottom-tabs, stack, drawer (drawer installed, not used) |
| HTTP | `axios` via singleton `ApiClient` (`api/client.ts`) |
| Forms | `react-hook-form` |
| Charts | `react-native-chart-kit`, `react-native-svg` |
| Storage | `expo-secure-store` (token), `@react-native-async-storage/async-storage` (user, prefs) |
| Auth extras | `expo-local-authentication` (biometrics), `react-native-sms-retriever` (OTP autofill), `expo-auth-session` + Google OAuth |
| Notifications | `expo-notifications` (register-only), `expo-device` |
| Updates | `expo-updates` (OTA) + APK download fallback |
| Location | `expo-location` (foreground only — staff clock geofence) |
| Media | `expo-image-picker`, `expo-document-picker`, `react-native-pdf`, `react-native-webview` |
| State management | **React Context + local state only.** No Redux/Zustand/MobX/Recoil/React Query. |
| EAS project | `d8b53a3a-3093-407c-b552-de66fc1cc8bb` |

**Provider tree (`App.tsx`):** `ThemeProvider → SafeAreaProvider → AppErrorBoundary → AuthProvider → NotificationPreferencesProvider → AppNavigator`.

---

## 1. User roles & permissions

### 1.1 Roles defined (`constants/roles.ts`)

`UserRole` enum (14 values, normalized to backend strings):

`super_admin`, `admin`, `secretary`, `academic_admin`, `teacher`, `senior_teacher`, `supervisor`, `accountant`, `finance`, `parent`, `guardian`, `student`, `driver`, `transport`.

> Roles in the brief **not present** in the mobile enum: Principal, Deputy Principal, Head Teacher, Bursar, Receptionist, Librarian, Nurse, Store Keeper, Security Personnel. These are either backend-only, mapped onto existing roles, or future work.

### 1.2 Role normalization (`utils/roleUtils.ts`)
- `normalizeRole()` maps human/API strings ("Senior Teacher", "super_admin", …) → `UserRole`; **defaults unknown roles to `TEACHER`** (a risk to flag).
- Helper predicates: `isTeacherRole`, `isSeniorTeacherRole`, `canViewStudentFeeAmounts`, `canViewTeamClockHistory`, `canViewPayrollRecords`, `canAccessLeaveManagement`.

### 1.3 Permissions (`constants/roles.ts → PERMISSIONS`)
A static permission constant map exists but is **declarative only** — enforcement in the app is by **role checks in navigators/screens**, not a permission engine:

`view/create/edit/delete_students`, `mark/view/edit_attendance`, `view/create_invoices`, `view/create_payments`, `view/create_exams`, `enter_marks`, `publish_results`, `view/manage_staff`, `approve_leave`, `process_payroll`, `view_logs`, `manage_settings`, `backup_restore`.

**Finding:** Authorization is role-string driven on the client; the real enforcement boundary is the Laravel API. The client gates *visibility*, not *access*.

---

## 2. Role → navigation shell mapping (`navigation/RoleBasedNavigator.tsx`)

| Role group | Shell | Type |
|------------|-------|------|
| `super_admin`, `admin`, `secretary`, `accountant`, `finance` | Admin bottom tabs | 6 tabs |
| `academic_admin` | `AcademicAdminNavigator` | 4 tabs (read-mostly academics) |
| `teacher`, `senior_teacher`, `supervisor` | `TeacherNavigator` | stack + 4 tabs |
| `parent`, `guardian` | `ParentTabNavigator` | 4 tabs |
| `student` | `StudentTabNavigator` | 4 tabs |
| `driver`, `transport` | `DriverTabNavigator` | 3 tabs |
| unmatched | fallback single `Dashboard` (AdminDashboard) | 1 tab |

This is the **fault line for the app split**: the admin group + academic-admin shells → Admin App; teacher/parent/student/driver shells → Staff App.

---

## 3. Navigation routes (per shell)

### 3.1 Auth (`AuthNavigator`)
`Login` → `LoginScreen` · `ForgotPassword` → `ForgotPasswordScreen` · `OTPVerification` → `OTPVerificationScreen` · `ResetPassword` → `ResetPasswordScreen`

### 3.2 Admin / Super Admin / Secretary / Accountant / Finance — bottom tabs
`Dashboard`(AdminDashboard) · `Students`(StudentsNavigator) · `Payments`(PaymentsNavigator) · `Attendance`(AttendanceNavigator) · `Finance`(FinanceNavigator) · `More`(MoreNavigator)

- **StudentsNavigator:** `StudentsList`, `StudentDetail`, `AddStudent`, `EditStudent`, `RecordPayment`, `StudentStatement`, `ReportCard`
- **PaymentsNavigator:** `PaymentsHub`, `PaymentDetail`, `TransactionDetail`
- **AttendanceNavigator:** `MarkAttendance`, `AttendanceRecords`(placeholder), `AttendanceAnalytics`(placeholder)
- **FinanceNavigator:** `FinanceHome`, `InvoicesList`, `InvoiceDetail`, `PaymentsList`, `PaymentDetail`, `RecordPayment`, `StudentStatement`, `FeeStructures`, `Receipts`, `Defaulters`, `MpesaWaitingWeb`
- **MoreNavigator:** `MoreMenu`, `AcademicReports`, `AcademicReportFill`, `Feedback`, `StaffDirectory`, `StaffDetail`, `StaffEdit`, `LeaveManagement`, `ApplyLeave`, `PayrollRecords`, `RoutesList`, `RouteDetail`, `LibraryBooks`, `Announcements`, `Notifications`, `Settings`, `TeacherClock`, `StaffClockTeam`, `ExamsList`, `ExamMarksSetup`, `MarksEntry`, `TeacherRequirements`, `TeacherRequirementDetail`

### 3.3 Academic Admin (`AcademicAdminNavigator`) — bottom tabs
`Dashboard`(AdminDashboard) · `Students`(AcademicAdminStudentsNavigator) · `Academics`(AcademicAdminAcademicsNavigator) · `More`(MoreNavigator)
- **Students stack:** `StudentsList`, `StudentDetail`, `ReportCard`
- **Academics stack:** `ExamsList`, `ExamDetail`, `Timetable`, `ReportCard`, `Assignments`, `AssignmentDetail`, `ViewAssignment`, `LessonPlanReviewQueue`, `LessonPlanDetail`, `LessonPlanReject`
- Staff Directory hidden in More.

### 3.4 Teacher / Senior Teacher / Supervisor (`TeacherNavigator`) — stack + tabs
- **Tabs (`Main`):** `Home`(TeacherDashboard) · `Classes`(StudentsListScreen) · `Attendance`(MarkAttendanceScreen) · `More`(TeacherMoreHubScreen)
- **Stack routes (40+):** `MyClasses`, `StudentsList`, `StudentDetail`, `RecordPayment`, `StudentStatement`, `MarkAttendance`, `TeacherClock`, `StaffClockTeam`, `AttendanceRecords`, `Timetable`, `Assignments`, `AssignmentDetail`, `CreateAssignment`, `LessonPlans`, `LessonPlanDetail`, `LessonPlanCreateFromTimetable`, `LessonPlanEditor`, `LessonPlanReviewQueue`, `LessonPlanReject`, `MarksEntry`, `MarksMatrixSetup`, `MarksMatrixEntry`, `ExamsList`, `ExamMarksSetup`, `ExamDetail`, `ReportCard`, `Transport`/`TeacherTransport`, `RouteDetail`, `Diary`, `MyProfile`, `StaffEdit`, `MySalary`, `Leave`, `ApplyLeave`, `SupervisedClassrooms`*, `SupervisedStaff`*, `FeeBalances`*, `TeacherRequirements`, `TeacherRequirementDetail`, `Notifications`, `Settings`
- \* Senior-teacher/supervisor-only (UI-gated via `isSeniorTeacherRole`).
- **TeacherMoreHub** gates: `StaffClockTeam` (team-clock roles), lesson-plan review + supervised items (senior only).

### 3.5 Parent / Guardian (`ParentTabNavigator`) — bottom tabs
`ParentHomeTab`(ParentDashboardScreen) · `ParentChildrenTab`(ChildrenList→StudentsListScreen, StudentDetail, StudentStatement, ReportCard) · `ParentPaymentsTab`(ParentPaymentsMain, StudentDetail, StudentStatement, ReportCard) · `ParentMoreTab`(MoreMenu, Announcements, Notifications)
- **Gap:** `MoreScreen` (shared with admin) renders many menu items whose target routes are **not registered** in the Parent stack → dead links for parents.

### 3.6 Student (`StudentTabNavigator`) — bottom tabs
`StudentHomeTab`(StudentHomeScreen) · `StudentHomeworkTab`(StudentHomeworkScreen) · `StudentResultsTab`(StudentResultsScreen) · `StudentMoreTab`(MoreMenu, Announcements, Notifications). Same dead-link gap as Parent.

### 3.7 Driver / Transport (`DriverTabNavigator`) — bottom tabs
`DriverHomeTab`(DriverHomeMain, ActiveTrip, RouteDetail) · `DriverRoutesTab`(RoutesList, RouteDetail) · `DriverAccountTab`(DriverSettings→SettingsScreen)

### 3.8 Orphan navigators (defined, never mounted)
`AcademicsNavigator`, `HRNavigator`, `TransportNavigator`, `LibraryNavigator`, `CommunicationNavigator` in `ModuleNavigators.tsx` — contain several `PlaceholderFeatureScreen` routes (`CreateExam`, `AddStaff`, `AddRoute`, `Vehicles`, `Trips`, `AddBook`, `Borrowings`, `LibraryCards`, `Messages`, etc.). These represent **unfinished modules**.

---

## 4. Screen inventory (84 screens, grouped by module)

| Module (folder) | Screens |
|-----------------|---------|
| **Auth** | LoginScreen, ForgotPasswordScreen, OTPVerificationScreen, ResetPasswordScreen |
| **Dashboard** | AdminDashboard, TeacherDashboard, StudentDashboard, FinanceDashboard, TeacherMoreHubScreen |
| **Students** | StudentsListScreen, StudentDetailScreen, AddStudentScreen, StudentFormScreen (Edit), StudentRecordsScreen, FamilyManagementScreen, BulkUploadScreen |
| **Academics** | ExamsListScreen, ExamDetailScreen, ExamMarksSetupScreen, MarksEntryScreen, MarksMatrixSetupScreen, MarksMatrixEntryScreen, AssignmentsScreen, AssignmentDetailScreen, CreateAssignmentScreen, LessonPlansScreen, LessonPlanDetailScreen, LessonPlanEditorScreen, LessonPlanCreateFromTimetableScreen, DiaryScreen, TimetableScreen, ReportCardScreen |
| **SeniorTeacher** | SupervisedClassroomsScreen, SupervisedStaffScreen, FeeBalancesScreen, LessonPlanReviewQueueScreen, LessonPlanRejectScreen |
| **Attendance** | MarkAttendanceScreen, AttendanceRecordsScreen, TeacherClockScreen, StaffClockTeamScreen |
| **Finance** | FinanceHomeScreen, InvoicesListScreen, InvoiceDetailScreen, PaymentsListScreen, PaymentDetailScreen, RecordPaymentScreen, FeeStructuresScreen, FeeStructuresListScreen, StudentStatementScreen, MpesaWaitingWebViewScreen |
| **Payments** | PaymentsHubScreen, TransactionsListScreen, TransactionDetailScreen |
| **HR** | StaffDirectoryScreen, StaffDetailScreen, StaffEditScreen, PayrollRecordsScreen, MySalaryScreen, MyProfileScreen, LeaveManagementScreen, ApplyLeaveScreen |
| **Transport** | RoutesListScreen, RouteDetailScreen, DriverHomeScreen, DriverActiveTripScreen, TeacherTransportListScreen |
| **Library** | LibraryBooksScreen |
| **Communication** | AnnouncementsScreen, NotificationsScreen, SendSMSScreen |
| **Reports** | AcademicReportsListScreen, AcademicReportFillScreen, FeedbackScreen |
| **Requirements** | TeacherRequirementsScreen, TeacherRequirementDetailScreen |
| **Student (self)** | StudentHomeScreen, StudentHomeworkScreen, StudentResultsScreen |
| **Parent** | ParentDashboardScreen, ParentPaymentsScreen |
| **Settings/More/common** | SettingsScreen, MoreScreen, PlaceholderFeatureScreen |

---

## 5. API endpoint inventory (23 modules, ~180 unique paths)

> Full per-function table lives in the audit appendix; summarized here by domain. All relative to `API_BASE_URL`. `upload` = POST multipart.

| Module | Base resource(s) | Notable endpoints |
|--------|------------------|-------------------|
| `auth.api` | `/login`, `/user`, `/password/*` | login, google login, OTP login (`/login/otp/request`,`/verify`), logout, getProfile, password reset/change |
| `academics.api` | `/exams`, `/marks`, `/lesson-plans`, `/assignments`, `/report-cards`, `/timetables` | batch marks (`/exam-marks/batch`, `/exam-marks/matrix/batch`), lesson-plan submit/approve/reject, review-queue |
| `attendance.api` | `/attendance` | mark, class, student stats, analytics, update, delete |
| `finance.api` | `/invoices`, `/payments`, `/fee-structures`, `/finance/*`, `/students/{id}/statement` | createPayment, finance transactions (bank/c2b) confirm/reject/share, M-Pesa prompt + payment-link, finance summary |
| `students.api` | `/students`, `/classes`, `/student-categories` | CRUD (+multipart), bulk-upload + template, stats, attendance-calendar, archive/restore, profile-update-link |
| `hr.api` | `/staff`, `/leave-requests`, `/leave-types`, `/staff-attendance`, `/payroll-records`, `/payrolls`, `/salary-advances` | staff CRUD+photo, leave apply/approve/reject, payroll generate/process/payslip, salary advances, HR summary |
| `communication.api` | `/announcements`, `/messages`, `/message-templates`, `/notifications`, `/push-notifications` | announcement CRUD+publish, sendMessage, delivery-status, templates, notifications read/mark-all, legacy push register |
| `dashboard.api` | `/dashboard/stats` | single role-aware stats/charts endpoint |
| `transport.api` | `/vehicles`, `/routes`, `/drop-points`, `/trips`, `/driver/trips`, `/student-route-assignments` | vehicle/route CRUD, drop points, driver trips, cancel trip, student-route assignment, transport summary |
| `teacherTransport.api` | `/teacher/transport/*` | class roster, pickups (mark/cancel), temporary reassign |
| `library.api` | `/library/books`, `/library/cards`, `/library/borrowings` | book CRUD, card issue/suspend/activate, borrow/return/renew/mark-lost, summary |
| `inventory.api` | `/inventory/items`, `/inventory/adjustments`, `/requisitions`, `/student-requirements` | item CRUD, adjustments, requisition approve/reject/fulfill, student requirements, summary |
| `pos.api` | `/pos/products`, `/pos/orders`, `/pos/uniforms`, `/public/shop/*` | product/variant CRUD, orders + status/cancel, uniforms, public token shop, POS summary |
| `hostel.api` | `/hostels`, `/hostel-rooms`, `/room-allocations` | hostel/room CRUD, allocate/deallocate, summary |
| `documents.api` | `/documents`, `/document-templates`, `/report-cards`, `/certificates`, `/export`, `/import` | upload/download, generate from template, report-card generate/bulk/publish, certificates, Excel/PDF export & import |
| `branding.api` | `/app-branding` | public school name/logo/colors/apk url |
| `device.api` | `/device-tokens` | register / revoke push token |
| `feedback.api` | `/feedback/*` | template, submit, file answer upload |
| `notificationPreferences.api` | `/notification-preferences` | get / update prefs |
| `seniorTeacher.api` | `/senior-teacher/*` | supervised classrooms/staff/students, fee-balances |
| `staffClock.api` | `/staff-attendance/*` | geofence config, today/history, clock-roster, clock-in/out (GPS) |
| `teacherRequirements.api` | `/teacher/requirements/*` | students, templates, collect |
| `academicReports.api` | `/academic-reports/*` | assigned, template, submit, file answer |

**Audit notes:**
- **Duplicate push registration** paths (`/push-notifications/register` legacy + `/device-tokens`).
- **`/report-cards`** consumed by both `academics.api` and `documents.api`.
- **`/staff-attendance`** is split between HR (list/mark) and geofence clock (`staffClock.api`).
- **No PATCH** usage anywhere.

---

## 6. Shared services, components & infrastructure

### 6.1 Components (`components/**`, 20 files)
- **common/ (14):** `Button`, `Input`, `Card`, `ScreenContainer`, `Avatar`, `StatusBadge`, `FeeStatusBadge`, `EmptyState`, `ListLoadingSkeleton`, `LoadErrorBanner`, `AppScreenHeader`, `GlobalAppHeader`, `OfflineBanner`, `AppErrorBoundary`.
- **dashboard/ (4):** `DashboardHero`, `DashboardCharts` (line/bar), `DashboardMenuGrid`, barrel `index.ts`.
- **root (2):** `Icon` (MaterialIcons re-export), `MpesaPromptModal` (STK + status polling).

### 6.2 Contexts / state
- `AuthContext` — auth state, login/logout, session restore, 401 → global logout, foreground + 60s session-expiry checks.
- `ThemeContext` — light/dark/auto + portal branding merge (mode **not persisted**).
- `NotificationPreferencesContext` — local-first prefs hydrated from AsyncStorage + remote merge.
- `AdminBrandedContext` — **defined but never wired** into the tree.

### 6.3 Hooks
- `useNetworkStatus` (NetInfo online/offline), `usePushNotifications(enabled)`, `useRootScreenBackground` (**dead** until admin shell wires `AdminBrandedProvider`).

### 6.4 Auth / session utilities
- `storage.ts` — token in **SecureStore** (`school_erp_token`, device-only), user/prefs in AsyncStorage, one-time legacy token migration.
- `session.ts` — 7-day max age; 30-min idle (no remember) / 7-day idle (remember); `touchSession` also fires on every successful API response.
- `biometrics.ts` — `expo-local-authentication`, token bundle in SecureStore, lock after 5 failures.
- `smsRetriever.ts` — Android OTP autofill (no-op in Expo Go).
- `env.ts` — `API_BASE_URL`, `WEB_BASE_URL`, Google OAuth IDs from `expo-constants`/`EXPO_PUBLIC_*`.
- `mpesaStatus.ts` — trusted-origin M-Pesa status polling.

---

## 7. Authentication flows

1. **Password login** — `POST /login` → `{token,user}` → SecureStore token + AsyncStorage user + `startSession`.
2. **Google login** — `expo-auth-session` → ID token → `POST /login/google`.
3. **OTP login** — `POST /login/otp/request` → `POST /login/otp/verify`; Android SMS autofill via `react-native-sms-retriever`.
4. **Biometric login** — re-uses stored token bundle behind `expo-local-authentication`.
5. **Password reset** — email / SMS-link / OTP variants (`/password/*`).
6. **Session restore on launch** — token present → `GET /user`; 401 clears, network/5xx falls back to cached user.
7. **Global 401 handling** — `apiClient.setOnUnauthorized` → `logout()`.
8. **Session expiry** — idle/max-age timers + app-foreground re-check.

---

## 8. Notifications & background tasks

- **Push (register-only):** `usePushNotifications` registers Expo push token (`getExpoPushTokenAsync({projectId})`) → `POST /device-tokens`; revoke on disable. **No `addNotificationReceivedListener` / response→navigation routing.** Skipped in Expo Go.
- **In-app notifications:** `GET /notifications` list + read/mark-all in `NotificationsScreen`.
- **Preferences:** `push_enabled`, `email_enabled`, `sms_enabled`, `attendance_alerts`, `fee_reminders`, `announcements`.
- **Background tasks:** **None.** No `expo-task-manager` / `expo-background-fetch`. Location is foreground-only (staff clock geofence).
- **OTA updates:** `expo-updates` (`checkForUpdateAsync`/`fetchUpdateAsync`/`reloadAsync`), with **APK download fallback** from branding `android_apk_download_url`. Triggered on launch + Settings.

---

## 9. State management summary

- **No global server-state cache.** Each screen fetches via `apiClient` in `useEffect` and holds results in local `useState`; loading/error tracked per screen.
- **Cross-cutting state** only in 3 active contexts (Auth, Theme, NotificationPreferences).
- **Implication for split:** introducing **TanStack Query** (or RTK Query) in the shared core is the single highest-leverage architectural upgrade — see [`04-architecture.md`](./04-architecture.md).

---

## 10. Key audit findings (carry-forward)

| # | Finding | Impact on split |
|---|---------|-----------------|
| F1 | Six independent role shells already exist | Split boundary is clean and obvious. |
| F2 | Parent/Student `MoreScreen` reuses admin menu → dead links | Must build app-specific More menus. |
| F3 | `normalizeRole` defaults unknown → `TEACHER` | Security smell; tighten to explicit deny. |
| F4 | No offline sync despite README claims | Define a real offline strategy. |
| F5 | Push is register-only | Add notification routing/deep links. |
| F6 | No analytics | Add analytics layer in both apps. |
| F7 | Orphan navigators + placeholder screens | Decide build vs delete per app. |
| F8 | `AdminBrandedProvider`/`useRootScreenBackground` unwired | Wire in Admin App shell. |
| F9 | Server-driven branding already exists | Reuse for multi-tenant in both apps. |
| F10 | Client authz is role-string only | Real enforcement must stay server-side. |
