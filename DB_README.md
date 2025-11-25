# Database Schema Documentation

This document provides an overview of the database schema, key tables, and data relationships.

## Database Information

- **Database Type:**** MySQL/MariaDB (primary), PostgreSQL (supported)
- **Character Set:** UTF8MB4
- **Collation:** utf8mb4_unicode_ci
- **Laravel Version:** 12.x

## Core Tables

### Users & Authentication

#### `users`
- Primary user authentication table
- Uses Spatie Laravel Permission for roles (no `role` column)
- **Key Fields:**
  - `id`, `name`, `email`, `password`
  - `email_verified_at`, `remember_token`
- **Relationships:**
  - Has one `Staff` (if staff user)
  - Has many roles via Spatie permissions

#### `staff`
- Staff/employee information
- **Key Fields:**
  - `id`, `staff_id` (unique identifier)
  - `first_name`, `middle_name`, `last_name`
  - `email`, `phone`, `department_id`, `job_title_id`
  - `hire_date`, `employment_status`
  - `bank_account`, `nssf_number`, `nhif_number`
- **Relationships:**
  - Belongs to `Department`
  - Belongs to `JobTitle`
  - Belongs to `StaffCategory`
  - Has one `User` (for system access)
  - Has many `PayrollRecord`
  - Has many `LeaveRequest`

### Students

#### `students`
- Student information
- **Key Fields:**
  - `id`, `admission_number` (unique)
  - `first_name`, `middle_name`, `last_name`
  - `dob`, `gender`
  - `classroom_id`, `stream_id`
  - `parent_id`, `family_id`
  - `nemis_number`, `knec_assessment_number`
  - `status` (active, inactive, graduated, transferred, expelled, suspended)
  - `admission_date`, `graduation_date`
- **Relationships:**
  - Belongs to `Classroom`
  - Belongs to `Stream`
  - Belongs to `ParentInfo`
  - Belongs to `Family`
  - Has many `Attendance`
  - Has many `Invoice`
  - Has many `Payment`
  - Has many `ExamMark`

#### `parent_info`
- Parent/guardian information
- **Key Fields:**
  - `id`, `first_name`, `last_name`
  - `email`, `phone`, `address`
  - `relationship` (father, mother, guardian, etc.)
- **Relationships:**
  - Has many `Student`

#### `families`
- Family grouping for multiple students
- **Key Fields:**
  - `id`, `guardian_name`, `guardian_phone`, `guardian_email`
- **Relationships:**
  - Has many `Student`

### Academics

#### `classrooms`
- Class/grade levels
- **Key Fields:**
  - `id`, `name` (e.g., "Grade 1", "Form 1")
  - `level_type` (preschool, lower_primary, upper_primary, junior_high)
- **Relationships:**
  - Has many `Stream`
  - Has many `Student`
  - Has many `ClassroomSubject`

#### `streams`
- Streams/sections within a classroom
- **Key Fields:**
  - `id`, `name` (e.g., "A", "B", "Red", "Blue")
  - `classroom_id`
- **Relationships:**
  - Belongs to `Classroom`
  - Has many `Student`

#### `subjects`
- Subject/course catalog
- **Key Fields:**
  - `id`, `name`, `code`
  - `subject_group_id`
- **Relationships:**
  - Belongs to `SubjectGroup`
  - Has many `ClassroomSubject`
  - Has many `Exam`
  - Has many `ExamMark`

#### `exams`
- Exam/test definitions
- **Key Fields:**
  - `id`, `name`, `type` (cat, midterm, endterm, sba, mock, quiz)
  - `academic_year_id`, `term_id`
  - `classroom_id`, `subject_id`
  - `max_marks`, `weight`
  - `status` (draft, open, marking, moderation, approved, published, locked)
- **Relationships:**
  - Belongs to `AcademicYear`
  - Belongs to `Term`
  - Belongs to `Classroom`
  - Belongs to `Subject`
  - Has many `ExamMark`
  - Has many `ExamSchedule`

#### `exam_marks`
- Individual student exam scores
- **Key Fields:**
  - `id`, `exam_id`, `student_id`, `subject_id`
  - `score_raw`, `score_moderated`
  - `grade_label`, `remark`
- **Relationships:**
  - Belongs to `Exam`
  - Belongs to `Student`
  - Belongs to `Subject`

#### `report_cards`
- Generated report cards per term
- **Key Fields:**
  - `id`, `student_id`, `academic_year_id`, `term_id`
  - `pdf_path`, `published_at`
  - `public_token` (for public access)
- **Relationships:**
  - Belongs to `Student`
  - Belongs to `AcademicYear`
  - Belongs to `Term`

### Finance

#### `invoices`
- Student fee invoices
- **Key Fields:**
  - `id`, `student_id`, `invoice_number`
  - `academic_year_id`, `term_id`
  - `total_amount`, `paid_amount`, `balance`
  - `status` (draft, pending, posted, paid, overdue, cancelled)
  - `due_date`
- **Relationships:**
  - Belongs to `Student`
  - Has many `InvoiceItem`
  - Has many `Payment`

#### `payments`
- Payment records
- **Key Fields:**
  - `id`, `student_id`, `invoice_id`
  - `amount`, `payment_method`
  - `reference`, `payment_date`
  - `reversed` (boolean)
- **Relationships:**
  - Belongs to `Student`
  - Belongs to `Invoice`

#### `fee_structures`
- Fee structure definitions
- **Key Fields:**
  - `id`, `name`, `academic_year_id`, `term_id`
  - `classroom_id` (nullable for school-wide)
- **Relationships:**
  - Belongs to `AcademicYear`
  - Belongs to `Term`
  - Belongs to `Classroom` (nullable)
  - Has many `FeeCharge`

#### `voteheads`
- Fee voteheads/categories
- **Key Fields:**
  - `id`, `name`, `code`, `description`
- **Relationships:**
  - Has many `FeeCharge`

### Attendance

#### `attendance`
- Student attendance records
- **Key Fields:**
  - `id`, `student_id`, `date`
  - `status` (present, absent, late)
  - `reason`, `reason_code_id`
  - `is_excused`, `is_medical_leave`
  - `arrival_time`, `departure_time`
  - `consecutive_absence_count`
- **Relationships:**
  - Belongs to `Student`
  - Belongs to `AttendanceReasonCode` (nullable)
  - Belongs to `Subject` (nullable, for subject-wise attendance)

#### `attendance_reason_codes`
- Standardized absence reasons
- **Key Fields:**
  - `id`, `code`, `name`, `description`
  - `requires_excuse` (boolean)

### Transport

#### `routes`
- Transport routes
- **Key Fields:**
  - `id`, `name`, `description`
  - `start_location`, `end_location`
- **Relationships:**
  - Has many `Vehicle`
  - Has many `Trip`
  - Has many `Student` (via transport assignment)

#### `vehicles`
- School vehicles
- **Key Fields:**
  - `id`, `registration_number`, `make`, `model`
  - `capacity`, `driver_id`
- **Relationships:**
  - Belongs to `Staff` (driver)
  - Belongs to `Route`
  - Has many `Trip`

#### `trips`
- Individual transport trips
- **Key Fields:**
  - `id`, `route_id`, `vehicle_id`
  - `trip_type` (morning, evening)
  - `scheduled_time`
- **Relationships:**
  - Belongs to `Route`
  - Belongs to `Vehicle`
  - Has many `StudentAssignment`

### HR & Payroll

#### `payroll_records`
- Staff payroll records
- **Key Fields:**
  - `id`, `staff_id`, `payroll_period_id`
  - `gross_salary`, `net_salary`
  - `deductions` (JSON)
  - `payslip_number`
- **Relationships:**
  - Belongs to `Staff`
  - Belongs to `PayrollPeriod`
  - Belongs to `SalaryStructure`

#### `leave_requests`
- Staff leave requests
- **Key Fields:**
  - `id`, `staff_id`, `leave_type_id`
  - `start_date`, `end_date`
  - `reason`, `status` (pending, approved, rejected, cancelled)
- **Relationships:**
  - Belongs to `Staff`
  - Belongs to `LeaveType`

### Inventory

#### `inventory_items`
- Inventory items (stationery, books, supplies)
- **Key Fields:**
  - `id`, `name`, `category`, `brand`
  - `quantity`, `min_stock_level`
  - `unit_cost`, `location`
- **Relationships:**
  - Has many `InventoryTransaction`
  - Has many `RequisitionItem`

#### `requisitions`
- Requisition requests
- **Key Fields:**
  - `id`, `requested_by` (staff_id)
  - `status` (pending, approved, fulfilled, rejected)
  - `requested_date`, `approved_date`
- **Relationships:**
  - Belongs to `Staff` (requested_by)
  - Has many `RequisitionItem`

### Communication

#### `communication_logs`
- Communication history (SMS, Email)
- **Key Fields:**
  - `id`, `type` (sms, email)
  - `recipient`, `message`, `status`
  - `sent_at`, `delivered_at`
- **Relationships:**
  - Belongs to `User` (sent_by)

### Settings

#### `settings`
- System settings
- **Key Fields:**
  - `id`, `key`, `value` (JSON)
  - `group` (general, branding, academic, etc.)
- **Note:** Settings are stored as key-value pairs with JSON values

## Sensitive Data Locations

### Personal Identifiable Information (PII)

- **`students` table:**
  - `first_name`, `middle_name`, `last_name`
  - `dob`, `gender`
  - `national_id_number`, `passport_number`
  - `home_address`, `home_city`, `home_county`
  - `medical_insurance_number`
  - `allergies`, `chronic_conditions`

- **`staff` table:**
  - `first_name`, `middle_name`, `last_name`
  - `dob`, `gender`
  - `national_id_number`, `kra_pin`
  - `bank_account`, `nssf_number`, `nhif_number`
  - `residential_address`

- **`parent_info` table:**
  - `first_name`, `last_name`
  - `email`, `phone`, `address`

### Financial Data

- **`invoices` table:**
  - `total_amount`, `paid_amount`, `balance`

- **`payments` table:**
  - `amount`, `payment_method`, `reference`

- **`payroll_records` table:**
  - `gross_salary`, `net_salary`, `deductions`

### Authentication Data

- **`users` table:**
  - `email`, `password` (hashed)

## Data Relationships Summary

```
AcademicYear
  ├── Term
  │     ├── Exam
  │     │     └── ExamMark → Student
  │     └── ReportCard → Student
  └── FeeStructure
        └── FeeCharge → Votehead

Classroom
  ├── Stream
  │     └── Student
  │           ├── Attendance
  │           ├── Invoice → Payment
  │           └── ExamMark
  └── ClassroomSubject → Subject

Staff
  ├── User (authentication)
  ├── PayrollRecord → PayrollPeriod
  └── LeaveRequest → LeaveType

Student
  ├── ParentInfo
  ├── Family
  └── Transport Assignment → Route → Vehicle
```

## Migration Safety Notes

### Safe to Run
- Most migrations are additive (adding columns/tables)
- Migrations that add nullable columns
- Migrations that create new tables

### Requires Caution
- Migrations that modify existing columns
- Migrations that add non-nullable columns (may need data backfill)

### Requires Manual Approval
- `2025_11_19_120010_drop_legacy_diary_tables.php` - Drops tables
- `2025_07_01_071712_remove_role_column_from_users_table.php` - Drops column
- `2025_03_27_151108_remove_class_from_students_table.php` - Drops column
- `2025_09_10_093710_remove_old_department_column_from_staff_table.php` - Drops column

## Backup Recommendations

**Always backup before:**
1. Running any migration
2. Modifying production data
3. Bulk updates
4. Schema changes

**Backup frequency:**
- Production: Daily automated backups
- Staging: Before each deployment
- Development: Before major schema changes

## Indexes & Performance

Key indexes exist on:
- `students.admission_number` (unique)
- `students.classroom_id`, `students.stream_id`
- `attendance.student_id`, `attendance.date`
- `invoices.student_id`, `invoices.status`
- `payments.invoice_id`

## Notes for Developers

1. **Never drop columns without two-step migration process**
2. **Always use foreign key constraints**
3. **Use soft deletes where appropriate** (`deleted_at` column)
4. **Keep audit trails** (created_at, updated_at, created_by, updated_by)
5. **Use enums for status fields** (with safe defaults)
6. **Test migrations on development database first**

## Additional Resources

- Laravel Migration Documentation: https://laravel.com/docs/migrations
- Database Naming Conventions: Follow Laravel conventions (plural table names, snake_case columns)
- Foreign Key Naming: `{table}_{column}_foreign`

