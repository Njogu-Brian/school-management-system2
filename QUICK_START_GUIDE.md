# Quick Start Guide - Enhanced Communication Features

## 🎯 New Feature: Select Specific Students

### Where to Find It
Navigate to any communication page:
- **Communication → Send Email**
- **Communication → Send SMS**
- **Communication → Send WhatsApp**

### How to Use

#### Step 1: Select Target
In the "Target" dropdown, you'll now see a new option:
```
Target: [Select Specific Students ▼]
```

#### Step 2: Open Student Selector
A button will appear:
```
[👥 Open Student Selector]
```
Click it to open the student selection modal.

#### Step 3: Search & Filter Students

**Search Box:**
- Type student name: "John"
- Type admission number: "ADM001"
- Type class name: "Grade 5"
- Results filter in real-time!

**Class Filter:**
- Dropdown to show only students from specific class
- Combine with search for precision

**Quick Actions:**
- **Select All** - Checks all visible students
- **Clear All** - Unchecks everything

#### Step 4: Select Students
- Check the box next to each student you want
- Selected count updates in top-right badge
- Visual confirmation as you select

#### Step 5: Confirm Selection
Click the **"Confirm Selection"** button at bottom of modal.

#### Step 6: Review Selected Students
- Modal closes
- Selected students appear as blue badges
- Count displayed: "3 selected"

#### Step 7: Compose & Send
- Write your message
- Use placeholders (they work!)
- Click **Send Email/SMS/WhatsApp**

## 📝 Using Placeholders

### Basic Example
```
Dear {{parent_name}},

This is to inform you about {{student_name}} 
from {{class_name}}.

The school office will be closed on {{date}}.

Regards,
{{school_name}}
```

### What Recipients See
**Parent 1 receives:**
```
Dear John Doe,

This is to inform you about Mary Doe 
from Grade 5A.

The school office will be closed on 13 Jan 2026.

Regards,
Royal Kings Premier School
```

**Parent 2 receives:**
```
Dear Jane Smith,

This is to inform you about Robert Smith 
from Grade 3B.

The school office will be closed on 13 Jan 2026.

Regards,
Royal Kings Premier School
```

### Available Placeholders

**Click any placeholder button on the page to insert it automatically!**

**General:**
- `{{school_name}}` - Your school name
- `{{school_phone}}` - School phone
- `{{date}}` - Today's date

**Student Info:**
- `{{student_name}}` - Student's full name
- `{{admission_number}}` - Admission/ID number
- `{{class_name}}` - Current class
- `{{parent_name}}` - Parent's name

**Finance (when sending invoices/receipts):**
- `{{invoice_number}}` - Invoice reference
- `{{total_amount}}` - Amount due
- `{{receipt_number}}` - Receipt reference
- `{{payment_date}}` - Date of payment

## 🎨 Tips & Tricks

### Tip 1: Use Search Efficiently
- Start typing immediately after opening modal
- Search works across name, admission no, and class
- Case-insensitive

### Tip 2: Class Filter First
1. Select class from filter dropdown
2. Then search within that class
3. Much faster for large schools!

### Tip 3: Select All Wisely
- Filters first, THEN "Select All"
- Only selects visible students
- Great for class-wide communications

### Tip 4: Double-Check Selection
- Review the badge list before sending
- Count badge shows total selected
- Can reopen modal to adjust

### Tip 5: Save as Template
If you send similar messages often:
1. Create a communication template
2. Include all your placeholders
3. Next time, just select template + students
4. Send!

## ⚠️ Common Mistakes to Avoid

### Mistake 1: Wrong Placeholder Format
❌ `{student_name}` - Single braces (old format, but still works)  
✅ `{{student_name}}` - Double braces (preferred)

### Mistake 2: Placeholder with Wrong Target
❌ Target: "Custom email list" + `{{student_name}}`  
✅ Target: "Select Specific Students" + `{{student_name}}`

**Why?** Custom emails don't have student data to pull from!

### Mistake 3: Forgetting to Confirm
- Selecting students in modal
- Closing modal without clicking "Confirm"
- Nothing gets saved!
- **Always click "Confirm Selection"**

### Mistake 4: Typing Placeholders Wrong
❌ `{{studentname}}` - Missing underscore  
❌ `{{Student_Name}}` - Wrong capitalization  
✅ `{{student_name}}` - Exactly as shown

**Pro Tip:** Click the placeholder buttons instead of typing!

## 🚀 Common Use Cases

### Use Case 1: Reminder to Specific Parents
**Scenario:** 5 students haven't submitted consent forms

**Steps:**
1. Communication → Send Email
2. Target: "Select Specific Students"
3. Search each student by name, check box
4. Compose: 
   ```
   Dear {{parent_name}},
   
   {{student_name}} has not submitted the consent form.
   Please send it by Friday.
   ```
5. Send!

### Use Case 2: Class Field Trip Announcement
**Scenario:** All Grade 5 students going on field trip

**Steps:**
1. Communication → Send SMS
2. Target: "Select Specific Students"
3. Filter: Select "Grade 5" from class dropdown
4. Click "Select All"
5. Confirm
6. Compose:
   ```
   Field trip on Friday for {{student_name}}. 
   Fee: 500/=. {{school_name}}
   ```
7. Send!

### Use Case 3: Fee Reminder to Selected Students
**Scenario:** 10 students with outstanding balances

**Steps:**
1. Communication → Send WhatsApp
2. Target: "Select Specific Students"
3. Manually select the 10 students
4. Use invoice placeholders:
   ```
   Dear {{parent_name}},
   
   Invoice {{invoice_number}} for {{student_name}}
   Balance: {{outstanding_amount}}
   Due: {{due_date}}
   
   Pay here: {{invoice_link}}
   ```
5. Send!

## 🔧 Troubleshooting

### Problem: Student Not Showing in List
**Solutions:**
- Check if student is archived
- Check if marked as alumni
- Only active students appear

### Problem: Placeholder Shows as {{student_name}} in Sent Message
**Causes:**
- Wrong target selected (e.g., custom emails)
- Student data missing in database
- Typo in placeholder name

**Fix:**
- Use correct target for data type
- Verify data exists in student record
- Copy placeholder from button, don't type

### Problem: Modal Won't Open
**Checks:**
- Is "Select Specific Students" selected in Target dropdown?
- Page fully loaded?
- Try refreshing the page

### Problem: Selection Not Saving
**Steps:**
1. Select students
2. **Must click "Confirm Selection" button**
3. Don't just close modal with X

## 📊 System Placeholders Reference Card

| Placeholder | What It Shows | Example Output |
|------------|---------------|----------------|
| `{{school_name}}` | School name | Royal Kings Premier School |
| `{{date}}` | Today's date | 13 Jan 2026 |
| `{{student_name}}` | Student full name | Mary Jane Doe |
| `{{admission_number}}` | Student ID | ADM001234 |
| `{{class_name}}` | Current class | Grade 5A |
| `{{parent_name}}` | Parent name | John Doe |
| `{{invoice_number}}` | Invoice ref | INV-2026-001 |
| `{{total_amount}}` | Invoice total | 15,000.00 |
| `{{receipt_number}}` | Receipt ref | RCPT-2026-001 |
| `{{payment_date}}` | Payment date | 10 Jan 2026 |

## 🎓 Creating Custom Placeholders

### When to Use Custom Placeholders
For school-wide information that's the same for everyone:
- School motto
- Bank account number
- Principal's name
- School address
- Office hours

### How to Create
1. Go to **Settings → Placeholders**
2. Click "Add New Placeholder"
3. Enter:
   - **Key:** `school_motto` (lowercase, underscores)
   - **Value:** `Excellence in Education`
4. Save
5. Use in messages: `{{school_motto}}`

### Example Custom Placeholders

```
Key: school_bank_account
Value: ABC Bank - 1234567890

Key: principal_name
Value: Dr. James Mwangi

Key: office_hours
Value: Monday-Friday, 8AM-5PM
```

**Usage:**
```
For payments, use account: {{school_bank_account}}

Signed,
{{principal_name}}
Principal

Office Hours: {{office_hours}}
```

## 📞 Need Help?

If you encounter issues:
1. Check this guide first
2. Refer to `PLACEHOLDER_DOCUMENTATION.md` for technical details
3. Verify your data in the system
4. Contact system administrator

---

**Enjoy the enhanced communication features! 🎉**

*Making school communication easier, one message at a time.*

