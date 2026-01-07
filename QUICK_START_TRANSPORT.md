# Quick Start Guide - Transport Import & Daily Lists

## 🚀 Getting Started in 5 Minutes

### Step 1: Access the System
1. Log in to your school management system
2. Navigate to **Transport Management** from the main menu
3. You'll see two new buttons:
   - **Import Assignments** (blue button)
   - **Daily List** (blue button)

---

## 📥 Importing Transport Assignments

### Quick Import Process

1. **Click "Import Assignments"**
   
2. **Download the Template**
   - Click "Download Template" button
   - Open the Excel file
   - You'll see 5 columns with sample data

3. **Fill Your Data**
   ```
   ADMISSION NO | NAME           | ROUTE  | CLASS            | VEHICLE
   RKS438       | Student Name   | REGEN  | FOUNDATION LOVE  | KDR TRIP 1
   ```
   
   **Important:**
   - ADMISSION NO: Must match exactly with system
   - ROUTE: Drop-off location (or "OWN" if student uses own transport)
   - VEHICLE: Format must be "{VEHICLE} TRIP {NUMBER}" (e.g., "KDR TRIP 1")

4. **Upload & Preview**
   - Click "Choose File" and select your Excel
   - Click "Preview Import"
   - Review the preview screen

5. **Handle Conflicts (if any)**
   - If a student's route differs from the system, you'll see a yellow warning
   - Choose either:
     - "Use System" - Keep existing route
     - "Use Excel" - Update to new route
   - Click "Resolve Conflicts & Import"

6. **Done!**
   - You'll see a success message with counts
   - Trips and drop-off points are automatically created

---

## 📋 Generating Daily Transport Lists

### Quick List Generation

1. **Click "Daily List"**

2. **Select Date**
   - Defaults to today
   - Change if needed

3. **Apply Filters (Optional)**
   - Vehicle: Show only one vehicle
   - Class: Show only one class
   - Or leave blank for all

4. **Click "Apply Filters"**

5. **View Results**
   - Students grouped by vehicle
   - Only shows students marked "present" today

6. **Download or Print**
   - **Download Excel**: Click "Download Excel" button
   - **Print All**: Click "Print All" for all vehicles
   - **Print Single Vehicle**: Click "Print Vehicle List" on any vehicle card

---

## 💡 Pro Tips

### For Importing
- ✅ Create vehicles in the system BEFORE importing
- ✅ Use the template to avoid format errors
- ✅ "OWN" in VEHICLE column = student uses own transport (will be skipped)
- ✅ Trips are auto-created (TRIP 1, TRIP 2, TRIP 3)
- ✅ Drop-off points are auto-created from ROUTE column

### For Daily Lists
- ✅ Mark attendance BEFORE generating lists
- ✅ Only "present" students appear on the list
- ✅ Print separate lists for each vehicle/driver
- ✅ Use signature sections on printed lists

---

## 🎯 Common Scenarios

### Scenario 1: First Time Setup
```
1. Create vehicles: KDR, KCB, KAQ, KCA, KCF
2. Download template
3. Fill in all students
4. Import
5. Resolve any conflicts
6. Done! Trips and routes created automatically
```

### Scenario 2: Daily Morning Routine
```
1. Teachers mark attendance
2. Go to Transport → Daily List
3. Select today's date
4. Print lists for each vehicle
5. Give to drivers
6. Drivers check off students as they board
```

### Scenario 3: Updating Routes
```
1. Prepare Excel with updated routes
2. Import (preview will show conflicts)
3. Choose "Use Excel" for students with new routes
4. Import completes with updates
```

---

## 📊 What Gets Created Automatically

### Trips
Format: `{VEHICLE} TRIP {NUMBER}`
- Example: "KDR TRIP 1", "KCB TRIP 2"
- Direction: Evening (drop-off)
- Days: Monday to Friday

### Drop-off Points
Created from ROUTE column
- Example: "REGEN", "RUKUBI", "MUTHURE"
- Reused if already exists

### Student Assignments
Links students to:
- Evening trip
- Drop-off point

---

## ⚠️ Troubleshooting

| Problem | Solution |
|---------|----------|
| "Vehicle not found" | Create the vehicle first in Transport → Add Vehicle |
| "Student not found" | Check admission number is correct |
| No students in daily list | Mark attendance first, or check date |
| Import errors | Download template and match format exactly |

---

## 📞 Need Help?

1. **View Import History**: Import page → Recent Imports section
2. **Check Logs**: Click "View" on any import to see detailed errors
3. **User Guide**: See `TRANSPORT_IMPORT_GUIDE.md` for detailed documentation

---

## ✅ Checklist for First Use

- [ ] Vehicles created (KDR, KCB, KAQ, KCA, KCF)
- [ ] Template downloaded
- [ ] Excel file prepared with student data
- [ ] Import preview checked
- [ ] Conflicts resolved
- [ ] Import completed successfully
- [ ] Daily list tested
- [ ] Print functionality tested

---

**You're all set! Start importing and managing transport efficiently! 🚌**

