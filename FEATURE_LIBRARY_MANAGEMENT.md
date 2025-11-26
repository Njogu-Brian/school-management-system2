# Library Management System

## Overview

Complete library management system for cataloging books, managing borrowing/returning, library cards, fines, and reservations.

## Architecture

### Database Schema

```sql
books:
  - id
  - isbn
  - title
  - author
  - publisher
  - publication_year
  - category
  - language
  - total_copies
  - available_copies
  - location
  - description
  - cover_image

book_copies:
  - id
  - book_id
  - copy_number
  - barcode
  - status (available, borrowed, reserved, lost, damaged)
  - condition
  - purchase_date
  - purchase_price

library_cards:
  - id
  - student_id
  - card_number (unique)
  - issued_date
  - expiry_date
  - status (active, expired, suspended, lost)
  - max_borrow_limit
  - current_borrow_count

book_borrowings:
  - id
  - book_copy_id
  - library_card_id
  - student_id
  - borrowed_date
  - due_date
  - returned_date
  - status (borrowed, returned, overdue, lost)
  - fine_amount
  - fine_paid
  - notes

book_reservations:
  - id
  - book_id
  - student_id
  - library_card_id
  - reserved_date
  - expiry_date
  - status (pending, fulfilled, cancelled, expired)
  - notified_at

library_fines:
  - id
  - borrowing_id
  - student_id
  - amount
  - reason (overdue, lost, damaged)
  - status (pending, paid, waived)
  - paid_at
```

## Implementation Plan

### Phase 1: Core Infrastructure
- [ ] Create migrations
- [ ] Create models
- [ ] Create relationships

### Phase 2: Book Management
- [ ] Book CRUD
- [ ] Book copy management
- [ ] Barcode generation
- [ ] Book search

### Phase 3: Library Cards
- [ ] Card issuance
- [ ] Card management
- [ ] Card renewal

### Phase 4: Borrowing System
- [ ] Borrow book
- [ ] Return book
- [ ] Renewal
- [ ] Overdue tracking

### Phase 5: Fines & Reservations
- [ ] Fine calculation
- [ ] Fine payment
- [ ] Book reservation
- [ ] Reservation notifications

### Phase 6: Reports
- [ ] Borrowing reports
- [ ] Overdue reports
- [ ] Popular books
- [ ] Fine reports

