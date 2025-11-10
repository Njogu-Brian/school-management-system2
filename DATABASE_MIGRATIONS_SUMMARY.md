# Database Migrations Summary

## Overview
This document summarizes all new database migrations created for the enhanced Students module.

## Migration Files Created

### 1. Extended Demographics (`2025_11_10_073742_add_extended_demographics_to_students_table.php`)
**Purpose**: Add extended personal and medical information to students table

**New Fields**:
- **Personal Details**: national_id_number, passport_number, religion, ethnicity, home_address, home_city, home_county, home_postal_code, language_preference
- **Medical Information**: blood_group, allergies, chronic_conditions, medical_insurance_provider, medical_insurance_number, emergency_medical_contact_name, emergency_medical_contact_phone
- **Previous School**: previous_schools (JSON), transfer_reason
- **Special Needs**: has_special_needs, special_needs_description, learning_disabilities

---

### 2. Status & Lifecycle (`2025_11_10_073747_add_status_and_lifecycle_to_students_table.php`)
**Purpose**: Track student status changes and lifecycle events

**New Fields**:
- **Status Management**: status (enum: active, inactive, graduated, transferred, expelled, suspended)
- **Dates**: admission_date, graduation_date, transfer_date, transfer_to_school
- **Status Tracking**: status_change_reason, status_changed_by, status_changed_at
- **Re-admission**: is_readmission, previous_student_id

**Foreign Keys**:
- `status_changed_by` → `users.id`

---

### 3. Medical Records (`2025_11_10_073754_create_student_medical_records_table.php`)
**Purpose**: Store comprehensive medical records for students

**Table Structure**:
- student_id (FK)
- record_type (enum: vaccination, checkup, medication, incident, certificate, other)
- record_date, title, description
- doctor_name, clinic_hospital
- medication_name, medication_dosage, medication_start_date, medication_end_date
- vaccination_name, vaccination_date, next_due_date
- certificate_type, certificate_file_path
- notes, created_by (FK)

**Indexes**: 
- (student_id, record_type)
- record_date

---

### 4. Disciplinary Records (`2025_11_10_073755_create_student_disciplinary_records_table.php`)
**Purpose**: Track student disciplinary incidents and actions

**Table Structure**:
- student_id (FK)
- incident_date, incident_time, incident_type, severity
- description, witnesses
- action_taken (enum: warning, verbal_warning, written_warning, detention, suspension, expulsion, parent_meeting, counseling, other)
- action_details, action_date
- improvement_plan
- parent_notified, parent_notification_date
- follow_up_notes, follow_up_date
- resolved, resolved_date
- reported_by (FK), action_taken_by (FK)

**Indexes**:
- (student_id, incident_date)
- severity
- resolved

---

### 5. Extracurricular Activities (`2025_11_10_073757_create_student_extracurricular_activities_table.php`)
**Purpose**: Track student participation in clubs, sports, competitions, and activities

**Table Structure**:
- student_id (FK)
- activity_type (enum: club, society, sports_team, competition, leadership_role, community_service, other)
- activity_name, description
- start_date, end_date
- position_role, team_name
- competition_name, competition_level
- award_achievement, achievement_description, achievement_date
- community_service_hours
- notes, is_active
- supervisor_id (FK)

**Indexes**:
- (student_id, activity_type)
- is_active

---

### 6. Academic History (`2025_11_10_073759_create_student_academic_history_table.php`)
**Purpose**: Track student academic progression through classes and years

**Table Structure**:
- student_id (FK)
- academic_year_id (FK, nullable)
- classroom_id (FK, nullable)
- stream_id (FK, nullable)
- enrollment_date, completion_date
- promotion_status (enum: promoted, retained, demoted, transferred, graduated)
- final_grade, class_position, stream_position
- remarks, teacher_comments
- is_current
- promoted_by (FK)

**Indexes**:
- (student_id, academic_year_id)
- is_current
- enrollment_date

---

### 7. Extended Parent Info (`2025_11_10_073800_add_extended_parent_info_to_parent_info_table.php`)
**Purpose**: Add extended information for parents/guardians

**New Fields**:
- **Father**: occupation, employer, work_address, education_level, whatsapp
- **Mother**: occupation, employer, work_address, education_level, whatsapp
- **Guardian**: occupation, employer, work_address, education_level, whatsapp
- **Family**: income_bracket, primary_contact_person, communication_preference, language_preference

---

## Migration Execution Order

When running migrations in production, they will execute in chronological order:

1. `2025_11_10_072028_add_father_mother_to_families_table.php` (already created)
2. `2025_11_10_073742_add_extended_demographics_to_students_table.php`
3. `2025_11_10_073747_add_status_and_lifecycle_to_students_table.php`
4. `2025_11_10_073754_create_student_medical_records_table.php`
5. `2025_11_10_073755_create_student_disciplinary_records_table.php`
6. `2025_11_10_073757_create_student_extracurricular_activities_table.php`
7. `2025_11_10_073759_create_student_academic_history_table.php`
8. `2025_11_10_073800_add_extended_parent_info_to_parent_info_table.php`

## Production Deployment Steps

1. **Backup Database**: Always backup before running migrations
   ```bash
   php artisan backup:run  # if using backup package
   # OR manual database backup
   ```

2. **Run Migrations**:
   ```bash
   php artisan migrate
   ```

3. **Verify Migration Status**:
   ```bash
   php artisan migrate:status
   ```

4. **Rollback if Needed** (if issues occur):
   ```bash
   php artisan migrate:rollback --step=1
   ```

## Notes

- All new fields are nullable to ensure backward compatibility
- Foreign keys use appropriate cascade/restrict/set null actions
- Indexes are added for performance on frequently queried fields
- All migrations include proper `down()` methods for rollback capability

## Next Steps

After migrations are run:
1. Update Eloquent models to include new fields in `$fillable`
2. Create controllers/services for new features
3. Create views/forms for data entry
4. Update validation rules
5. Create seeders for initial data if needed

