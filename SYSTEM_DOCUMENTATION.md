# School Management System - Comprehensive Documentation

**Last Updated:** December 15, 2025  
**Version:** 2.0  
**Status:** Active Development

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [System Overview](#system-overview)
3. [Fees Management Module](#fees-management-module)
4. [Features Implemented](#features-implemented)
5. [Features Pending](#features-pending)
6. [Technical Architecture](#technical-architecture)
7. [Database Schema](#database-schema)
8. [API & Services](#api--services)
9. [Testing & Quality Assurance](#testing--quality-assurance)
10. [Deployment & Maintenance](#deployment--maintenance)
11. [Change Log](#change-log)

---

## Executive Summary

The School Management System is a comprehensive Laravel-based application designed to manage all aspects of school operations including student admissions, academics, finance, transport, library, hostel management, and more. This document consolidates all implementation details, features, and technical documentation.

**Current Status:**
- ✅ All core modules implemented and functional
- ✅ Fees management module complete (Phases 1-5)
- ✅ Frontend views implemented with Bootstrap 5
- ✅ Backend services fully functional
- ✅ Testing infrastructure in place
- ✅ Database migrations complete
- 🔄 Ongoing enhancements and refinements

---

## System Overview

### Technology Stack

- **Framework:** Laravel 12.38.1
- **PHP Version:** 8.2.12
- **Database:** MySQL/MariaDB
- **Frontend:** Bootstrap 5.3.0, Blade Templates
- **PDF Generation:** DomPDF (barryvdh/laravel-dompdf)
- **Excel Import/Export:** Maatwebsite/Excel, PhpOffice/PhpSpreadsheet
- **Permissions:** Spatie Permission Package

### Core Modules

1. **Student Management** - Admissions, enrollment, records
2. **Academics** - Classes, streams, subjects, timetable
3. **Finance** - Fees, payments, invoices, receipts
4. **Transport** - Routes, vehicle management
5. **Library** - Book management, borrowing
6. **Hostel** - Boarding management
7. **POS** - Point of sale for school shop
8. **Examinations** - Exam management, report cards
9. **Attendance** - Student and staff attendance
10. **HR** - Staff management

---

## Fees Management Module

### Overview

The Fees Management module is a comprehensive system for managing school fees, payments, invoices, discounts, and financial reporting. It supports multiple fee structures, payment methods, allocation, and audit tracking.

### Module Status: ✅ **COMPLETE**

All core features have been implemented, tested, and documented. The module is production-ready.

---

## Features Implemented

### 1. Votehead Management ✅

**Description:** Manage fee categories and charge types

**Features:**
- ✅ CRUD operations for voteheads
- ✅ Auto-generated codes from names
- ✅ Votehead categories with pre-filled common categories
- ✅ Charge types:
  - `per_student` - Charged every term
  - `once` - Charged once only (new students only)
  - `once_annually` - Charged once per academic year
  - `per_family` - Charged once per family
- ✅ Preferred term for once_annually fees (e.g., textbook fee in Term 1)
- ✅ Mandatory and optional flags
- ✅ Active/inactive status
- ✅ Bulk import from Excel with dropdown validations
- ✅ Excel template generation with existing voteheads

**Database:**
- `voteheads` table with all fields
- `votehead_categories` table
- `preferred_term` field for once_annually fees

**Files:**
- `app/Models/Votehead.php`
- `app/Models/VoteheadCategory.php`
- `app/Http/Controllers/Finance/VoteheadController.php`
- `app/Services/VoteheadImportService.php`
- `database/seeders/VoteheadCategorySeeder.php`

---

### 2. Fee Structures ✅

**Description:** Define fee structures by class, stream, term, and student category

**Features:**
- ✅ Fee structure creation and management
- ✅ Support for classroom-specific structures
- ✅ Stream-specific structures (optional)
- ✅ Term-specific structures
- ✅ **Student category-specific structures** (e.g., staff students, boarding students)
- ✅ Academic year and term foreign keys
- ✅ Versioning support
- ✅ Structure replication to multiple classrooms
- ✅ Active/inactive status
- ✅ Approval workflow
- ✅ Bulk import from Excel
- ✅ Template generation with prefilled data

**Database:**
- `fee_structures` table with `student_category_id`
- Unique constraint: `(classroom_id, academic_year_id, term_id, stream_id, student_category_id, is_active)`

**Files:**
- `app/Models/FeeStructure.php`
- `app/Http/Controllers/Finance/FeeStructureController.php`
- `app/Services/FeeStructureImportService.php`

**Key Method:**
```php
FeeStructure::replicateTo($classroomIds, $academicYearId, $termId, $studentCategoryId)
```

---

### 3. Fee Posting ✅

**Description:** Post fees from structures to student invoices with change tracking

**Features:**
- ✅ Preview posting with color-coded diffs
- ✅ Posting run tracking and history
- ✅ Reversal capability
- ✅ Idempotency checks (prevents double posting)
- ✅ Charge type enforcement:
  - Once-only fees only for newly admitted students
  - Once_annually fees respect preferred_term
  - Per-family fees check family members
- ✅ Before/after snapshots
- ✅ Summary statistics (total changes, net amount)
- ✅ Filter by class, stream, student, votehead

**Database:**
- `fee_posting_runs` table
- `posting_diffs` table

**Files:**
- `app/Services/FeePostingService.php`
- `app/Http/Controllers/Finance/PostingController.php`
- `app/Models/FeePostingRun.php`
- `app/Models/PostingDiff.php`

---

### 4. Invoice Management ✅

**Description:** Generate and manage student invoices

**Features:**
- ✅ Automatic invoice generation from fee structures
- ✅ Invoice editing with inline modals
- ✅ Automatic credit/debit note creation on edits
- ✅ Invoice history tracking
- ✅ Status indicators (paid, partial, unpaid, overdue)
- ✅ Payment tracking and allocation display
- ✅ PDF generation support
- ✅ Family-level invoicing
- ✅ Academic year and term tracking

**Database:**
- `invoices` table
- `invoice_items` table
- `credit_notes` table
- `debit_notes` table

**Files:**
- `app/Models/Invoice.php`
- `app/Models/InvoiceItem.php`
- `app/Services/InvoiceService.php`
- `app/Http/Controllers/Finance/InvoiceController.php`

---

### 5. Payment Management ✅

**Description:** Record and allocate payments to invoices

**Features:**
- ✅ Payment recording with multiple methods
- ✅ Payment allocation to invoice items
- ✅ Auto-allocation (FIFO)
- ✅ Sibling payment sharing across family
- ✅ Overpayment handling and carry-forward
- ✅ Payment methods (Cash, M-Pesa, Bank Transfer, Cheque, etc.)
- ✅ Bank account tracking
- ✅ Receipt number generation
- ✅ Transaction code tracking
- ✅ Unallocated amount tracking

**Database:**
- `payments` table
- `payment_allocations` table
- `payment_methods` table
- `bank_accounts` table

**Files:**
- `app/Models/Payment.php`
- `app/Models/PaymentAllocation.php`
- `app/Services/PaymentAllocationService.php`
- `app/Http/Controllers/Finance/PaymentController.php`

---

### 6. Discount Management ✅

**Description:** Apply discounts to students, voteheads, invoices, or families

**Features:**
- ✅ Multiple discount types:
  - Percentage-based
  - Fixed amount
- ✅ Multiple scopes:
  - Student-level
  - Votehead-specific
  - Invoice-specific
  - Family-level
- ✅ Discount categories:
  - Sibling discount
  - Referral discount
  - Early repayment discount
  - Transport discount
  - Manual/Other
- ✅ Frequency options:
  - Termly
  - Yearly
  - Once
  - Manual
- ✅ Auto-approve option
- ✅ Active date ranges

**Database:**
- `fee_concessions` table (enhanced)

**Files:**
- `app/Services/DiscountService.php`
- `app/Http/Controllers/Finance/DiscountController.php`
- `app/Models/FeeConcession.php`

---

### 7. Receipt Generation ✅

**Description:** Generate professional PDF receipts for payments

**Features:**
- ✅ PDF receipt generation
- ✅ Professional templates with school branding
- ✅ Payment allocations display
- ✅ Total calculations (allocated/unallocated)
- ✅ Narration display
- ✅ Receipt numbering

**Files:**
- `app/Services/ReceiptService.php`
- `resources/views/finance/receipts/pdf/template.blade.php`

---

### 8. Document Numbering ✅

**Description:** Configurable numbering sequences for invoices, receipts, credit/debit notes

**Features:**
- ✅ Configurable prefix and suffix
- ✅ Padding length configuration
- ✅ Reset periods (yearly, monthly, never)
- ✅ Helper methods for each document type

**Database:**
- `document_counters` table

**Files:**
- `app/Services/DocumentNumberService.php`

---

### 9. Audit Logging ✅

**Description:** Track all financial transactions and changes

**Features:**
- ✅ Posting operations logged
- ✅ Payment creation and allocation logged
- ✅ Invoice item edits logged
- ✅ Credit/debit note creation logged
- ✅ Discount creation and application logged
- ✅ User tracking
- ✅ Timestamp tracking

**Files:**
- `app/Models/AuditLog.php`
- Integrated across all services

---

### 10. Bulk Import/Export ✅

**Description:** Import voteheads and fee structures from Excel files

**Features:**
- ✅ Votehead bulk import
  - Excel template with dropdown validations
  - Category dropdowns
  - Charge type dropdowns
  - Pre-filled with existing voteheads
- ✅ Fee structure bulk import
  - Support for classrooms, academic years, terms, streams
  - Student category support
  - Multiple voteheads per structure
  - Template prefilled with reference data

**Files:**
- `app/Services/VoteheadImportService.php`
- `app/Services/FeeStructureImportService.php`

---

### 11. Student Category Integration ✅

**Description:** Support different fee structures for different student categories

**Features:**
- ✅ Student categories linked to fee structures
- ✅ Category-specific fee structures (e.g., staff students, boarding)
- ✅ Students linked to categories
- ✅ Fee posting respects student categories
- ✅ Replication supports category selection

**Database:**
- `student_categories` table
- `students.category_id` field
- `fee_structures.student_category_id` field

**Files:**
- `app/Models/StudentCategory.php`
- Updated in FeeStructure, FeePostingService

---

### 12. Once-Only Fees for New Students ✅

**Description:** Once-only fees charged only to newly admitted students

**Features:**
- ✅ Automatic detection of new students via admission_date
- ✅ Once-only fees only charged to new students
- ✅ Existing students marked as already charged
- ✅ Integration with fee posting logic

**Implementation:**
- `Student::isNewlyAdmitted()` method
- Updated `Votehead::canChargeForStudent()` method

---

### 13. Preferred Term for Once_Annually Fees ✅

**Description:** Specify which term to charge once_annually fees

**Features:**
- ✅ Preferred term field (1, 2, or 3)
- ✅ Fees charged in preferred term regardless of student join date
- ✅ Example: Textbook fee charged in Term 1 even if student joins in Term 2

**Database:**
- `voteheads.preferred_term` field

---

---

## POS (Point of Sale) Module

### Overview

The POS module manages the school shop, allowing students and parents to purchase uniforms, books, supplies, and other items online or in-store.

### Module Status: ✅ **IMPLEMENTED**

### Features Implemented

- ✅ Product management with variants (sizes, colors)
- ✅ Product categories and types
- ✅ Inventory tracking with stock levels
- ✅ Order management (pending, processing, completed, cancelled)
- ✅ Public shop links for students/parents
- ✅ Discount codes and promotions
- ✅ Payment integration
- ✅ Requirement templates integration (link products to class requirements)
- ✅ Bulk product import
- ✅ Product variants (e.g., uniform sizes)
- ✅ Backorder management

**Database Tables:**
- `pos_products` - Product catalog
- `pos_product_variants` - Product variants (sizes, colors)
- `pos_orders` - Customer orders
- `pos_order_items` - Order line items
- `pos_discounts` - Discount codes
- `pos_public_shop_links` - Shareable shop links

**Files:**
- `app/Models/Pos/Product.php`
- `app/Models/Pos/Order.php`
- `app/Models/Pos/ProductVariant.php`
- `app/Models/Pos/Discount.php`
- `app/Models/Pos/PublicShopLink.php`
- `app/Services/PosService.php`
- `app/Http/Controllers/Pos/ProductController.php`
- `app/Http/Controllers/Pos/OrderController.php`
- `app/Http/Controllers/Pos/PublicShopController.php`

---

## Library Management Module

### Overview

The Library module manages books, borrowing, library cards, and fines.

### Module Status: ✅ **IMPLEMENTED**

### Features Implemented

- ✅ Book catalog management
- ✅ Book copies tracking
- ✅ Library card management
- ✅ Book borrowing and returns
- ✅ Fine calculation for overdue books
- ✅ Book reservations
- ✅ Borrowing history
- ✅ Overdue tracking

**Database Tables:**
- `books` - Book catalog
- `book_copies` - Individual book copies
- `library_cards` - Student library cards
- `book_borrowings` - Borrowing records
- `book_reservations` - Reservation records
- `library_fines` - Fine records

**Files:**
- `app/Models/Book.php`
- `app/Models/BookCopy.php`
- `app/Models/LibraryCard.php`
- `app/Models/BookBorrowing.php`
- `app/Services/LibraryService.php`
- `app/Http/Controllers/Library/BookController.php`
- `app/Http/Controllers/Library/BookBorrowingController.php`
- `app/Http/Controllers/Library/LibraryCardController.php`

---

## Hostel Management Module

### Overview

The Hostel module manages boarding facilities, room allocations, and hostel attendance.

### Module Status: ✅ **IMPLEMENTED**

### Features Implemented

- ✅ Hostel management (boys, girls, mixed)
- ✅ Room management with capacity tracking
- ✅ Student allocation to rooms
- ✅ Bed number assignment
- ✅ Hostel attendance tracking
- ✅ Hostel fees management
- ✅ Warden assignment
- ✅ Occupancy tracking

**Database Tables:**
- `hostels` - Hostel facilities
- `hostel_rooms` - Rooms within hostels
- `hostel_allocations` - Student room assignments
- `hostel_attendance` - Attendance records
- `hostel_fees` - Hostel fee structures

**Files:**
- `app/Models/Hostel.php`
- `app/Models/HostelRoom.php`
- `app/Models/HostelAllocation.php`
- `app/Models/HostelAttendance.php`
- `app/Services/HostelService.php`
- `app/Http/Controllers/Hostel/HostelController.php`
- `app/Http/Controllers/Hostel/HostelAllocationController.php`

---

## Transport Management Module

### Overview

The Transport module manages school transport routes, vehicles, trips, and student assignments.

### Module Status: ✅ **IMPLEMENTED**

### Features Implemented

- ✅ Route management
- ✅ Vehicle management
- ✅ Trip scheduling
- ✅ Student assignment to routes
- ✅ Drop-off point management
- ✅ Driver assignment
- ✅ Bulk import of drop-off points

**Database Tables:**
- `routes` - Transport routes
- `vehicles` - School vehicles
- `trips` - Trip schedules
- `student_assignments` - Student route assignments
- `drop_off_points` - Pickup/drop-off locations

**Files:**
- `app/Models/Route.php`
- `app/Models/Vehicle.php`
- `app/Models/Trip.php`
- `app/Models/StudentAssignment.php`
- `app/Models/DropOffPoint.php`
- `app/Http/Controllers/TransportController.php`
- `app/Http/Controllers/VehicleController.php`
- `app/Http/Controllers/RouteController.php`
- `app/Http/Controllers/TripController.php`

---

## Academics Module

### Overview

The Academics module manages classes, subjects, timetables, exams, homework, report cards, and CBC curriculum.

### Module Status: ✅ **IMPLEMENTED**

### Features Implemented

- ✅ Classroom and stream management
- ✅ Subject management
- ✅ Timetable creation and management
- ✅ Exam management (CAT, Midterm, Endterm, SBA, Mock, Quiz)
- ✅ Exam scheduling
- ✅ Mark entry and grading
- ✅ Report card generation
- ✅ Homework and diaries
- ✅ CBC curriculum (Learning Areas, Strands, Substrands, Competencies)
- ✅ Portfolio assessments
- ✅ Student promotion
- ✅ Scheme of work
- ✅ Lesson plans
- ✅ Extra-curricular activities
- ✅ Behavior management
- ✅ Student skills grading

**Database Tables:**
- `classrooms` - Class levels
- `streams` - Streams within classes
- `subjects` - Subject catalog
- `timetables` - Class schedules
- `exams` - Exam definitions
- `exam_schedules` - Exam timetables
- `exam_marks` - Student marks
- `report_cards` - Generated report cards
- `homework` - Homework assignments
- `learning_areas` - CBC learning areas
- `competencies` - CBC competencies
- And many more...

**Files:**
- `app/Models/Academics/Classroom.php`
- `app/Models/Academics/Subject.php`
- `app/Models/Academics/Exam.php`
- `app/Models/Academics/Timetable.php`
- `app/Http/Controllers/Academics/ExamController.php`
- `app/Http/Controllers/Academics/TimetableController.php`
- `app/Http/Controllers/Academics/ReportCardController.php`
- And many more...

---

## HR & Payroll Module

### Overview

The HR module manages staff, payroll, leave, attendance, and staff records.

### Module Status: ✅ **IMPLEMENTED**

### Features Implemented

- ✅ Staff management (profiles, documents, qualifications)
- ✅ Staff categories and departments
- ✅ Job titles and positions
- ✅ Salary structure management
- ✅ Payroll period processing
- ✅ Payroll record generation
- ✅ Payslip generation
- ✅ Leave management (requests, balances, types)
- ✅ Staff attendance tracking
- ✅ Staff advances
- ✅ Custom deductions
- ✅ Statutory deductions (NSSF, NHIF, PAYE)
- ✅ HR analytics dashboard
- ✅ Staff performance reviews
- ✅ Training records

**Database Tables:**
- `staff` - Staff members
- `salary_structures` - Salary configurations
- `payroll_periods` - Payroll periods
- `payroll_records` - Payroll calculations
- `leave_requests` - Leave applications
- `leave_types` - Leave categories
- `staff_leave_balances` - Leave balances
- `staff_attendance` - Attendance records
- `staff_advances` - Salary advances
- `custom_deductions` - Custom deductions
- And more...

**Files:**
- `app/Models/Staff.php`
- `app/Models/SalaryStructure.php`
- `app/Models/PayrollRecord.php`
- `app/Models/LeaveRequest.php`
- `app/Http/Controllers/Hr/StaffController.php`
- `app/Http/Controllers/Hr/PayrollPeriodController.php`
- `app/Http/Controllers/Hr/LeaveRequestController.php`
- And more...

---

## Attendance Module

### Overview

The Attendance module tracks student and staff attendance with reason codes and notifications.

### Module Status: ✅ **IMPLEMENTED**

### Features Implemented

- ✅ Student attendance tracking (present, absent, late)
- ✅ Subject/period-specific attendance
- ✅ Attendance reason codes
- ✅ Excused absences and medical leave
- ✅ Consecutive absence tracking
- ✅ Staff attendance tracking
- ✅ Attendance notifications (SMS/Email)
- ✅ Attendance reports

**Database Tables:**
- `attendance` - Student attendance records
- `staff_attendance` - Staff attendance records
- `attendance_reason_codes` - Absence reason codes
- `attendance_recipients` - Notification recipients

**Files:**
- `app/Models/Attendance.php`
- `app/Models/StaffAttendance.php`
- `app/Models/AttendanceReasonCode.php`
- `app/Http/Controllers/Attendance/AttendanceController.php`
- `app/Http/Controllers/Hr/StaffAttendanceController.php`

---

## Communication Module

### Overview

The Communication module handles SMS, email, announcements, and bulk messaging.

### Module Status: ✅ **IMPLEMENTED**

### Features Implemented

- ✅ SMS sending and scheduling
- ✅ Email sending
- ✅ Communication templates (SMS and Email)
- ✅ Bulk communication (to classes, all students, selected)
- ✅ Announcements management
- ✅ Communication logs
- ✅ Scheduled communications
- ✅ Placeholder replacement in templates

**Database Tables:**
- `communication_templates` - SMS/Email templates
- `communication_logs` - Communication history
- `announcements` - School announcements
- `scheduled_communications` - Scheduled messages
- `sms_logs` - SMS delivery logs

**Files:**
- `app/Models/CommunicationTemplate.php`
- `app/Models/CommunicationLog.php`
- `app/Models/Announcement.php`
- `app/Services/CommunicationService.php`
- `app/Http/Controllers/CommunicationController.php`
- `app/Http/Controllers/Communication/BulkCommunicationController.php`

---

## Inventory Module

### Overview

The Inventory module manages school inventory items, requirements, and requisitions.

### Module Status: ✅ **IMPLEMENTED**

### Features Implemented

- ✅ Inventory item management
- ✅ Requirement types (uniforms, books, supplies)
- ✅ Requirement templates (by class)
- ✅ Student requirements tracking
- ✅ Requisition management (request, approve, fulfill)
- ✅ Inventory transactions
- ✅ Stock tracking

**Database Tables:**
- `inventory_items` - Inventory catalog
- `requirement_types` - Requirement categories
- `requirement_templates` - Class requirements
- `student_requirements` - Student requirement fulfillment
- `requisitions` - Requisition requests
- `requisition_items` - Requisition line items
- `inventory_transactions` - Stock movements

**Files:**
- `app/Models/InventoryItem.php`
- `app/Models/RequirementType.php`
- `app/Models/RequirementTemplate.php`
- `app/Models/Requisition.php`
- `app/Http/Controllers/Inventory/InventoryItemController.php`
- `app/Http/Controllers/Inventory/RequisitionController.php`

---

## Student Management Module

### Overview

The Student Management module handles admissions, student records, families, and student lifecycle.

### Module Status: ✅ **IMPLEMENTED**

### Features Implemented

- ✅ Online admissions
- ✅ Student registration and enrollment
- ✅ Student categories
- ✅ Family management
- ✅ Sibling relationships
- ✅ Student medical records
- ✅ Academic history
- ✅ Disciplinary records
- ✅ Extracurricular activities
- ✅ Student promotion
- ✅ Alumni management
- ✅ Student documents

**Database Tables:**
- `students` - Student records
- `student_categories` - Student categories
- `families` - Family groups
- `student_siblings` - Sibling relationships
- `online_admissions` - Admission applications
- `student_medical_records` - Medical information
- `student_academic_history` - Academic records
- `student_disciplinary_records` - Disciplinary actions
- And more...

**Files:**
- `app/Models/Student.php`
- `app/Models/StudentCategory.php`
- `app/Models/Family.php`
- `app/Models/OnlineAdmission.php`
- `app/Http/Controllers/Students/StudentController.php`
- `app/Http/Controllers/Students/OnlineAdmissionController.php`

---

## Features Pending

### High Priority

- [ ] **Fee Reminders** - Automated email/SMS reminders for overdue fees
- [ ] **Payment Plans** - Installment payment plans for students
- [ ] **Fee Waivers** - Complete fee waiver management
- [ ] **Financial Reports** - Comprehensive financial reporting and analytics
- [ ] **Fee Structure Templates** - Save and reuse fee structure templates
- [ ] **Online Payment Gateway Integration** - Complete integration with payment gateways
- [ ] **Mobile App API** - RESTful API for mobile applications
- [ ] **Fee Statement Generation** - PDF statements for students/parents

### Medium Priority

- [ ] **Multi-Currency Support** - Support for multiple currencies
- [ ] **Fee Structure Comparison** - Compare fee structures across years
- [ ] **Bulk Fee Adjustments** - Adjust fees for multiple students at once
- [ ] **Fee Refund Management** - Process and track fee refunds
- [ ] **Integration with Accounting Software** - Export to QuickBooks, Xero, etc.
- [ ] **Advanced Reporting** - Custom report builder
- [ ] **Fee Projections** - Forecast fee collections

### Low Priority

- [ ] **Fee Notifications via WhatsApp** - WhatsApp integration for notifications
- [ ] **Fee Structure Approval Workflow** - Multi-level approval process
- [ ] **Fee Structure Version Comparison** - Visual diff of structure versions
- [ ] **Automated Fee Escalation** - Automatic fee increases based on rules
- [ ] **Fee Structure Analytics** - Analyze fee structure effectiveness

---

## Technical Architecture

### Directory Structure

```
app/
├── Http/
│   └── Controllers/
│       └── Finance/
│           ├── VoteheadController.php
│           ├── FeeStructureController.php
│           ├── PostingController.php
│           ├── InvoiceController.php
│           ├── PaymentController.php
│           ├── DiscountController.php
│           └── ...
├── Models/
│   ├── Votehead.php
│   ├── FeeStructure.php
│   ├── Invoice.php
│   ├── Payment.php
│   ├── FeeConcession.php
│   └── ...
└── Services/
    ├── FeePostingService.php
    ├── PaymentAllocationService.php
    ├── DiscountService.php
    ├── InvoiceService.php
    ├── ReceiptService.php
    ├── VoteheadImportService.php
    └── FeeStructureImportService.php

database/
├── migrations/
│   ├── 2025_08_04_082122_create_voteheads_table.php
│   ├── 2025_08_13_081503_create_fee_structures_table.php
│   ├── 2025_12_10_100008_enhance_fee_structures_table.php
│   ├── 2025_12_15_000005_add_student_category_to_fee_structures.php
│   ├── 2025_12_15_000006_add_preferred_term_to_voteheads.php
│   └── ...

resources/
└── views/
    └── finance/
        ├── voteheads/
        ├── fee_structures/
        ├── posting/
        ├── invoices/
        ├── payments/
        └── discounts/
```

---

## Database Schema

### Core Tables

#### `voteheads`
- `id`, `code`, `name`, `description`
- `category` (string), `category_id` (FK to votehead_categories)
- `is_mandatory`, `is_optional`, `is_active` (boolean)
- `charge_type` (enum), `preferred_term` (integer, nullable)
- `timestamps`

#### `fee_structures`
- `id`, `name`
- `classroom_id` (FK), `academic_year_id` (FK), `term_id` (FK)
- `stream_id` (FK, nullable), `student_category_id` (FK, nullable)
- `version`, `parent_structure_id` (FK, nullable)
- `is_active`, `created_by` (FK), `approved_by` (FK), `approved_at`
- `year` (integer, backward compatibility)
- `timestamps`

#### `fee_charges`
- `id`, `fee_structure_id` (FK), `votehead_id` (FK)
- `term` (1, 2, or 3), `amount` (decimal)
- `timestamps`

#### `invoices`
- `id`, `invoice_number`, `student_id` (FK), `family_id` (FK, nullable)
- `academic_year_id` (FK), `term_id` (FK), `year` (integer)
- `status`, `total`, `paid_amount`, `balance`
- `due_date`, `posted_at`, `fee_posting_run_id` (FK, nullable)
- `timestamps`

#### `payments`
- `id`, `payment_number`, `receipt_number`
- `student_id` (FK), `family_id` (FK, nullable)
- `amount`, `unallocated_amount`, `payment_method_id` (FK)
- `bank_account_id` (FK, nullable), `transaction_code`
- `payment_date`, `narration`
- `timestamps`

#### `payment_allocations`
- `id`, `payment_id` (FK), `invoice_item_id` (FK)
- `amount` (decimal), `allocated_at`
- `timestamps`

### Finance Module Supporting Tables

- `student_categories` - Student category definitions
- `votehead_categories` - Votehead category groupings
- `fee_posting_runs` - Posting operation tracking
- `posting_diffs` - Change tracking for postings
- `fee_structure_versions` - Version history
- `fee_concessions` - Discount definitions
- `credit_notes`, `debit_notes` - Adjustment tracking
- `payment_methods`, `bank_accounts` - Payment configuration
- `document_counters` - Numbering sequences

### POS Module Tables

- `pos_products` - Product catalog
- `pos_product_variants` - Product variants
- `pos_orders` - Customer orders
- `pos_order_items` - Order line items
- `pos_discounts` - Discount codes
- `pos_public_shop_links` - Shareable shop links

### Library Module Tables

- `books` - Book catalog
- `book_copies` - Individual copies
- `library_cards` - Student library cards
- `book_borrowings` - Borrowing records
- `book_reservations` - Reservations
- `library_fines` - Fine records

### Hostel Module Tables

- `hostels` - Hostel facilities
- `hostel_rooms` - Rooms
- `hostel_allocations` - Student assignments
- `hostel_attendance` - Attendance records
- `hostel_fees` - Fee structures

### Transport Module Tables

- `routes` - Transport routes
- `vehicles` - School vehicles
- `trips` - Trip schedules
- `student_assignments` - Route assignments
- `drop_off_points` - Pickup/drop-off locations

### Academics Module Tables

- `classrooms` - Class levels
- `streams` - Streams within classes
- `subjects` - Subject catalog
- `timetables` - Class schedules
- `exams` - Exam definitions
- `exam_schedules` - Exam timetables
- `exam_marks` - Student marks
- `report_cards` - Generated report cards
- `homework` - Homework assignments
- `learning_areas` - CBC learning areas
- `competencies` - CBC competencies
- And many more...

### HR Module Tables

- `staff` - Staff members
- `salary_structures` - Salary configurations
- `payroll_periods` - Payroll periods
- `payroll_records` - Payroll calculations
- `leave_requests` - Leave applications
- `leave_types` - Leave categories
- `staff_leave_balances` - Leave balances
- `staff_attendance` - Attendance records
- `staff_advances` - Salary advances
- `custom_deductions` - Custom deductions
- And more...

### Attendance Module Tables

- `attendance` - Student attendance
- `staff_attendance` - Staff attendance
- `attendance_reason_codes` - Absence reasons
- `attendance_recipients` - Notification recipients

### Communication Module Tables

- `communication_templates` - SMS/Email templates
- `communication_logs` - Communication history
- `announcements` - School announcements
- `scheduled_communications` - Scheduled messages
- `sms_logs` - SMS delivery logs

### Inventory Module Tables

- `inventory_items` - Inventory catalog
- `requirement_types` - Requirement categories
- `requirement_templates` - Class requirements
- `student_requirements` - Student fulfillment
- `requisitions` - Requisition requests
- `requisition_items` - Requisition line items
- `inventory_transactions` - Stock movements

### Student Management Tables

- `students` - Student records
- `student_categories` - Student categories
- `families` - Family groups
- `student_siblings` - Sibling relationships
- `online_admissions` - Admission applications
- `student_medical_records` - Medical information
- `student_academic_history` - Academic records
- `student_disciplinary_records` - Disciplinary actions

---

## API & Services

### Service Classes

#### FeePostingService
- `previewWithDiffs()` - Preview posting with change tracking
- `commitWithTracking()` - Commit posting with run tracking
- `reversePostingRun()` - Reverse a posting operation

#### PaymentAllocationService
- `allocatePayment()` - Manual payment allocation
- `autoAllocate()` - Automatic FIFO allocation
- `sharePaymentAcrossSiblings()` - Family payment sharing
- `handleOverpayment()` - Overpayment/carry-forward

#### DiscountService
- `applyDiscountsToInvoice()` - Apply during posting
- `createDiscount()` - Create new discount
- `applySiblingDiscount()` - Auto-apply sibling discounts

#### InvoiceService
- `updateItemAmount()` - Update item with auto credit/debit notes
- `applyDiscount()` - Apply discount to invoice/item

#### ReceiptService
- `generateReceipt()` - Generate PDF receipt
- `downloadReceipt()` - Download PDF response

#### VoteheadImportService
- `generateExcelTemplate()` - Generate Excel template with dropdowns
- `processImport()` - Process imported voteheads

#### FeeStructureImportService
- `generateTemplate()` - Generate CSV template
- `processImport()` - Process imported fee structures

### Other Module Services

#### PosService
- `getCart()` - Get shopping cart
- `addToCart()` - Add item to cart
- `removeFromCart()` - Remove item from cart
- `applyDiscount()` - Apply discount code
- `checkout()` - Process order

#### LibraryService
- `borrowBook()` - Issue book to student
- `returnBook()` - Return borrowed book
- `renewBook()` - Renew borrowing period
- `calculateFine()` - Calculate overdue fines

#### HostelService
- `allocateStudent()` - Allocate student to room
- `deallocateStudent()` - Remove student from room
- `getAvailableRooms()` - Get available rooms

#### CommunicationService
- `sendSMS()` - Send SMS message
- `sendEmail()` - Send email
- `sendBulk()` - Send bulk messages
- `scheduleCommunication()` - Schedule future message

---

## Testing & Quality Assurance

### Test Coverage

#### Unit Tests ✅
- `FeePostingServiceTest.php` - 5 test methods
- `PaymentAllocationServiceTest.php` - 4 test methods

#### Feature Tests ✅
- `FeePostingTest.php` - 4 test methods
- `PaymentTest.php` - 6 test methods
- `DiscountTest.php` - 3 test methods

**Total Test Methods:** 22

### Test Factories ✅

All models have factories:
- PaymentFactory, InvoiceFactory, InvoiceItemFactory
- VoteheadFactory, FeeConcessionFactory, FeePostingRunFactory
- PaymentMethodFactory, FeeStructureFactory
- StudentFactory, AcademicYearFactory, TermFactory
- ClassroomFactory, StreamFactory, FamilyFactory

### QA Checklist

- ✅ All services use transactions
- ✅ Audit logging implemented
- ✅ Error handling in place
- ✅ Idempotency checks
- ✅ Validation rules defined
- ✅ Relationship constraints enforced

---

## Deployment & Maintenance

### Prerequisites

- PHP 8.2+
- MySQL 5.7+ / MariaDB 10.3+
- Composer
- Node.js (for frontend assets)

### Installation Steps

1. **Clone repository**
   ```bash
   git clone <repository-url>
   cd school-management-system2
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database setup**
   ```bash
   php artisan migrate
   php artisan db:seed --class=VoteheadCategorySeeder
   php artisan db:seed --class=PaymentMethodSeeder
   php artisan db:seed --class=BankAccountSeeder
   php artisan db:seed --class=DocumentCounterSeeder
   ```

5. **Storage link**
   ```bash
   php artisan storage:link
   ```

6. **Build frontend**
   ```bash
   npm run build
   ```

### Maintenance

- Regular database backups recommended
- Monitor audit logs for unusual activity
- Keep Laravel and dependencies updated
- Review and optimize database queries periodically

---

## Change Log

### December 15, 2025

**Added:**
- Student category support in fee structures
- Preferred term for once_annually fees
- Once-only fee logic for new students only
- Enhanced fee structure replication with category support
- Votehead categories with seeder
- Excel import with dropdown validations
- Removed `default_amount` from voteheads

**Fixed:**
- Syntax error in FeeStructureController
- Fee posting respects student categories
- Invoice generation respects preferred_term
- Import services updated for categories

### December 10, 2025

**Added:**
- Phase 5 completion (Testing & QA)
- Model factories
- Unit and feature tests
- Audit logging enhancements
- Navigation menu updates
- Frontend views (Phase 4)

**Fixed:**
- Posting diff display (action vs change_type)
- Receipt service enhancements
- Invoice service audit log references

### December 8-9, 2025

**Added:**
- Phase 3 services (FeePostingService, PaymentAllocationService, DiscountService, ReceiptService)
- Phase 2 database enhancements
- Phase 1 audit report

---

## Documentation Maintenance

**This document should be updated:**
- After each major feature implementation
- After bug fixes that affect functionality
- When new requirements are identified
- After deployment to production
- Before git commits/pushes

**Update Process:**
1. Make code changes
2. Update relevant sections in this document
3. Update Change Log with date and details
4. Commit changes with descriptive messages
5. Push to repository

---

## Support & Contact

For issues, questions, or contributions, please refer to the project repository or contact the development team.

---

**Document Version:** 1.0  
**Last Updated:** December 15, 2025  
**Next Review:** As needed

