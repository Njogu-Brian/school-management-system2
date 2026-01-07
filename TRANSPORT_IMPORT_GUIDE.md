# Transport Import & Daily List System - User Guide

## Overview

This system allows you to:
1. **Import transport assignments** from Excel files
2. **Resolve route conflicts** when Excel data differs from system data
3. **Download daily transport lists** for present students only
4. **Print vehicle-specific lists** for drivers

---

## 1. Importing Transport Assignments

### Accessing the Import Feature

1. Navigate to **Transport Management** (from the main menu)
2. Click the **"Import Assignments"** button (top right)

### Excel File Format

Your Excel file must have these columns:

| Column | Description | Example |
|--------|-------------|---------|
| **ADMISSION NO** | Student's admission number (required) | RKS438 |
| **NAME** | Student's full name (for reference) | Chrissy Njeri |
| **ROUTE** | Drop-off point name | REGEN, RUKUBI, OWN |
| **CLASS** | Student's class (for reference) | FOUNDATION LOVE |
| **VEHICLE** | Vehicle and trip information | KDR TRIP 1, KCB TRIP 2, OWN |

### Important Notes

- **Students marked as "OWN"** will be skipped (they use their own transport)
- **Vehicles must exist** in the system before importing (KDR, KCB, KAQ, KCA, KCF)
- **Trips will be auto-created** (TRIP 1, TRIP 2, TRIP 3) if they don't exist
- **Drop-off points will be auto-created** if they don't exist
- **Evening assignments only** - This import is specifically for evening drop-off

### Import Process

#### Step 1: Download Template
1. Click **"Download Template"** to get a sample Excel file
2. Fill in your student data following the format

#### Step 2: Upload & Preview
1. Click **"Choose File"** and select your Excel file
2. Click **"Preview Import"**
3. The system will analyze your file and show:
   - ✅ **Ready to import** (green rows)
   - ⚠️ **Conflicts** (yellow rows) - route differs from system
   - ❌ **Errors** (red rows) - invalid data
   - ⏭️ **Skipped** (gray rows) - students using own transport

#### Step 3: Resolve Conflicts
If there are route conflicts:
1. Review each conflict in the **"Route Conflicts"** section
2. For each student, choose:
   - **Use System** - Keep the existing route in the database
   - **Use Excel** - Update to the route from your Excel file
3. Click **"Resolve Conflicts & Import"**

#### Step 4: Complete Import
- If no conflicts, click **"Proceed with Import"**
- View the success message showing:
  - Created assignments
  - Updated assignments
  - Skipped entries
  - Errors (if any)

### Viewing Import History
- Recent imports are shown at the bottom of the import page
- Click **"View"** to see detailed logs of any import
- Logs include all errors and conflicts that occurred

---

## 2. Daily Transport List

### Accessing Daily Transport Lists

1. Navigate to **Transport Management**
2. Click **"Daily List"** button (top right)

### Features

The daily transport list shows **only present students** with transport assignments for the selected date.

#### Filtering Options

- **Date** - Select the date (defaults to today)
- **Vehicle** - Filter by specific vehicle (optional)
- **Class** - Filter by classroom (optional)

#### Viewing the List

Students are grouped by vehicle, showing:
- Admission number
- Student name
- Class
- Trip name
- Drop-off point

### Downloading Options

#### 1. Download Excel
- Click **"Download Excel"** to get a spreadsheet
- Contains all filtered students
- Useful for record-keeping and sharing

#### 2. Print All Vehicles
- Click **"Print All"** to generate a PDF
- Shows all vehicles and their students
- Organized by vehicle for easy distribution

#### 3. Print Single Vehicle
- Click **"Print Vehicle List"** on any vehicle card
- Generates a PDF for that specific vehicle only
- Includes:
  - Vehicle information
  - Driver name
  - Students grouped by trip
  - Signature sections for driver and supervisor
  - Checkboxes for marking student pickup

---

## 3. Workflow Example

### Scenario: Importing Evening Transport Assignments

1. **Prepare Excel File**
   ```
   ADMISSION NO | NAME              | ROUTE    | CLASS            | VEHICLE
   RKS438       | Chrissy Njeri     | REGEN    | FOUNDATION LOVE  | KDR TRIP 1
   RKS644       | Eileen Sitati     | RUKUBI   | FOUNDATION LOVE  | KCB TRIP 1
   RKS582       | Praise Wanjiku    | MUTHURE  | FOUNDATION LOVE  | KCB TRIP 2
   RKS123       | John Doe          | OWN      | FOUNDATION PEACE | OWN
   ```

2. **Import Process**
   - Upload file
   - System finds RKS123 has "OWN" → Skips
   - System finds RKS438 already has route "RUKUBI" but Excel says "REGEN" → Conflict!
   - You choose "Use Excel" to update to REGEN
   - Import completes: 2 created, 1 updated, 1 skipped

3. **Generate Daily List**
   - Select today's date
   - System checks attendance
   - Only shows students marked "present"
   - Groups by vehicle (KDR, KCB)
   - Print lists for drivers

---

## 4. Technical Details

### Database Structure

- **Trips** - Auto-created with format: "{VEHICLE} TRIP {NUMBER}"
  - Example: "KDR TRIP 1", "KCB TRIP 2"
  - Direction: "evening"
  - Days: Monday-Friday

- **Drop-off Points** - Auto-created from ROUTE column
  - Example: "REGEN", "RUKUBI", "MUTHURE"

- **Student Assignments** - Links students to trips and drop-off points
  - `evening_trip_id` - The trip for evening drop-off
  - `evening_drop_off_point_id` - The drop-off location

### Import Logs
All imports are logged with:
- Filename and timestamp
- User who performed the import
- Success/error counts
- Detailed error messages
- Conflict resolutions

---

## 5. Troubleshooting

### Common Issues

**Issue: "Vehicle not found"**
- **Solution**: Create the vehicle in the system first (Transport → Add Vehicle)

**Issue: "Student not found"**
- **Solution**: Verify the admission number is correct and student is not archived

**Issue: "Route conflict"**
- **Solution**: Review and choose which route to use during preview

**Issue: "No students in daily list"**
- **Solution**: 
  - Check that attendance has been marked for the date
  - Verify students have transport assignments
  - Check filters (vehicle/class) aren't too restrictive

### Best Practices

1. **Before Importing**
   - Ensure all vehicles exist in the system
   - Verify admission numbers are correct
   - Use the template for consistent formatting

2. **During Import**
   - Always preview before importing
   - Carefully review conflicts
   - Check error messages for invalid data

3. **Daily Lists**
   - Generate lists after attendance is marked
   - Print separate lists for each vehicle
   - Keep printed lists for driver signatures

---

## 6. Quick Reference

### Navigation
- **Import**: Transport → Import Assignments
- **Daily List**: Transport → Daily List
- **View History**: Import page → Recent Imports section

### File Formats
- **Import**: .xlsx, .xls, .csv (max 10MB)
- **Export**: .xlsx (Excel)
- **Print**: .pdf

### Permissions
Required role: Super Admin, Admin, Secretary, or Driver

---

## Support

For technical issues or questions, contact your system administrator.

**Version**: 1.0  
**Last Updated**: January 7, 2026

