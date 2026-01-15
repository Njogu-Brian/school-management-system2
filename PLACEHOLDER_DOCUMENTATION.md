# Communication Placeholders Documentation

## Overview
The school management system supports dynamic placeholders in Email, SMS, and WhatsApp communications. Placeholders are automatically replaced with actual data when messages are sent.

## Placeholder Format
Placeholders use double curly braces: `{{placeholder_name}}`
Legacy single brace format `{placeholder_name}` is also supported for backward compatibility.

## System Placeholders

### General Information
- `{{school_name}}` - School name from settings
- `{{school_phone}}` - School contact phone
- `{{school_email}}` - School email address  
- `{{date}}` - Current date (format: DD MMM YYYY)

### Student & Parent Information
- `{{student_name}}` - Student's full name
- `{{admission_number}}` - Student admission/registration number
- `{{class_name}}` - Student's current class/grade
- `{{parent_name}}` - Parent's name (father > guardian > mother priority)
- `{{father_name}}` - Father's name specifically

### Staff Information
- `{{staff_name}}` - Staff member's full name

### Finance - Invoices & Reminders
- `{{invoice_number}}` - Invoice reference number (e.g., INV-2024-001)
- `{{total_amount}}` - Total invoice amount
- `{{due_date}}` - Invoice due date
- `{{outstanding_amount}}` - Amount still owed
- `{{status}}` - Payment status (paid, partial, unpaid)
- `{{invoice_link}}` - Public link to view invoice
- `{{days_overdue}}` - Number of days past due date

### Finance - Receipts
- `{{receipt_number}}` - Receipt reference number (e.g., RCPT-2024-001)
- `{{transaction_code}}` - Payment transaction code
- `{{payment_date}}` - Date payment was received
- `{{amount}}` - Payment amount
- `{{receipt_link}}` - Public link to view receipt
- `{{carried_forward}}` - Unallocated payment balance

### Finance - Payment Plans
- `{{installment_count}}` - Total number of installments
- `{{installment_amount}}` - Amount per installment
- `{{installment_number}}` - Current installment number
- `{{start_date}}` - Payment plan start date
- `{{end_date}}` - Payment plan end date
- `{{remaining_installments}}` - Installments left to pay
- `{{payment_plan_link}}` - Public link to view payment plan

### Custom Finance
- `{{custom_message}}` - Custom message content
- `{{custom_subject}}` - Custom email subject

## How Placeholder Replacement Works

### 1. Data Source Mapping
The `replace_placeholders()` function in `app/helpers.php` handles all placeholder replacements:

```php
replace_placeholders($message, $entity, $extra)
```

**Parameters:**
- `$message` - The template text containing placeholders
- `$entity` - The primary data source (Student, Staff, or ParentInfo model)
- `$extra` - Additional key-value pairs for context-specific data (invoices, receipts, etc.)

### 2. Entity-Based Replacement
When a message is personalized for a recipient:

**For Students:**
```php
if ($entity instanceof Student) {
    '{{student_name}}' => $entity->full_name
    '{{admission_number}}' => $entity->admission_number
    '{{class_name}}' => $entity->classroom->name
    '{{parent_name}}' => $entity->parent->father_name ?? ...
}
```

**For Staff:**
```php
if ($entity instanceof Staff) {
    '{{staff_name}}' => $entity->full_name
}
```

### 3. Context Data (Extra Parameters)
For finance operations, additional context is passed:

```php
replace_placeholders($message, $student, [
    'invoice_number' => $invoice->invoice_number,
    'total_amount' => number_format($invoice->total_amount, 2),
    'due_date' => $invoice->due_date->format('d M Y'),
    ...
]);
```

## Custom Placeholders

### Creating Custom Placeholders
Administrators can create custom placeholders through:
- **Settings → Placeholders** (managed via `PlaceholderController`)
- Stored in `communication_placeholders` OR `custom_placeholders` table

### Custom Placeholder Storage
```sql
CREATE TABLE custom_placeholders (
    id BIGINT PRIMARY KEY,
    key VARCHAR(255) UNIQUE,
    value VARCHAR(255),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### How Custom Placeholders Work

1. **Creation:**
   - Admin enters a `key` (e.g., "school_motto")
   - Admin enters a `value` (e.g., "Excellence in Education")
   - System stores in database

2. **Replacement:**
   ```php
   // In replace_placeholders() function
   foreach (CustomPlaceholder::all() as $ph) {
       $replacements['{{'.$ph->key.'}}'] = $ph->value;
   }
   ```

3. **Usage in Messages:**
   ```
   Dear Parent,
   
   Welcome to {{school_name}}.
   Our motto: {{school_motto}}
   
   Student: {{student_name}}
   Class: {{class_name}}
   ```

### Data Accuracy for Custom Placeholders

**Static Values:**
Custom placeholders contain **static values** that don't change per recipient:
- School motto
- Principal's name
- School address details
- School bank account
- Contact information

**NOT Suitable For:**
Custom placeholders should NOT be used for:
- Dynamic student data (use `{{student_name}}`, etc.)
- Dynamic parent data (use `{{parent_name}}`, etc.)
- Dynamic financial data (use invoice/receipt placeholders)

**Recommendation:**
- Use custom placeholders for school-wide constants
- Use system placeholders for person-specific data

## Validation & Error Prevention

### Target-Based Validation
The system validates placeholder usage against selected targets:

**Email/SMS/WhatsApp Forms:**
```javascript
// Validate before sending
if (usesStudentData && target === 'custom') {
    alert('Student placeholders require student/parent targets');
    return false;
}
```

**Valid Combinations:**
- `{{student_name}}` + Target: "Specific Students" ✅
- `{{student_name}}` + Target: "Parents (all)" ✅
- `{{student_name}}` + Target: "Custom emails" ❌
- `{{school_name}}` + Any target ✅

## Best Practices

### 1. Always Test Placeholders
Before mass sending, test with a single recipient to verify data appears correctly.

### 2. Provide Fallbacks
System handles missing data gracefully:
```php
'{{parent_name}}' => $entity->parent->father_name 
                    ?? $entity->parent->guardian_name 
                    ?? $entity->parent->mother_name 
                    ?? '';
```

### 3. Use Appropriate Targets
- Student data → Use student/parent/class targets
- Generic announcements → Can use any target
- Staff communications → Use staff target

### 4. Custom Placeholder Naming
- Use descriptive keys: `school_bank_account` not `acc`
- Use lowercase with underscores: `school_motto` not `SchoolMotto`
- Avoid conflicts with system placeholders

## Troubleshooting

### Placeholder Not Replaced
1. Check spelling matches exactly
2. Verify double curly braces: `{{name}}` not `{name}`
3. Ensure target type matches placeholder type
4. Check if data exists in database for that entity

### Wrong Data Appearing
1. Verify entity is correctly loaded with relationships
2. Check `replace_placeholders()` function for correct attribute names
3. Ensure Student model uses `full_name` accessor or correct field

### Custom Placeholder Not Working
1. Verify it's saved in `custom_placeholders` table
2. Check key spelling exactly matches usage
3. Ensure `CustomPlaceholder` model is properly loaded
4. Clear cache if needed

## Code References

### Key Files
- `app/helpers.php` - `replace_placeholders()` function
- `app/Services/CommunicationHelperService.php` - Recipient collection
- `app/Http/Controllers/CommunicationController.php` - Placeholder definitions
- `app/Models/CustomPlaceholder.php` - Custom placeholder model
- `resources/views/communication/partials/*.blade.php` - Forms with placeholder UI

### Database Tables
- `custom_placeholders` - Custom admin-created placeholders
- `communication_placeholders` - Alternative placeholder storage
- `students` - Student data source
- `parent_info` - Parent data source
- `staff` - Staff data source




