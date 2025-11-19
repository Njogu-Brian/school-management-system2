# Inventory & Requirements Management System

## Overview
Complete inventory management and student requirements collection system with communication integration and activity logging.

## Features Implemented

### 1. Inventory Management
- **Inventory Items**: Track all school inventory (stationery, books, supplies)
- **Categories**: Organize items by category
- **Stock Levels**: Monitor quantities and set minimum stock levels
- **Transactions**: Track all inventory movements (in/out/adjustments)
- **Location Tracking**: Store location information

### 2. Requirements Management
- **Requirement Types**: Define types (pencils, pens, storybooks, workbooks, tissues, glue, etc.)
- **Requirement Templates**: Admin sets requirements per class/term
  - Brand specification
  - Quantity per student
  - Student type (new/existing/both)
  - Leave with teacher vs. student
  - Verification only items
- **Student Requirements**: Track individual student requirement collection
- **Status Tracking**: pending, partial, complete, missing

### 3. Teacher Features
- View requirements for assigned classes
- Collect requirements from students
- Mark items as collected/missing
- Items left with teacher automatically logged to inventory

### 4. Communication Integration
- Automatic SMS/Email to parents when requirements collected
- Notifies if all collected or missing items listed
- Uses existing communication module

### 5. Requisition System
- Teachers can request requisitions
- Admin can approve/reject requisitions
- Track requisition items and fulfillment

### 6. Activity Logging
- Logs all user actions (login, logout, create, update, delete)
- Tracks user, date, time, IP, route, method
- Available for all system functions

## Database Tables
1. `inventory_items` - Main inventory items
2. `requirement_types` - Types of requirements
3. `requirement_templates` - Templates per class/term
4. `student_requirements` - Individual student requirements
5. `requisitions` - Requisition requests
6. `requisition_items` - Items in requisitions
7. `inventory_transactions` - All inventory movements
8. `activity_logs` - System activity logging

## Routes Structure
- `/inventory/*` - Inventory management
- `/requirements/*` - Requirements management
- `/requisitions/*` - Requisition system
- `/activity-logs` - Activity log viewing

## Timezone Fix
- Set to `Africa/Nairobi` in `config/app.php`
- Uses `APP_TIMEZONE` environment variable

## Next Steps
1. Run migrations: `php artisan migrate`
2. Create permissions for inventory management
3. Add navigation links
4. Test communication integration

