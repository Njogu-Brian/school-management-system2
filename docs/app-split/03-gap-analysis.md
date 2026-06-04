# STEP 3 — Gap Analysis & Modern Feature Recommendations

> What a modern, competitive school-ERP mobile ecosystem should have, mapped against **what exists today** in the codebase. Each row notes: current state, the gap, the target app(s), and priority (P0 critical → P3 nice-to-have).

---

## 3.1 Academics

| Capability | Today | Gap / recommendation | App | Priority |
|------------|-------|----------------------|-----|----------|
| **Attendance** | Class mark + records + per-student stats (`MarkAttendanceScreen`, `attendance.api`) | Add **biometric/QR/RFID** quick-mark, **bulk "all present"**, **offline marking with sync**, parent absence notifications, period-wise (subject) attendance, attendance trends per student. | A (mark) / B (analytics) | P1 |
| **CBC/CBE Assessment** | **Missing** (only numeric exam marks + matrix) | Build **competency-based assessment**: strands/sub-strands, rubric levels (E.E./M.E./A.E./B.E.), formative vs summative, learning-outcome tracking, CBC report cards. Kenya CBC/CBE is now mandatory. | A (capture) / B (config+publish) | **P0** |
| **Lesson Plans** | Full authoring + submit + senior review (`LessonPlan*`) | Add **templates per subject/grade**, **versioning**, **curriculum-aligned objectives library**, **cloning previous term**, attachment of resources. | A (author) / B (templates) | P2 |
| **Schemes of Work** | **Missing** | Add scheme-of-work builder linked to lesson plans & curriculum, term coverage tracking, "behind schedule" flags. | A (author) / B (approve+track) | **P0** |
| **Grade Books** | Marks entry only; no consolidated gradebook | Add **continuous gradebook** per class/subject: weighted categories, running averages, trend per student, export. | A (teacher) / B (oversight) | P1 |
| **Report Cards** | View/generate/publish/download (`ReportCardScreen`, `documents.api`) | Add **CBC report formats**, **teacher comment workflow with approval**, **parent acknowledgment**, multi-term comparison, digital signature. | C (view) / B (publish) | P1 |
| **Exam Management** | Exams list, marks setup, batch/matrix entry | Add **exam scheduling & seating**, **invigilation rosters**, **grading scales config**, **moderation workflow**, **result analysis (mean/deviation, subject ranking)**. | B (manage) / A (enter) | P1 |
| **Learning Resources** | **Missing** | Add **resource library** (PDF/video/links) per subject/grade, teacher upload, student/parent access, offline download. | A (consume+upload) / B (curate) | P2 |
| **Teacher Performance Tracking** | Lesson-plan submission + clock-in are proxies only | Add **TPAD-style appraisal**, lesson-observation forms, syllabus-coverage %, marks-submission timeliness, KPI dashboard. | A (self+evidence) / B (appraise+analyze) | P2 |
| **Homework/Assignments** | Create/view; student homework view | Add **submission upload by students**, **auto/teacher grading**, due reminders, plagiarism flag, parent visibility. | A | P1 |
| **Timetable** | View only | Add **timetable builder** (clash detection), substitution/cover management, "my next class" widget. | B (build) / A (view) | P2 |

## 3.2 Communication

| Capability | Today | Gap / recommendation | App | Priority |
|------------|-------|----------------------|-----|----------|
| **Chat / Messaging** | **Missing** (only one-way SMS broadcast) | Build **real-time 1:1 & group chat** (teacher↔parent, staff↔staff) with read receipts, moderation, attachments — use Laravel Echo + Pusher (already a web dependency). | A + B | **P0** |
| **Announcements** | Read + create/publish | Add **targeting** (class/grade/role), **scheduled publish**, **acknowledgment tracking**, rich media, pinned. | C (read) / B (manage) | P1 |
| **Push Notifications** | Register-only; no routing | Add **deep-link routing** (tap → screen), categories/channels, rich push, silent data push for sync, quiet hours. | C | **P0** |
| **Parent Communication** | SMS broadcast only | Add **per-child communication hub**, consent forms, permission-slip e-sign, meeting/PTC booking, absence reporting by parent. | A | P1 |
| **Circulars** | **Missing** | Add **circular distribution** with read-receipt, attachments, versioning, archive. | C (read) / B (issue) | P2 |

## 3.3 Transport

| Capability | Today | Gap / recommendation | App | Priority |
|------------|-------|----------------------|-----|----------|
| **Live Bus Tracking** | **Missing** (only foreground clock GPS) | Build **background GPS** on driver app + **live map for parents** (ETA, geofence arrival alerts). Requires `expo-task-manager`/`expo-location` background + map SDK. | A (driver broadcast + parent view) | **P0** |
| **Driver Trips** | Active trip + today's trips + roster | Add **trip start/stop with telemetry**, **per-stop boarding/alighting scan**, incident reporting, fuel/mileage log. | A | P1 |
| **Route Management** | CRUD routes/drop-points/vehicles | Add **map-based route editor**, capacity vs assignment view, optimization hints. | B | P2 |
| **Student Pickup Verification** | Mark collected by parent (`teacherTransport.api`) | Add **QR/OTP handover at stop**, guardian photo match, parent live "boarded/alighted" notification, unauthorized-pickup alert. | A | P1 |

## 3.4 Finance

| Capability | Today | Gap / recommendation | App | Priority |
|------------|-------|----------------------|-----|----------|
| **Fee Statements** | Student statement view | Add **downloadable PDF statement**, term breakdown, multi-child consolidated view. | C (view) / B (manage) | P1 |
| **Payment Tracking** | Payments list/detail, M-Pesa STK | Add **payment history timeline per child**, receipt download, multiple gateways (card, bank, Jenga — already documented), partial-payment plans. | A (parent) / B (admin) | P1 |
| **Fee Balances** | Senior-teacher balances; statement | Add **parent fee-balance widget + reminders**, due-date countdown, auto-reminder scheduling. | A (parent) / B (config) | P1 |
| **Parent Financial Portal** | Partial (statement + pay) | Build a **dedicated parent finance hub**: balances, invoices, pay now, history, receipts, payment plans, statements — all per child. | A | **P0** |
| **Expenses / Budgets** | Web has expense module (docs) | Surface **expense approvals & budget dashboards** in Admin App. | B | P2 |

## 3.5 Administration

| Capability | Today | Gap / recommendation | App | Priority |
|------------|-------|----------------------|-----|----------|
| **Incident Reporting** | **Missing** | Build **incident capture** (safety/health/behavior) with photos, severity, assignment, resolution workflow. | A (report) / B (manage) | P1 |
| **Discipline Tracking** | **Missing** | Add **behavior/merit-demerit system**, case log, parent notification, sanction workflow, analytics. | A (log) / B (manage) | P1 |
| **Visitor Management** | **Missing** | Build **visitor check-in/out** (security): pre-registration, QR badge, host notification, blacklist, gate pass — core Security Personnel feature. | B (security) | **P0** for security role |
| **Inventory Requests** | Requisitions API exists; no full UI | Build **requisition raise (any staff) + approval/fulfillment (store keeper)** with stock levels and notifications. | A (raise) / B (approve/fulfill) | P1 |
| **Leave Requests** | Apply + manage exist | Add **leave balance/accrual view**, calendar of who's out, multi-level approval, cover assignment. | A (request) / B (approve) | P1 |
| **Approval Workflows** | Ad-hoc per feature (leave, lesson plan, requisition, advances) | Build a **unified approvals inbox** (one queue for all pending approvals) + configurable multi-step workflow engine. | B (primary) / A (my requests status) | **P0** |
| **Gate Pass / Movement** | **Missing** | Student/staff exit-pass approvals. | B | P3 |

## 3.6 Human Resource

| Capability | Today | Gap / recommendation | App | Priority |
|------------|-------|----------------------|-----|----------|
| **Staff Attendance** | GPS geofence clock + roster | Add **shift schedules**, **late/overtime rules**, **biometric integration**, monthly timesheet export, absence reasons. | A (clock) / B (manage) | P1 |
| **Payroll Access** | School payroll + my salary/payslip | Add **payslip PDF download**, YTD earnings, deductions breakdown, P9/tax forms, NHIF/NSSF. | A (self) / B (process) | P1 |
| **Leave Management** | Apply/approve | Add **balances, accrual policy, calendar, cover** (see Administration). | A/B | P1 |
| **Performance Appraisals** | **Missing** | Build **appraisal cycles** (self-assessment → supervisor → moderation), goal-setting, evidence upload, scoring, reports (TPAD/TSC-aligned). | A (participate) / B (run+analyze) | P2 |
| **Recruitment / Onboarding** | **Missing** | Onboarding checklists, document collection, contract e-sign. | B | P3 |

## 3.7 Health (Nurse) — role gap

| Capability | Today | Gap / recommendation | App | Priority |
|------------|-------|----------------------|-----|----------|
| **Clinic Visits / Sick Bay** | **Missing** | Build **clinic log** (visit, symptoms, treatment, medication), parent notification, recurring-condition flags. | B (nurse manage) / A (parent view) | P1 |
| **Medical Records** | **Missing** | Per-student health profile, allergies, immunization, emergency contacts. | B (manage) / A (parent maintain) | P1 |
| **Medication Schedule** | **Missing** | Scheduled meds with administration log + reminders. | B | P2 |

## 3.8 Platform / non-functional gaps (apply to both apps)

| Capability | Today | Gap / recommendation | Priority |
|------------|-------|----------------------|----------|
| **Offline-first** | Banner only; no sync | Add **read cache + write queue + background sync** (TanStack Query persist + mutation queue). README already over-claims this. | **P0** |
| **Analytics & telemetry** | None | Add **product analytics** (screen views, funnels) + **crash reporting** (Sentry) + performance monitoring. | **P0** |
| **Deep linking & universal links** | None | Required for push routing, share links, M-Pesa returns. | P1 |
| **Accessibility (a11y)** | Unaudited | Font scaling, screen-reader labels, contrast (esp. branded themes), localization (EN/SW). | P1 |
| **Localization (i18n)** | None | Swahili + English at minimum. | P2 |
| **Security hardening** | `normalizeRole` defaults to TEACHER; client-only gating | Explicit-deny role mapping, certificate pinning, jailbreak/root detection, secure logging. | P1 |
| **Feature flags / remote config** | None | Roll out features per tenant/role without store releases. | P2 |
| **Search (global)** | Per-list only | Global search across students/staff/transactions in Admin; children/announcements in Staff. | P2 |
| **Audit trail visibility** | Backend only | Surface "who changed what" in Admin App. | P3 |

---

## 3.9 Prioritized backlog (top P0s to anchor the roadmap)

1. **CBC/CBE assessment + schemes of work** (academic compliance).
2. **Unified approvals inbox + workflow engine** (admin efficiency).
3. **Real-time chat** (parent/teacher engagement).
4. **Push deep-link routing + offline-first sync** (table-stakes platform).
5. **Parent financial portal** (revenue + parent satisfaction).
6. **Live bus tracking + pickup verification** (safety differentiator).
7. **Visitor management** (security role enablement).
8. **Analytics + crash reporting** (operational visibility for both apps).
