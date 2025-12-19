# Demo Walkthrough – Super Admin

Use this script to guide stakeholders through the full system using the demo data seeded by `DemoDataSeeder`.

## 0) Sign in
- URL: your demo domain (e.g., `https://demo.school.test/`)
- Super admin login: `admin.demo@school.test` / `Demo@123`

## 1) Dashboard overview
- Observe key widgets (students, fees, attendance, exams, transport, hostel).
- Confirm notifications/announcements display (“Welcome to Demo Mode”).

## 2) Students & admissions
- Navigate: `Students → Students`.
- Verify Kenyan-named students, families, guardians, categories, streams.
- Open a student: see demographics, family link, diary note, documents (sample report).
- Admissions: `Students → Online Admissions` to show application flow.

## 3) Academics
- Navigate: `Academics → Exams`: view CAT exam with marks.
- `Academics → Report Cards`: demo report entries exist.
- `Academics → Homework/Diaries`: show demo homework and diary notes.
- `Academics → Classes/Streams/Subjects`: confirm seeded classes/streams/subjects.

## 4) Attendance
- Navigate: `Attendance → Student Attendance`.
- Show recorded present entries from demo seed.

## 5) Finance (Fees)
- Navigate: `Finance → Fee Structures`: demo structure with voteheads and charges.
- `Finance → Invoices`: open a student invoice (partial payment visible).
- `Finance → Payments/Receipts`: M-Pesa demo payment recorded.
- `Finance → Voteheads & Categories`: preseeded categories and voteheads.

## 6) POS (School shop)
- Navigate: `POS → Orders`: demo order paid via M-Pesa.
- `POS → Products`: PE Kit product with size variants and stock.
- Show order items, fulfillment status, and linked payment transaction.

## 7) Inventory & Requisitions
- Navigate: `Inventory → Items`: demo stock (exercise books, PE kit, lab goggles).
- `Inventory → Transactions`: inbound GRN and outbound issues.
- `Inventory → Requisitions`: approved requisition for lab goggles with issued quantities.
- `Inventory → Requirements`: requirement templates tied to classes; student requirement marked complete.

## 8) HR & Payroll
- Navigate: `HR → Staff`: Kenyan staff records; show profile fields.
- `HR → Payroll → Periods`: current month period (draft/approved).
- Open payroll records: allowances/deductions, generated payslip numbers.
- Documents: HR contract sample attached to a staff profile.

## 9) Transport
- Navigate: `Transport → Routes/Vehicles/Trips`: “Nairobi Westlands Loop”, vehicle `KDA 234A`.
- Student assignments: show pickup/drop-off points.

## 10) Hostel
- Navigate: `Hostel → Hostels/Rooms/Allocations`: “Kilimanjaro Hostel”, room A1 with allocation.

## 11) Library
- Navigate: `Library → Books`: “Fasihi ya Kiswahili” with copies.
- Borrowings: active borrowing for a student; library card details.

## 12) Communication & Announcements
- `Communication → Templates`: “Fee Reminder” SMS template.
- `Announcements`: “Welcome to Demo Mode” published.

## 13) Documents
- `Documents` (or student/staff profile attachments): sample student report and HR contract with download links.

## 14) Settings & Permissions
- Navigate: `Settings → Roles/Permissions` to show RBAC; demo roles include admin, teacher, bursar, accountant, parent, student.
- Highlight that super admin can manage all modules and users.

## Tips for the demo
- Stay in read-only mode: avoid deleting seeded records; cloning or creating extras is safe.
- If data looks missing, rerun seeder: `php artisan migrate:fresh --seed --class=DemoDataSeeder`.
- Keep credentials handy; parent/student logins also exist for role-specific views if needed.

