# STEP 6 — High-Fidelity UI Specifications (Stitch / Figma ready)

> Each screen spec lists: **Purpose · Components · User Actions · API Calls · Empty State · Loading State · Error State.**
> Components reference the shared library (`@erp/ui`) from [`04-architecture.md`](./04-architecture.md). API paths reference [`01-codebase-audit.md`](./01-codebase-audit.md).

---

## 6.0 Global conventions (apply to every screen)

**Layout grid:** 8pt spacing system; screen padding 16; cards radius 16, elevation/shadow per `SHADOWS`. Safe-area aware via `ScreenContainer`. Bottom-tab height + insets respected.

**Theme tokens:** colors resolved from `ThemeContext` (per-school branding overrides `COLORS`). Support light/dark. Min contrast AA. Min tap target 44×44.

**Standard state pattern (reused everywhere — do not redraw per screen):**
- **Loading:** `ListLoadingSkeleton` (3–6 shimmer rows) for lists; skeleton tiles for dashboards; inline spinner inside buttons for submit.
- **Empty:** `EmptyState` — illustration + title + subtitle + (optional) primary CTA. Copy is screen-specific (given per screen).
- **Error:** `LoadErrorBanner` — message + "Retry"; offline variant shows "You're offline — showing cached data" when cache exists; otherwise full-screen error with retry.
- **Offline:** global `OfflineBanner` at top; queued writes show a "pending sync" chip.
- **Toast/confirm:** success toast on mutation; destructive actions use `ConfirmDialog`.

**Header:** `AppScreenHeader` (title, back, optional right action). Dashboards/home use `GlobalAppHeader` (gradient, avatar, notifications bell with unread badge).

---

# PART A — STAFF APP SCREENS

## A1. Auth (shared `@erp/features`/core)

### A1.1 Login
- **Purpose:** Authenticate staff/parent/student/driver; route to role shell.
- **Components:** Branded logo (from `branding`), `Input`(email/phone), `Input`(password, secure), "Remember me" switch, primary `Button`(Login), Google button, "Login with OTP" link, "Forgot password" link, biometric icon (if enrolled).
- **User Actions:** submit credentials; Google OAuth; switch to OTP; biometric unlock; toggle remember.
- **API Calls:** `POST /login`; `POST /login/google`; `GET /app-branding` (on mount); `GET /user` (post-login profile).
- **Empty State:** n/a (form).
- **Loading State:** button spinner; full-screen splash while restoring session.
- **Error State:** inline field errors (422), banner for invalid credentials/network; biometric failure count → lock after 5.

### A1.2 OTP Verification
- **Purpose:** Verify login/reset OTP.
- **Components:** 6-box OTP input (SMS autofill on Android), resend timer, verify `Button`.
- **Actions:** enter/autofill OTP; resend; verify.
- **API:** `POST /login/otp/verify` or `POST /password/verify-otp`; resend → `/login/otp/request`.
- **Empty/Loading/Error:** disabled verify until 6 digits; spinner on verify; "code invalid/expired" banner + resend.

### A1.3 Forgot / Reset Password
- **Purpose:** Recover access via email/SMS-link/OTP.
- **Components:** method selector, identifier `Input`, submit; reset form (new + confirm password).
- **API:** `/password/email` · `/password/sms-link` · `/password/otp` · `/password/reset`.
- **States:** success confirmation screen; error banner; loading button.

### A1.4 App-mismatch guard (NEW)
- **Purpose:** If an admin-only role logs into Staff App, block and redirect.
- **Components:** message card + "Open Admin App" `Button` (deep link / store link).
- **Actions:** open/install Admin App; logout.

## A2. Teacher

### A2.1 Teacher Home / Dashboard
- **Purpose:** Daily teacher cockpit.
- **Components:** `GlobalAppHeader` (avatar, bell), clock-in status chip, Next-class card, today stats tiles (lessons, attendance pending, marks due), `MenuGrid` quick actions, pending lesson-plans list, announcements strip, senior-only quick actions.
- **Actions:** clock in/out; tap quick action → screen; open announcement; pull-to-refresh.
- **API:** `GET /dashboard/stats?term_id`; `GET /staff-attendance/me/today`; `GET /lesson-plans?status=draft`; `GET /announcements`.
- **Empty:** "No classes scheduled today" in next-class card; "You're all caught up" for pending.
- **Loading:** skeleton tiles + skeleton list.
- **Error:** per-widget inline error with retry; dashboard never blanks fully (cached).

### A2.2 Classes / Students list (scoped)
- **Purpose:** Browse the teacher's classes/students.
- **Components:** search bar, class/stream filter chips, student `ListItem` (avatar, name, adm no, status badge), section headers.
- **Actions:** search; filter; tap student → detail; pull-to-refresh; paginate.
- **API:** `GET /students?class_id&stream_id&search&page`; `GET /classes`.
- **Empty:** "No students in this class yet."
- **Loading:** skeleton rows.
- **Error:** retry banner; offline shows cached.

### A2.3 Student Detail (shared `students-view`)
- **Purpose:** 360° student view (scoped by role).
- **Components:** profile header (avatar, name, class, adm), tabbed sections (Overview, Attendance, Results, Fees, Transport), stat tiles, action buttons (record payment*/statement/report card — *admin/finance only).
- **Actions:** switch tabs; view statement; view report card; (teacher) view attendance calendar.
- **API:** `GET /students/{id}`; `GET /students/{id}/stats`; `GET /students/{id}/attendance-calendar`; `GET /students/{id}/statement` (scoped).
- **Empty:** per-tab ("No results published yet").
- **Loading:** header skeleton + tab skeletons.
- **Error:** retry per tab.

### A2.4 Mark Attendance
- **Purpose:** Capture class attendance for a date (offline-tolerant).
- **Components:** date picker, class/stream selector, "Mark all present" button, student rows with Present/Absent/Late toggles, absent reason field, summary footer (P/A/L counts), Save `Button`.
- **Actions:** select date/class; bulk present; toggle per student; add reason; save (queues if offline).
- **API:** `GET /attendance/class?date&class_id&stream_id`; `POST /attendance/mark`.
- **Empty:** "Select a class to begin."
- **Loading:** roster skeleton.
- **Error:** save failure → keep edits, show retry; offline → "Saved locally, will sync" chip.

### A2.5 Marks Entry / Marks Matrix
- **Purpose:** Enter exam marks (single or grid).
- **Components:** exam/class/subject selector, matrix grid (students × fields) with numeric cells + validation (max marks), autosave indicator, submit `Button`, totals/average row.
- **Actions:** select context; type marks (cell autosave); validate; submit batch.
- **API:** `GET /exams/{id}/marking-options`; `GET /marks/matrix/context`; `GET /marks/matrix`; `POST /exam-marks/matrix/batch` (or `/exam-marks/batch`).
- **Empty:** "No students/subjects for this selection."
- **Loading:** grid skeleton.
- **Error:** cell-level validation errors; batch save error banner + retry; offline queue.

### A2.6 Assignments (list / create / detail)
- **Purpose:** Manage classroom assignments.
- **Components:** list (`ListItem`: title, class, due date, status); create form (title, class/subject, due date, description, attachment picker); detail (description, submissions count, attachments).
- **Actions:** create; attach file; view; (NEW) review submissions.
- **API:** `GET /assignments`; `POST /assignments`; `GET /assignments/{id}`.
- **Empty:** "No assignments yet — create one."
- **Loading:** list skeleton / form disabled.
- **Error:** create 422 field errors; list retry.

### A2.7 Lesson Plans (list / editor / create-from-timetable / detail / review)
- **Purpose:** Author, submit, and (senior) review lesson plans.
- **Components:** list with status badges (draft/submitted/approved/rejected); editor form (objectives, content, resources, date, class/subject); create-from-timetable picker; detail with review history; review actions (approve / reject + reason).
- **Actions:** create/edit; save draft (offline); submit; (senior) approve/reject.
- **API:** `GET /lesson-plans`; `GET /lesson-plans/{id}`; `POST /lesson-plans`; `PUT /lesson-plans/{id}`; `POST /lesson-plans/{id}/submit`; `GET /lesson-plans/review-queue`; `POST /lesson-plans/{id}/approve|reject`.
- **Empty:** "No lesson plans" / "Nothing to review."
- **Loading:** list + editor skeleton.
- **Error:** save/submit error retry; offline draft queue.

### A2.8 Diary / Timetable / Report Card
- **Diary:** Purpose: daily class log. Components: date-grouped entries, add entry. API: (diary endpoints). States: empty "No entries", skeleton, retry.
- **Timetable:** Purpose: weekly schedule. Components: day tabs, period cards. API: `GET /timetables/teacher/{id}?term_id`. Empty: "No timetable set." 
- **Report Card:** Purpose: view/print. Components: term selector, grades table, comments, download PDF. API: `GET /report-cards/{id}`, `GET /report-cards/{id}/download`. Empty: "Not published yet."

### A2.9 Staff self-service (Clock, Profile, Salary, Leave, Requirements)
- **Clock in/out:** geofence check via GPS; components: map preview, in/out button, today's record, history list. API: `GET /staff-attendance/geofence`, `/me/today`, `/me/history`, `POST /clock-in|clock-out`. Error: "Outside school radius."
- **My Profile / Edit:** components: profile card, edit form, photo upload. API: `GET /staff/{id}`, `PUT /staff/{id}`, `POST /staff/{id}/photo`.
- **My Salary:** payslip list + breakdown + download. API: `GET /payroll-records`, `GET /payrolls/{id}/payslip`. Empty: "No payslips yet."
- **Apply Leave:** form (type, dates, reason, attachment), balance display. API: `GET /leave-types`, `POST /leave-requests`; status list `GET /leave-requests`.
- **Requirements collection:** student list → templates → collect quantity. API: `/teacher/requirements/*`.

### A2.10 Senior-teacher (Review queue, Supervised classes/staff, Fee balances, Team clock)
- **Components:** queues/lists with counts, drill-downs, read-only fee-balance table, clock roster.
- **API:** `/senior-teacher/supervised-classrooms|supervised-staff|fee-balances|students`; `/staff-attendance/clock-roster|staff/history`.
- **States:** empty "Nothing assigned"; skeletons; retry.

## A3. Parent

### A3.1 Parent Home
- **Purpose:** Multi-child overview.
- **Components:** child switcher chips, per-child cards (attendance %, fee balance, latest result), Pay-now CTA, bus-status card, announcements, unread chat.
- **Actions:** switch child; pay; open child detail; open chat.
- **API:** `GET /students?guardian` (children); `GET /students/{id}/stats`; `GET /students/{id}/statement`; `GET /announcements`.
- **Empty:** "No children linked — contact school."
- **Loading:** card skeletons. **Error:** per-card retry.

### A3.2 Fees / Financial Portal (enhanced)
- **Purpose:** View balances and pay per child.
- **Components:** child selector, balance summary, invoices list, statement, "Pay now" → `MpesaPromptModal`, payment history, receipt download.
- **Actions:** pay (STK), view statement, download receipt.
- **API:** `GET /students/{id}/statement?year`; `POST /students/{id}/mpesa/prompt`; `GET /students/{id}/mpesa/payment-link`; `GET /payments`.
- **Empty:** "No outstanding balance 🎉" / "No payment history."
- **Loading:** skeleton. **Error:** payment failure → status from `mpesaStatus` polling; retry.

### A3.3 Transport / Live Bus (NEW)
- **Purpose:** Track bus + verify pickup.
- **Components:** live map (bus marker, route, stops, ETA), boarding/alighting status, pickup verification (QR/OTP).
- **API:** transport live (NEW endpoint), `teacherTransport.api` pickups for verification.
- **Empty:** "No transport assigned." **Loading:** map skeleton. **Error:** "Live location unavailable."

## A4. Student

### A4.1 Student Home / Homework / Results
- **Home:** timetable today, homework due, latest results, attendance streak, announcements.
- **Homework:** list with due dates; (NEW) submit upload. API: `GET /assignments` (scoped).
- **Results:** term selector, subject grades, report card link. API: `GET /marks` (scoped), `GET /report-cards`.
- **States:** empty "No homework due", "Results not published"; skeletons; retry.

## A5. Driver

### A5.1 Driver Home
- **Purpose:** Today's trips + active trip entry.
- **Components:** active-trip card (start/resume), trips list, vehicle info, SOS/incident button.
- **API:** `GET /driver/trips?date`.
- **Empty:** "No trips scheduled today." **Loading:** skeleton. **Error:** retry.

### A5.2 Driver Active Trip
- **Purpose:** Run a trip with live GPS + boarding (NEW).
- **Components:** map, start/stop trip, per-stop student roster with board/alight toggles, progress, end trip.
- **API:** `GET /driver/trips/{id}?date`; trip start/stop + boarding (NEW); pickups via `teacherTransport.api`.
- **Empty:** "No students on this trip." **Loading:** roster skeleton. **Error:** GPS/permission prompts; offline buffers telemetry.

### A5.3 Routes / Route Detail
- View route + drop points. API: `GET /routes`, `GET /routes/{id}`, `GET /routes/{id}/drop-points`. Empty "No routes." 

## A6. Shared Staff screens
Announcements (read), Notifications (inbox + mark read), Settings (theme, biometrics, notification prefs, check updates, logout), Feedback form. APIs: `/announcements`, `/notifications` (+ read/mark-all), `/notification-preferences`, `/feedback/*`. Standard states.

---

# PART B — ADMIN APP SCREENS

## B1. Dashboard (role-aware)
- **Purpose:** Executive/finance/academic/ops cockpit.
- **Components:** `DashboardHero`, period selector (term/year), `StatTile` grid, `LineChart`/`BarChart`/`DonutChart`, approvals badge, quick-post announcement, drill-down tiles.
- **Actions:** change period; tap tile → module; quick actions.
- **API:** `GET /dashboard/stats?academic_year_id&term_id`; module `*/summary` (`/finance/summary`, `/hr/summary`, `/transport/summary`, `/library/summary`, `/inventory/summary`, `/pos/summary`, `/hostels/summary`).
- **Empty:** "No data for selected period." **Loading:** skeleton tiles + chart shimmer. **Error:** per-widget retry; cached fallback.

## B2. Approvals Inbox (NEW, unified)
- **Purpose:** Single queue for all pending approvals.
- **Components:** filter tabs (Leave/Advances/Lesson plans/Requisitions/Expenses), list with requester/type/urgency, detail sheet, Approve/Reject(+reason)/Escalate.
- **Actions:** filter; open; approve/reject/escalate; bulk approve.
- **API:** `GET /leave-requests`, `/salary-advances`, `/lesson-plans/review-queue`, `/requisitions`; approve/reject endpoints per type.
- **Empty:** "No pending approvals 🎉" **Loading:** list skeleton. **Error:** retry; per-item action error toast + rollback.

## B3. People — Students (registry)
### B3.1 Students List
- Components: search, filters (class/status/category), `ListItem`, FAB "Add", bulk-upload entry. API: `GET /students`, `GET /student-categories`, `GET /classes`. Empty "No students." 
### B3.2 Add / Edit Student
- Components: multi-section form (bio, class/stream, guardians, fees, photo/docs multipart), validation. API: `POST /students` / `createStudentMultipart`; `PUT /students/{id}` / `updateStudentMultipart`. Error: 422 field mapping. Loading: disabled form + spinner.
### B3.3 Bulk Upload
- Components: template download, file picker, preview, import result summary. API: `GET /students/bulk-upload-template`, `POST /students/bulk-upload`. States: progress bar; error rows table.
### B3.4 Family Management / Archive
- Guardian linking; archive/restore. API: family endpoints; `POST /students/{id}/archive|restore`. Confirm dialogs.

## B4. People — Staff / HR
### B4.1 Staff Directory / Detail / Edit
- List + search; detail tabs (profile, attendance, payroll, leave); create/edit + photo. API: `GET /staff`, `GET /staff/{id}`, `POST/PUT /staff`, `POST /staff/{id}/photo`, `DELETE /staff/{id}`. 
### B4.2 Payroll
- Generate (month), records list, process, payslip download. API: `GET /payroll-records`, `POST /payrolls/generate`, `POST /payrolls/{id}/process`, `GET /payrolls/{id}/payslip`. Empty "No payroll run." Confirm before process.
### B4.3 Leave Management
- Pending/approved tabs, approve/reject(+reason). API: `GET /leave-requests`, approve/reject. (Also feeds Approvals inbox.)
### B4.4 Staff Attendance / Clock Oversight / Geofence config
- Roster, history, manual mark, geofence settings (map radius). API: `/staff-attendance`, `/staff-attendance/geofence` (GET/PUT), `/clock-roster`.
### B4.5 Salary Advances / Appraisals (NEW)
- Request list + approve/reject; appraisal cycles. API: `/salary-advances` (+approve/reject); appraisals (NEW).

## B5. Academics (admin)
### B5.1 Exams Management
- List/schedule, grading scales, moderation, result analysis. API: `GET /exams`, `GET /exams/{id}`, marks read, analysis (NEW). 
### B5.2 Report Cards
- Generate / bulk generate / publish / download. API: `POST /report-cards/generate`, `/generate-bulk`, `/report-cards/{id}/publish`, `/download`. Progress + confirm publish.
### B5.3 Lesson Plan & Scheme Review
- Review queue, approve/reject. API: `/lesson-plans/review-queue`, approve/reject; schemes (NEW).
### B5.4 Attendance Analytics (school-wide)
- Charts, at-risk students, filters. API: `GET /attendance/analytics?class_id&dates`.
### B5.5 Timetable Builder (NEW) / CBC config (NEW)
- Drag-drop periods, clash detection; competency/strand config.

## B6. Finance (admin)
### B6.1 Finance Home / Summary
- KPI tiles + charts. API: `GET /finance/summary`, `GET /dashboard/stats`.
### B6.2 Invoices / Fee Structures / Defaulters
- Lists + detail + create. API: `GET /invoices`, `/invoices/{id}`, `/fee-structures`. Defaulters = filtered invoices.
### B6.3 Payments / Record Payment / Receipts
- List, detail, record (cash/manual), receipt. API: `GET /payments`, `/payments/{id}`, `POST /payments`.
### B6.4 Transactions Reconciliation
- Bank/C2B feed; confirm/reject/share-split across students; mark swimming. API: `GET /finance/transactions`, `/{id}` (+share/confirm/reject), `POST /finance/transactions/mark-swimming`.
- Empty "All transactions reconciled." Action errors toast + rollback.
### B6.5 M-Pesa initiate / Student Statement
- STK prompt + waiting webview; statement viewer. API: `POST /students/{id}/mpesa/prompt`, `GET .../payment-link`, `GET /students/{id}/statement`.

## B7. Operations
### B7.1 Transport (routes/vehicles/drop-points/assignments/live fleet)
- CRUD + map editor + live fleet (NEW). API: `transport.api` full set + `GET /transport/summary`.
### B7.2 Library (books/cards/borrowings)
- Catalog CRUD, card issue/suspend/activate, borrow/return/renew/mark-lost. API: `library.api` full set.
### B7.3 Inventory/Store (items/adjustments/requisitions/student-requirements)
- Items CRUD, stock adjust, requisition approve/fulfill, requirement templates. API: `inventory.api` full set.
### B7.4 POS (products/orders/uniforms)
- Catalog + orders + status. API: `pos.api` full set.
### B7.5 Hostel (hostels/rooms/allocations)
- CRUD + allocate/deallocate. API: `hostel.api` full set.
### B7.6 Health/Clinic (NEW) & Visitor/Security (NEW)
- Clinic visit log + medical records; visitor check-in/out + gate pass + incidents. API: new endpoints.
- Each: list + create + detail; standard empty/loading/error.

## B8. Communication (admin)
### B8.1 Announcements (create/publish/target)
- Editor (title, body, audience target, schedule, attachments), publish, delete. API: `POST/PUT /announcements`, `/{id}/publish`, `DELETE`.
### B8.2 Broadcast SMS/Email / Templates / Delivery Status
- Compose + recipient selection + template; delivery tracking. API: `POST /messages/send`, `/message-templates` CRUD, `GET /messages/{id}/delivery-status`.
### B8.3 Circulars (NEW) / Chat moderation (NEW)
- Distribution with read-receipt; moderate flagged chats.

## B9. Documents
- Templates, generate, certificates issue/download, export/import Excel/PDF. API: `documents.api` full set.

## B10. Reports & Analytics (NEW)
- Cross-module dashboards with cross-filtering + export. API: aggregate of `dashboard.api` + `*/summary` + analytics.

## B11. Settings (admin)
- School config, branding upload, geofence, roles/permissions, feature flags, app updates. API: `app-branding` (admin write — NEW), `staff-attendance/geofence`, notification prefs.

---

## 6.x Screen coverage map (for Stitch/Figma generation order)

| App | Module | Screens to generate |
|-----|--------|---------------------|
| Staff | Auth | Login, OTP, Forgot, Reset, App-mismatch |
| Staff | Teacher | Home, Classes, Student detail, Mark attendance, Marks matrix, Assignments(×3), Lesson plans(×5), Diary, Timetable, Report card, Clock, Profile/Edit, Salary, Apply leave, Requirements(×2), Senior queue/supervised(×4) |
| Staff | Parent | Home, Fees portal, Child detail, Statement, Transport/Live bus |
| Staff | Student | Home, Homework, Results |
| Staff | Driver | Home, Active trip, Routes, Route detail |
| Staff | Shared | Announcements, Notifications, Settings, Feedback |
| Admin | Core | Role dashboards (×4), Approvals inbox |
| Admin | People | Students list/add/edit/bulk/family, Staff directory/detail/edit, Payroll, Leave, Staff attendance, Advances |
| Admin | Academics | Exams, Report cards, Lesson/scheme review, Attendance analytics, Timetable builder, CBC config |
| Admin | Finance | Home, Invoices, Fee structures, Defaulters, Payments, Record payment, Reconciliation, M-Pesa, Statement |
| Admin | Operations | Transport(+live fleet), Library, Inventory, POS, Hostel, Clinic, Visitor/Security |
| Admin | Comms | Announcements, Broadcast, Templates, Delivery, Circulars, Chat moderation |
| Admin | Other | Documents, Reports & Analytics, Settings |

> Total ~95 screens. Generate shared/auth + design system first, then Staff teacher flow, then Admin dashboards + approvals, then back-office modules.
