# STEP 2 — Feature Matrix (Staff App / Admin App / Shared)

> **Legend:** **A** = Staff App only · **B** = Admin App only · **C** = Shared by both (lives in `@erp/core` + shared component library).
>
> Guiding principle: **Staff App = consume & capture at the point of work** (teach, parent, ride). **Admin App = configure, approve, manage, analyze.** When the same domain has both a "do" and a "manage" surface, the capture/self-service slice goes to Staff (A) and the configuration/oversight slice goes to Admin (B).

---

## 2.1 Cross-cutting / platform features (mostly Shared)

| Feature | Code today | Decision | Reason |
|---------|-----------|----------|--------|
| Auth (password/Google/OTP/biometric) | `auth.api`, `AuthContext`, `biometrics`, `smsRetriever` | **C** | Identical login contract; only role-routing differs after login. |
| Session management & expiry | `session.ts` | **C** | Same security policy both apps. |
| Server-driven branding | `branding.api`, `mergePortalColors`, `ThemeContext` | **C** | Multi-tenant theming needed in both. |
| API client + interceptors | `api/client.ts` | **C** | Single Laravel contract; must not diverge. |
| Push registration | `device.api`, `usePushNotifications` | **C** | Same mechanism; payload routing differs per app. |
| Notification preferences | `notificationPreferences.api` | **C** | Same model; UI may differ. |
| In-app notifications list | `NotificationsScreen` | **C** | Both apps receive notifications. |
| OTA updates / APK fallback | `update.service` | **C** | Both apps ship via same pipeline. |
| Network/offline banner | `useNetworkStatus`, `OfflineBanner` | **C** | Same UX primitive. |
| Design system (Button/Input/Card/Avatar/badges/EmptyState/skeletons/headers/error boundary) | `components/common/*` | **C** | Shared component library is the whole point of the split. |
| Settings (theme, biometrics, notifications, check updates) | `SettingsScreen` | **C** | Common shell; admin gets extra admin-only settings (B-extensions). |

---

## 2.2 Students

| Feature | Code today | Decision | Reason |
|---------|-----------|----------|--------|
| View student list / detail | `StudentsListScreen`, `StudentDetailScreen` | **C** | Teachers/parents view (scoped); admins manage (full). Same screens, scoped by API. |
| Create / edit student | `AddStudentScreen`, `StudentFormScreen` | **B** | Enrollment/registry is an administrative function. |
| Bulk upload + template | `BulkUploadScreen` | **B** | Mass data ops are admin/registrar. |
| Archive / restore student | `students.api` | **B** | Lifecycle/registry control. |
| Family / guardian management | `FamilyManagementScreen` | **B** | Record management (admin/registrar). |
| Student categories | `students.api` | **B** | Configuration. |
| Student stats / attendance calendar | `students.api` | **C** | Teachers & parents read; admin reads + manages. |
| Profile-update self-service link | `students.api` | **A** | Parent-facing self-service. |

## 2.3 Academics — teaching

| Feature | Code today | Decision | Reason |
|---------|-----------|----------|--------|
| Mark attendance (class) | `MarkAttendanceScreen` | **A** | Class teachers capture attendance at the point of work. |
| Attendance records (view) | `AttendanceRecordsScreen` | **C** | Teachers view their classes; admin views school-wide. |
| Attendance analytics (school-wide) | placeholder | **B** | Aggregated oversight = admin. |
| Enter marks / matrix marks | `MarksEntryScreen`, `MarksMatrix*` | **A** | Subject teachers enter marks. |
| Exam setup / marks setup | `ExamMarksSetupScreen` | **B** | Exam config/scheduling = academic admin. |
| Exams list / detail (read) | `ExamsListScreen`, `ExamDetailScreen` | **C** | Teachers read to mark; admin manages. |
| Assignments (create/view) | `Assignments*`, `CreateAssignmentScreen` | **A** | Teacher classroom workflow. |
| Lesson plans (create/edit/submit) | `LessonPlan*` (editor, create-from-timetable) | **A** | Teacher authoring. |
| Lesson plan review queue / approve / reject | `LessonPlanReviewQueueScreen`, `LessonPlanRejectScreen` | **B**+**A(senior)** | Approval is supervisory. Senior teachers approve in Staff App; academic admins/HoD approve in Admin App. Shared review components. |
| Diary | `DiaryScreen` | **A** | Teacher daily log. |
| Timetable (view) | `TimetableScreen` | **C** | Teacher/student/parent view; admin builds (build UI is B/future). |
| Report card (view/download) | `ReportCardScreen` | **C** | Parents/students view; teachers contribute; admin generates/publishes. |
| Report card generate / bulk / publish | `documents.api` | **B** | Publishing results = admin/academic admin gate. |
| Academic reports (assigned forms) | `AcademicReports*` | **A** | Teacher-completed report forms. |
| Teacher requirements collection | `TeacherRequirements*` | **A** | Class teacher captures items received. |

## 2.4 Finance

| Feature | Code today | Decision | Reason |
|---------|-----------|----------|--------|
| Finance dashboard / summary | `FinanceHomeScreen`, `FinanceDashboard` | **B** | Whole-school financial oversight. |
| Invoices list / detail | `InvoicesListScreen`, `InvoiceDetailScreen` | **B** | Billing administration. |
| Fee structures | `FeeStructures*` | **B** | Configuration. |
| Record payment (cash/manual) | `RecordPaymentScreen` | **B** | Cashier/bursar/accountant function. |
| Payments list / detail | `PaymentsListScreen`, `PaymentDetailScreen` | **B** | Finance back-office. |
| Finance transactions (bank/C2B) confirm/reject/share | `PaymentsHubScreen`, `TransactionsListScreen`, `finance.api` | **B** | Reconciliation = finance/bursar. |
| Defaulters | `Defaulters`→InvoicesList | **B** | Collections management. |
| Student fee statement (view) | `StudentStatementScreen` | **C** | Parents/students view their own; admin/teacher view scoped. |
| Senior-teacher fee balances (read) | `FeeBalancesScreen`, `seniorTeacher.api` | **A** | Senior teachers track their stream balances (read-only). |
| M-Pesa STK prompt / payment link | `MpesaPromptModal`, `MpesaWaitingWebViewScreen` | **C** | Parents pay (A-facing); admin initiates (B-facing). Shared modal. |

## 2.5 HR & Staff

| Feature | Code today | Decision | Reason |
|---------|-----------|----------|--------|
| Staff directory / detail | `StaffDirectoryScreen`, `StaffDetailScreen` | **B** | HR record visibility = admin/management. |
| Create / edit staff | `StaffEditScreen` | **B** | HR administration. |
| Payroll records (school) / generate / process / payslip | `PayrollRecordsScreen`, `hr.api` | **B** | Payroll processing = finance/HR admin. |
| My salary / payslip (self) | `MySalaryScreen` | **A** | Staff self-service payslip. |
| My profile (self) / edit own | `MyProfileScreen`, `StaffEditScreen(self)` | **A** | Personal profile. |
| Leave management (approve/reject) | `LeaveManagementScreen` | **B** | Approval is managerial. |
| Apply for leave (self) | `ApplyLeaveScreen` | **A** | Staff self-service request. |
| Salary advances request | `hr.api` | **A** | Self-service request. |
| Salary advances approve/reject | `hr.api` | **B** | Approval. |
| Staff clock in/out (GPS geofence, self) | `TeacherClockScreen`, `staffClock.api` | **A** | Each staff member clocks themselves. |
| Team clock history / roster | `StaffClockTeamScreen` | **B**+**A(supervisor)** | Oversight; supervisors get read in Staff App, admins manage in Admin App. |
| Geofence config | `staffClock.api`, Settings | **B** | Configuration. |
| Staff attendance records / manual mark | `hr.api` | **B** | HR administration. |

## 2.6 Transport

| Feature | Code today | Decision | Reason |
|---------|-----------|----------|--------|
| Driver home / active trip | `DriverHomeScreen`, `DriverActiveTripScreen` | **A** | Driver is operational staff. |
| Routes list / route detail (view) | `RoutesListScreen`, `RouteDetailScreen` | **C** | Driver/teacher view; admin manages. |
| Driver trips (today, roster) | `transport.api /driver/trips` | **A** | Driver operations. |
| Teacher transport (class roster) | `TeacherTransportListScreen`, `teacherTransport.api` | **A** | Class teacher verifies pickups. |
| Student pickup verification / mark collected | `teacherTransport.api` pickups | **A** | Point-of-handover capture. |
| Temporary reassign vehicle/trip | `teacherTransport.api` | **A** | Operational (teacher/driver). |
| Route / vehicle / drop-point CRUD | `transport.api` | **B** | Fleet & route configuration. |
| Student–route assignment management | `transport.api` | **B** | Admin/transport-office configuration. |
| Transport summary | `transport.api` | **B** | Oversight dashboard. |

## 2.7 Communication

| Feature | Code today | Decision | Reason |
|---------|-----------|----------|--------|
| Announcements (read) | `AnnouncementsScreen` | **C** | Everyone reads. |
| Announcements create / publish / delete | `communication.api` | **B** | Broadcasting authority = admin. |
| Send SMS / email broadcast | `SendSMSScreen`, `communication.api` | **B** | Mass messaging = admin/secretary. |
| Message templates CRUD | `communication.api` | **B** | Configuration. |
| Message delivery status | `communication.api` | **B** | Admin monitoring. |
| Notifications inbox | `NotificationsScreen` | **C** | Both apps. |
| Feedback form (submit) | `FeedbackScreen`, `feedback.api` | **A** | Staff/parents submit. |

## 2.8 Library

| Feature | Code today | Decision | Reason |
|---------|-----------|----------|--------|
| Library books (browse) | `LibraryBooksScreen` | **C** | Students/parents browse; librarian manages. |
| Book CRUD | `library.api` | **B** | Catalog management (librarian = Admin App). |
| Cards issue/suspend/activate | `library.api` | **B** | Membership administration. |
| Borrow / return / renew / mark-lost | `library.api` | **B** | Circulation desk = Admin App. |
| Library summary | `library.api` | **B** | Oversight. |
| My borrowings (self) | *(missing today)* | **A** | Student self-view — gap to build. |

## 2.9 Inventory / Store / POS / Hostel (admin-heavy back office)

| Feature | Code today | Decision | Reason |
|---------|-----------|----------|--------|
| Inventory items / adjustments | `inventory.api` | **B** | Store keeper management. |
| Requisitions create | `inventory.api` | **A** | Any staff can raise a requisition. |
| Requisitions approve/reject/fulfill | `inventory.api` | **B** | Approval & fulfillment. |
| Student requirements template | `inventory.api` | **B** | Configuration (collection is A in §2.3). |
| POS products / orders / uniforms | `pos.api` | **B** | Sales back-office. |
| Public shop (token) | `pos.api` | **C** | Parent-facing checkout could be A; admin manages catalog (B). |
| Hostel / rooms / allocations | `hostel.api` | **B** | Boarding administration. |
| Documents / templates / certificates / export-import | `documents.api` | **B** | Records administration (downloads shared where user-owned). |

---

## 2.10 Summary counts

| Bucket | Approx feature areas |
|--------|----------------------|
| **A — Staff only** | ~22 (teaching capture, self-service HR, driver ops, pickup verification, requisition raise, parent self-service) |
| **B — Admin only** | ~45 (finance back-office, HR admin, registry, exam/result publishing, fleet config, broadcasting, library/inventory/POS/hostel management, analytics) |
| **C — Shared** | ~18 (auth, branding, API layer, design system, notifications, settings shell, scoped student/statement/report-card/timetable/route views, M-Pesa modal) |

## 2.11 Decision rules used (so future features can be classified consistently)

1. **Capture vs configure** → capture = A, configure = B.
2. **Self vs others** → "my/own" data = A; "anyone's/school-wide" = B.
3. **Approve vs request** → request = A; approve = B (exception: senior-teacher supervisory reads live in Staff App, but the authoritative management surface is Admin App).
4. **Read scoped vs manage** → a screen that only *reads* the same data for a non-admin (scoped by API) can be **C** with shared components; the *write/manage* surface is B.
5. **Audience** → parent/student/driver-facing = A; finance/HR/registrar/management-facing = B.
