# Quick Setup Guide - Senior Teacher Role

## Overview
This guide will help you quickly set up and start using the Senior Teacher role in your school management system.

## Prerequisites
- School Management System installed and running
- Admin access to the system
- PHP and Laravel environment set up

## 5-Minute Setup

### Step 1: Run Database Migrations (1 minute)
Open your terminal and navigate to your project directory:

```bash
php artisan migrate
```

This creates two new tables:
- `senior_teacher_classrooms` (links senior teachers to supervised classrooms)
- `senior_teacher_staff` (links senior teachers to supervised staff)

### Step 2: Seed Permissions (1 minute)
Run the permissions seeder:

```bash
php artisan db:seed --class=SeniorTeacherPermissionsSeeder
```

This creates:
- The "Senior Teacher" role
- All necessary permissions
- Proper permission assignments

### Step 3: Assign the Role (1 minute)
1. Log in as Admin
2. Navigate to: **HR → Staff**
3. Select a staff member who should be a senior teacher
4. Edit their profile
5. In the "Roles" section, assign **"Senior Teacher"**
6. Save changes

### Step 4: Assign Supervised Classes (1 minute)
1. Navigate to: **HR → Senior Teacher Assignments**
2. Click **"Manage Assignments"** for the senior teacher
3. Check the boxes for classrooms they should supervise
4. Click **"Update Supervised Classrooms"**

### Step 5: Assign Supervised Staff (1 minute)
On the same page:
1. Scroll to the "Supervised Staff" section
2. Use the search box to find staff members
3. Check the boxes for staff they should supervise
4. Click **"Update Supervised Staff"**

## Testing the Setup

### As Administrator
1. Go to **HR → Senior Teacher Assignments**
2. Verify the senior teacher appears in the list
3. Verify assignment counts are correct

### As Senior Teacher
1. Log out and log back in as the senior teacher
2. You should see a new "Senior Teacher Dashboard"
3. Check the navigation menu - you should see:
   - Supervised Classrooms
   - Supervised Staff
   - All Students
   - Fee Balances (view only)
4. Verify you can:
   - View supervised classroom details
   - See students from supervised classes
   - Mark attendance
   - View fee balances (but not collect fees)
5. Verify you CANNOT:
   - Create new students
   - Access HR/Payroll sections
   - Collect fees or issue invoices

## Common Use Cases

### Use Case 1: Department Head
**Scenario**: Math department head supervises all math teachers

**Setup**:
1. Assign "Senior Teacher" role to department head
2. Assign all math classes to their supervision
3. Assign all math teachers to their supervision

**Result**: Department head can monitor math teachers' performance, view all math class data, track attendance, and oversee homework assignments.

### Use Case 2: Grade Level Coordinator
**Scenario**: Coordinator oversees all Grade 5 classes

**Setup**:
1. Assign "Senior Teacher" role to coordinator
2. Assign all Grade 5 classes (A, B, C, etc.) to supervision
3. Assign all Grade 5 teachers to supervision

**Result**: Coordinator has complete visibility into Grade 5 performance, can track attendance trends, monitor behaviors, and review fee balances.

### Use Case 3: Assistant Principal
**Scenario**: Assistant principal supervises lower primary (Grades 1-3)

**Setup**:
1. Assign "Senior Teacher" role
2. Assign all Grade 1, 2, and 3 classes
3. Assign all lower primary teachers

**Result**: Full supervisory oversight of lower primary with detailed reports and data visibility.

## Key Features Available to Senior Teachers

✅ **Can Do:**
- View comprehensive dashboard with KPIs
- See all supervised classroom details
- View all students in supervised classes
- Mark and view attendance
- Enter exam marks and grades
- Create and manage homework
- Record student behaviors
- View and edit timetables
- View transport information
- View fee balances
- Manage report cards
- Access digital diaries
- View announcements and events

❌ **Cannot Do:**
- Create new students
- Collect fees or payments
- Issue invoices
- Apply discounts or credit notes
- Access HR/payroll information
- View other staff's salaries
- Modify staff records

## Troubleshooting

### Problem: Senior teacher sees no data
**Solution**: 
- Verify classrooms are assigned in "Senior Teacher Assignments"
- Ensure the role is properly assigned
- Check that classrooms have students

### Problem: "Access Denied" errors
**Solution**:
- Re-run permissions seeder: `php artisan db:seed --class=SeniorTeacherPermissionsSeeder`
- Clear cache: `php artisan cache:clear`
- Clear views: `php artisan view:clear`
- Verify role is exactly "Senior Teacher" (case-sensitive)

### Problem: Navigation menu not showing senior teacher options
**Solution**:
- Clear browser cache
- Log out and log back in
- Verify role assignment in database
- Check `routes/senior_teacher.php` exists

### Problem: Admin cannot see assignment page
**Solution**:
- Ensure logged in as Admin or Super Admin
- Check route exists: `php artisan route:list | grep senior-teacher`
- Clear route cache: `php artisan route:clear`

## Quick Commands Reference

```bash
# Run migrations
php artisan migrate

# Run permissions seeder
php artisan db:seed --class=SeniorTeacherPermissionsSeeder

# Clear all caches
php artisan cache:clear
php artisan view:clear
php artisan route:clear
php artisan config:clear

# Check routes
php artisan route:list | grep senior-teacher

# Rollback migrations (if needed)
php artisan migrate:rollback --step=1
```

## Next Steps

1. **Customize Permissions**: Adjust permissions in `SeniorTeacherPermissionsSeeder.php` if needed
2. **Add More Senior Teachers**: Repeat Steps 3-5 for additional staff
3. **Train Users**: Share this guide with your senior teachers
4. **Monitor Usage**: Check dashboard analytics for insights
5. **Gather Feedback**: Ask senior teachers for enhancement requests

## Support

For detailed documentation, see: `SENIOR_TEACHER_IMPLEMENTATION.md`

For questions or issues:
1. Check the troubleshooting section above
2. Review error logs: `storage/logs/laravel.log`
3. Verify database tables were created correctly
4. Ensure all files were copied/created properly

## Success Checklist

- [ ] Migrations run successfully
- [ ] Permissions seeder completed
- [ ] At least one user has "Senior Teacher" role
- [ ] Classrooms assigned to senior teacher
- [ ] Senior teacher can log in and see custom dashboard
- [ ] Admin can access assignment management page
- [ ] Navigation menu shows correctly
- [ ] Data filtering works (only supervised classes visible)
- [ ] Fee balances viewable but not editable
- [ ] HR sections properly restricted

## Estimated Time
- **Initial Setup**: 5 minutes
- **Per Additional Senior Teacher**: 2 minutes
- **Training Per User**: 10-15 minutes

---

**Congratulations!** Your Senior Teacher role is now set up and ready to use. Senior teachers can now effectively supervise their assigned classes and staff with appropriate access levels.

