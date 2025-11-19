# School Management System - Complete User Manual

**Version:** 2.0  
**Last Updated:** November 18, 2025

---

## Table of Contents

1. [Getting Started](#getting-started)
2. [Login & Authentication](#login--authentication)
3. [Dashboard Overview](#dashboard-overview)
4. [Staff Management](#staff-management)
5. [Student Management](#student-management)
6. [Academics Management](#academics-management)
7. [Finance Management](#finance-management)
8. [Attendance Management](#attendance-management)
9. [Transport Management](#transport-management)
10. [Communication](#communication)
11. [Activities Management](#activities-management)
12. [Settings & Configuration](#settings--configuration)
13. [Reports & Analytics](#reports--analytics)

---

## Getting Started

### System Requirements
- Modern web browser (Chrome, Firefox, Edge, Safari)
- Internet connection
- Valid user account with appropriate role

### Accessing the System
1. Open your web browser
2. Navigate to the school management system URL
3. You will see the login page

---

## Login & Authentication

### Logging In

1. **Navigate to Login Page**
   - Go to the system URL
   - You will be redirected to `/login` if not authenticated

2. **Enter Credentials**
   - Enter your **Email** or **Username**
   - Enter your **Password**
   - Click **Login** button

3. **Dashboard Redirect**
   - After successful login, you will be redirected to your role-specific dashboard:
     - **Super Admin/Admin/Secretary** → Admin Dashboard
     - **Teacher** → Teacher Dashboard
     - **Parent** → Parent Dashboard
     - **Student** → Student Dashboard
     - **Driver** → Transport Dashboard

### Logging Out

1. Click on your **profile/name** in the top right corner
2. Select **Logout** from the dropdown menu
3. You will be logged out and redirected to the login page

### Profile Management

1. Click on your **profile/name** in the top right
2. Select **My Profile**
3. Update your information:
   - Personal details
   - Contact information
   - Password (if allowed)
4. Click **Save** to update

---

## Dashboard Overview

### Admin Dashboard
- **Quick Stats:** Total students, staff, classes
- **Recent Activities:** Latest system activities
- **Pending Tasks:** Approvals, notifications
- **Quick Links:** Common actions

### Teacher Dashboard
- **My Classes:** Assigned classes
- **Today's Schedule:** Timetable for today
- **Pending Tasks:** Homework to mark, attendance to take
- **Quick Actions:** Mark attendance, create lesson plan

### Parent Dashboard
- **My Children:** List of enrolled children
- **Fee Status:** Outstanding fees
- **Recent Updates:** Notifications, announcements
- **Quick Links:** View reports, pay fees

### Student Dashboard
- **My Classes:** Current class information
- **Timetable:** Weekly schedule
- **Assignments:** Pending homework
- **Grades:** Recent exam results

---

## Staff Management

### Creating a New Staff Member

1. **Navigate to Staff**
   - Click **Staff** in the main navigation
   - Click **Add New Staff** button

2. **Fill Staff Information**
   - **Personal Information:**
     - First Name, Middle Name, Last Name
     - Date of Birth
     - Gender
     - ID Number
     - KRA PIN
   - **Contact Information:**
     - Work Email
     - Personal Email
     - Phone Number
     - Residential Address
   - **Employment Information:**
     - Staff ID
     - Department
     - Job Title
     - Staff Category
     - Hire Date
     - Employment Status
     - Employment Type
   - **Financial Information:**
     - Bank Name
     - Bank Branch
     - Bank Account Number
     - NSSF Number
     - NHIF Number
   - **Emergency Contact:**
     - Contact Name
     - Relationship
     - Phone Number
   - **System Access:**
     - Create User Account (checkbox)
     - Email (for login)
     - Password
     - Assign Roles

3. **Save Staff**
   - Click **Save** button
   - Staff will be created and user account created if selected

### Viewing Staff List

1. Navigate to **Staff** → **Staff List**
2. Use filters to search:
   - Search by name
   - Filter by department
   - Filter by category
   - Filter by status
3. Click on a staff member to view details

### Editing Staff Information

1. Go to **Staff** → **Staff List**
2. Click on the staff member you want to edit
3. Click **Edit** button
4. Update information
5. Click **Save**

### Bulk Upload Staff

1. Navigate to **Staff** → **Upload Staff**
2. Download the template:
   - Click **Download Template**
   - Fill in staff information in Excel
3. Upload the file:
   - Click **Choose File**
   - Select your filled template
   - Click **Upload**
4. Review and confirm:
   - Review the parsed data
   - Fix any errors
   - Click **Confirm Upload**

### Leave Management

#### Creating Leave Request (Staff Self-Service)

1. Go to **My Profile** → **Leave Requests**
2. Click **Request Leave**
3. Fill in:
   - Leave Type
   - Start Date
   - End Date
   - Reason
   - Attach documents if needed
4. Click **Submit**

#### Approving Leave Requests (Admin)

1. Navigate to **Staff** → **Leave Requests**
2. View pending requests
3. Click on a request to view details
4. Click **Approve** or **Reject**
5. Add comments if needed

### Payroll Management

#### Creating Payroll Period

1. Navigate to **HR** → **Payroll** → **Periods**
2. Click **Create Period**
3. Fill in:
   - Period Name
   - Start Date
   - End Date
   - Pay Date
4. Click **Save**

#### Processing Payroll

1. Go to **HR** → **Payroll** → **Periods**
2. Click on a period
3. Click **Process Payroll**
4. Review generated records
5. Click **Lock Period** when ready

#### Viewing Payslips

1. Navigate to **HR** → **Payroll** → **Records**
2. Click on a payroll record
3. Click **View Payslip**
4. Download PDF if needed

---

## Student Management

### Creating a New Student

1. **Navigate to Students**
   - Click **Students** in navigation
   - Click **Add New Student** button

2. **Fill Student Information**
   - **Personal Information:**
     - First Name, Middle Name, Last Name
     - Date of Birth
     - Gender
     - Admission Number
     - Student Category
   - **Class Assignment:**
     - Classroom
     - Stream (if applicable)
     - Academic Year
     - Term
   - **Contact Information:**
     - Phone Number
     - Email (if applicable)
     - Address
   - **Parent/Guardian Information:**
     - Parent Name
     - Relationship
     - Phone Number
     - Email
     - Address

3. **Save Student**
   - Click **Save**
   - Student will be created and assigned to class

### Bulk Upload Students

1. Navigate to **Students** → **Bulk Upload**
2. Download template:
   - Click **Download Template**
   - Fill in student data in Excel
3. Upload:
   - Click **Choose File**
   - Select filled template
   - Click **Upload**
4. Review and import:
   - Review parsed data
   - Fix errors
   - Click **Import**

### Student Promotions

1. Navigate to **Academics** → **Promotions**
2. Select source classroom
3. View students in that class
4. Select students to promote:
   - Select individual students
   - Or click **Promote All**
5. Select destination classroom
6. Click **Promote**

### Student Records

#### Medical Records

1. Go to **Students** → Select a student
2. Click **Medical Records** tab
3. Click **Add Medical Record**
4. Fill in:
   - Date
   - Condition/Illness
   - Treatment
   - Doctor/Clinic
   - Notes
5. Click **Save**

#### Disciplinary Records

1. Go to **Students** → Select a student
2. Click **Disciplinary Records** tab
3. Click **Add Disciplinary Record**
4. Fill in:
   - Date
   - Incident Type
   - Description
   - Action Taken
   - Notes
5. Click **Save**

#### Academic History

1. Go to **Students** → Select a student
2. Click **Academic History** tab
3. Click **Add History**
4. Fill in previous school information
5. Click **Save**

---

## Academics Management

### Classrooms & Streams

#### Creating a Classroom

1. Navigate to **Academics** → **Classrooms**
2. Click **Add Classroom**
3. Fill in:
   - Classroom Name (e.g., "Grade 1")
   - Level
   - Capacity
   - Description
4. Click **Save**

#### Creating a Stream

1. Go to **Academics** → **Streams**
2. Click **Add Stream**
3. Fill in:
   - Stream Name (e.g., "A", "B")
   - Classroom
   - Capacity
4. Click **Save**

#### Assigning Teachers to Streams

1. Go to **Academics** → **Streams**
2. Click on a stream
3. Click **Assign Teachers**
4. Select teachers for each subject
5. Click **Save**

### Subjects Management

#### Creating a Subject

1. Navigate to **Academics** → **Subjects**
2. Click **Add Subject**
3. Fill in:
   - Subject Code
   - Subject Name
   - Subject Group
   - Learning Area
   - Level
   - Is Optional (checkbox)
4. Click **Save**

#### Assigning Subjects to Classrooms

1. Go to **Academics** → **Subjects**
2. Click on a subject
3. Click **Assign to Classrooms**
4. Select classrooms
5. Set lessons per week for each
6. Click **Save**

#### Generating CBC Subjects

1. Navigate to **Academics** → **Subjects**
2. Click **Generate CBC Subjects**
3. Select grade levels
4. Click **Generate**
5. Review and confirm

### Teacher Assignments

1. Navigate to **Academics** → **Assign Teachers**
2. Select:
   - Academic Year
   - Term
   - Classroom
3. View subject assignments
4. Assign teachers to subjects:
   - Select teacher for each subject
   - Set lessons per week
5. Click **Save Assignments**

### Exams Management

#### Creating an Exam

1. Navigate to **Academics** → **Exams**
2. Click **Create Exam**
3. Fill in:
   - Exam Name
   - Exam Type
   - Academic Year
   - Term
   - Start Date
   - End Date
   - Description
4. Click **Save**

#### Scheduling Exams

1. Go to **Academics** → **Exams** → Select an exam
2. Click **Schedules** tab
3. Click **Add Schedule**
4. Fill in:
   - Subject
   - Date
   - Start Time
   - End Time
   - Classroom/Stream
   - Venue
5. Click **Save**

#### Entering Exam Marks

**Individual Entry:**
1. Go to **Academics** → **Exam Marks**
2. Select exam and subject
3. Select student
4. Enter marks
5. Click **Save**

**Bulk Entry:**
1. Go to **Academics** → **Exam Marks** → **Bulk Entry**
2. Select exam and subject
3. Select classroom
4. Enter marks for all students in table
5. Click **Save All**

#### Publishing Exam Results

1. Navigate to **Academics** → **Exams** → **Results**
2. Select exam
3. Review results
4. Click **Publish** when ready
5. Results will be visible to students/parents

### Schemes of Work

#### Creating a Scheme of Work

1. Navigate to **CBC Curriculum & Planning** → **Schemes of Work**
2. Click **Create Scheme of Work**
3. Fill in:
   - Title
   - Subject
   - Classroom
   - Academic Year
   - Term
   - Week
   - Strand
   - Substrand
   - Learning Outcomes
   - Key Inquiry Questions
   - Core Competencies
   - Values
   - PCIs
   - Suggested Learning Experiences
   - Assessment Rubrics
4. Click **Save**

#### Approving Schemes of Work

1. Go to **Schemes of Work** → Select a scheme
2. Review content
3. Click **Approve** or **Reject**
4. Add comments if needed

#### Exporting Schemes of Work

1. Go to **Schemes of Work** → Select a scheme
2. Click **Export PDF** or **Export Excel**
3. File will download

### Lesson Plans

#### Creating a Lesson Plan

1. Navigate to **CBC Curriculum & Planning** → **Lesson Plans**
2. Click **Create Lesson Plan**
3. Fill in:
   - Title
   - Subject
   - Classroom
   - Date
   - Strand
   - Substrand
   - Learning Objectives
   - Learning Activities
   - Resources
   - Assessment
4. Click **Save**

#### Assigning Homework from Lesson Plan

1. Go to **Lesson Plans** → Select a lesson plan
2. Click **Assign Homework**
3. Fill in homework details
4. Select students/class
5. Set due date
6. Click **Assign**

### Curriculum Designs

#### Uploading a Curriculum Design

1. Navigate to **CBC Curriculum & Planning** → **Curriculum Designs**
2. Click **Upload Design**
3. Fill in:
   - Title
   - Description
   - Upload PDF file
4. Click **Upload**
5. System will process the PDF:
   - Extract text
   - Parse structure
   - Generate learning areas, strands, substrands
   - Progress bar will show status

#### Reviewing Extracted Data

1. Go to **Curriculum Designs** → Select a design
2. Click **Review Extraction**
3. Review:
   - Learning Areas
   - Strands
   - Substrands
   - Competencies
4. Make corrections if needed
5. Click **Save**

### Timetable Management

#### Viewing Timetable

1. Navigate to **Timetable** → **View Timetable**
2. Select:
   - Academic Year
   - Term
   - Classroom (optional)
   - Teacher (optional)
3. View timetable grid

#### Generating Timetable

1. Go to **Timetable** → **Classroom Timetable**
2. Select classroom, year, term
3. Click **Generate Timetable**
4. System will auto-generate based on:
   - Subject assignments
   - Lessons per week
   - Teacher availability
5. Review and adjust if needed
6. Click **Save Timetable**

#### Editing Timetable

1. Go to **Timetable** → **Classroom Timetable**
2. Select classroom
3. Click **Edit**
4. Drag and drop or click to change:
   - Subject assignments
   - Time slots
   - Teachers
5. Click **Save**

### Homework Management

#### Creating Homework

1. Navigate to **Academics** → **Homework**
2. Click **Create Homework**
3. Fill in:
   - Title
   - Subject
   - Classroom
   - Description
   - Due Date
4. Click **Assign**

#### Marking Homework

1. Go to **Academics** → **Homework**
2. Click on a homework assignment
3. View submissions
4. Click **Mark** for each submission
5. Enter marks and comments
6. Click **Save**

### Report Cards

#### Generating Report Cards

1. Navigate to **Academics** → **Report Cards** → **Generate Reports**
2. Select:
   - Academic Year
   - Term
   - Classroom
   - Report Type
3. Click **Generate**
4. Review generated reports
5. Click **Publish** when ready

#### Viewing Report Cards

1. Go to **Academics** → **Report Cards**
2. Select a report card
3. View student performance
4. Click **Export PDF** to download

---

## Finance Management

### Voteheads Management

1. Navigate to **Finance** → **Voteheads**
2. Click **Add Votehead**
3. Fill in:
   - Name
   - Description
   - Is Mandatory
   - Charge Type
   - Default Amount
4. Click **Save**

### Fee Structures

#### Creating Fee Structure

1. Go to **Finance** → **Fee Structures**
2. Click **Create Fee Structure**
3. Select:
   - Classroom
   - Academic Year
4. Add voteheads:
   - Select votehead
   - Enter amount
   - Add more as needed
5. Click **Save**

### Invoicing

#### Creating Invoice

1. Navigate to **Finance** → **Invoices**
2. Click **Create Invoice**
3. Select:
   - Student
   - Academic Year
   - Term
4. Select fee items:
   - Check voteheads to include
   - Adjust amounts if needed
5. Click **Generate Invoice**

#### Recording Payment

1. Go to **Finance** → **Payments**
2. Click **Record Payment**
3. Fill in:
   - Student
   - Invoice (or select fees directly)
   - Payment Method
   - Amount
   - Payment Date
   - Reference Number
4. Click **Save**
5. Receipt will be generated automatically

### Fee Statements

1. Navigate to **Finance** → **Fee Statements**
2. Select student
3. Select period
4. Click **Generate Statement**
5. View or download PDF

---

## Attendance Management

### Marking Attendance

1. Navigate to **Attendance** → **Mark Attendance**
2. Select:
   - Date
   - Classroom
   - Period (if applicable)
3. Mark each student:
   - Present
   - Absent
   - Late
   - Select reason code if absent
4. Click **Save Attendance**

### Viewing Attendance Records

1. Go to **Attendance** → **Records**
2. Use filters:
   - Date range
   - Classroom
   - Student
3. View attendance statistics
4. Export if needed

### At-Risk Students

1. Navigate to **Attendance** → **At-Risk Students**
2. View students with poor attendance
3. Click on student to see details
4. Send notifications if needed

---

## Transport Management

### Routes Management

1. Navigate to **Transport** → **Routes**
2. Click **Add Route**
3. Fill in:
   - Route Name
   - Description
   - Start Point
   - End Point
4. Add drop-off points
5. Click **Save**

### Vehicles Management

1. Go to **Transport** → **Vehicles**
2. Click **Add Vehicle**
3. Fill in:
   - Vehicle Number
   - Make/Model
   - Capacity
   - Driver
4. Click **Save**

### Assigning Students to Routes

1. Navigate to **Transport** → **Student Assignments**
2. Click **Assign Student**
3. Select:
   - Student
   - Route
   - Drop-off Point
4. Click **Save**

---

## Communication

### Sending Email

1. Navigate to **Communication** → **Send Email**
2. Select recipients:
   - Individual
   - Class
   - All parents
   - Custom list
3. Select template or write custom message
4. Fill in subject and message
5. Click **Send**

### Sending SMS

1. Go to **Communication** → **Send SMS**
2. Select recipients
3. Select template or write message
4. Review cost
5. Click **Send**

### Creating Templates

1. Navigate to **Communication** → **Templates**
2. Click **Create Template**
3. Fill in:
   - Name
   - Type (Email/SMS)
   - Subject (for email)
   - Message (use placeholders)
4. Click **Save**

### Announcements

1. Go to **Communication** → **Announcements**
2. Click **Create Announcement**
3. Fill in:
   - Title
   - Content
   - Target audience
   - Publish date
4. Click **Publish**

---

## Activities Management

### Creating an Activity

1. Navigate to **Timetable** → **Activities**
2. Click **Add Activity**
3. Fill in:
   - Name
   - Type (Club, Sport, Event, etc.)
   - Day
   - Start Time
   - End Time
   - Academic Year
   - Term
   - Classrooms
   - Supervising Staff
4. **Finance Integration:**
   - Fee Amount (if applicable)
   - Auto-invoice checkbox
5. **Assign Students:**
   - Select students to participate
6. Click **Save**
7. If fee is set:
   - Votehead will be created automatically
   - Fee will be added to class fee structures
   - Students will be invoiced if auto-invoice is enabled

### Assigning Students to Activity

1. Go to **Activities** → Select an activity
2. Click **Edit**
3. Scroll to **Assign Students** section
4. Select students
5. Check **Auto-invoice** if needed
6. Click **Save**

---

## Settings & Configuration

### General Settings

1. Navigate to **Settings** → **General**
2. Update:
   - School Name
   - School Address
   - Contact Information
   - Logo
3. Click **Save**

### Academic Configuration

1. Go to **Settings** → **Academic Configuration**
2. **Academic Years:**
   - Click **Add Year**
   - Enter year (e.g., "2025")
   - Set start and end dates
   - Click **Save**
3. **Terms:**
   - Click **Add Term**
   - Enter term name
   - Set start and end dates
   - Click **Save**

### School Days

1. Navigate to **Settings** → **School Days**
2. View calendar
3. Mark holidays:
   - Click on date
   - Enter holiday name
   - Click **Save**
4. Generate holidays:
   - Click **Generate Holidays**
   - Select year
   - System will add common holidays

### Module Management

1. Go to **Settings** → **Modules**
2. Enable/disable modules:
   - Attendance
   - Transport
   - Communication
   - Finance
   - etc.
3. Click **Save**

### Roles & Permissions

1. Navigate to **Settings** → **Roles & Permissions**
2. Select a role
3. Check/uncheck permissions
4. Click **Save**

---

## Reports & Analytics

### HR Reports

1. Navigate to **HR** → **Reports**
2. Available reports:
   - Staff Directory
   - Department Report
   - Category Report
   - New Hires
   - Terminations
   - Turnover Analysis
3. Select report
4. Apply filters
5. Click **Generate**

### Attendance Reports

1. Go to **Attendance** → **Reports**
2. Select report type
3. Apply filters
4. Click **Generate**

### Financial Reports

1. Navigate to **Finance** → **Reports**
2. Select report type
3. Apply filters
4. Click **Generate**

---

## Tips & Best Practices

### Data Entry
- Always verify information before saving
- Use bulk upload for large datasets
- Keep backups of important data

### Security
- Use strong passwords
- Don't share login credentials
- Log out when done

### Performance
- Use filters to narrow down large lists
- Export data for offline analysis
- Clear browser cache if experiencing issues

### Support
- Contact system administrator for issues
- Check documentation for common questions
- Report bugs or suggestions

---

**End of User Manual**

For technical support, contact your system administrator.

