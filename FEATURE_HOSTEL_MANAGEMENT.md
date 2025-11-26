# Hostel Management System

## Overview

Complete hostel/dormitory management system for boarding students including room allocation, hostel fees, mess management, and hostel attendance.

## Architecture

### Database Schema

```sql
hostels:
  - id
  - name
  - type (boys, girls, mixed)
  - capacity
  - current_occupancy
  - warden_id (staff_id)
  - location
  - description

hostel_rooms:
  - id
  - hostel_id
  - room_number
  - room_type (single, double, triple, dormitory)
  - capacity
  - current_occupancy
  - floor
  - status (available, occupied, maintenance, closed)

hostel_allocations:
  - id
  - student_id
  - hostel_id
  - room_id
  - bed_number
  - allocation_date
  - deallocation_date
  - status (active, completed, cancelled)
  - notes

hostel_fees:
  - id
  - hostel_id
  - academic_year_id
  - term_id
  - fee_type (accommodation, mess, utilities, other)
  - amount
  - description

hostel_attendance:
  - id
  - student_id
  - hostel_id
  - date
  - check_in_time
  - check_out_time
  - status (present, absent, late)
  - notes

mess_menus:
  - id
  - hostel_id
  - meal_type (breakfast, lunch, dinner, snack)
  - menu_date
  - items (JSON)
  - prepared_by (staff_id)

mess_subscriptions:
  - id
  - student_id
  - hostel_id
  - meal_plan (full, breakfast_only, lunch_dinner, custom)
  - start_date
  - end_date
  - status (active, suspended, cancelled)
  - monthly_fee
```

## Implementation Plan

### Phase 1: Core Infrastructure
- [ ] Create migrations
- [ ] Create models
- [ ] Create relationships

### Phase 2: Hostel & Room Management
- [ ] Hostel CRUD
- [ ] Room management
- [ ] Room allocation
- [ ] Capacity tracking

### Phase 3: Hostel Fees
- [ ] Fee structure
- [ ] Fee invoicing
- [ ] Payment tracking

### Phase 4: Mess Management
- [ ] Menu planning
- [ ] Meal subscriptions
- [ ] Meal tracking

### Phase 5: Hostel Attendance
- [ ] Check-in/check-out
- [ ] Attendance tracking
- [ ] Reports

