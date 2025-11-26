# Certificate & Document Generation Feature

## Overview

This feature adds a comprehensive document generation system that allows administrators to create templates and generate certificates, transcripts, ID cards, and other documents for students and staff.

## Architecture

### Components

1. **DocumentTemplate Model** - Stores template definitions
2. **DocumentGenerator Service** - Handles template rendering and PDF generation
3. **DocumentController** - API endpoints for template management and generation
4. **Template Editor UI** - Admin interface for creating/editing templates

### Database Schema

```sql
document_templates:
  - id
  - name
  - slug (unique)
  - type (certificate, transcript, id_card, transfer_certificate, etc.)
  - template_html
  - placeholders (JSON) - Available placeholders
  - is_active
  - created_by
  - updated_at

generated_documents:
  - id
  - template_id
  - student_id (nullable)
  - staff_id (nullable)
  - document_type
  - pdf_path
  - data (JSON) - Data used for generation
  - generated_by
  - generated_at
```

## Implementation Plan

### Phase 1: Core Infrastructure
- [x] Create migration for document_templates
- [x] Create migration for generated_documents
- [ ] Create DocumentTemplate model
- [ ] Create GeneratedDocument model
- [ ] Create DocumentGenerator service

### Phase 2: Template Management
- [ ] Create DocumentTemplateController
- [ ] Template CRUD operations
- [ ] Template preview functionality
- [ ] Placeholder validation

### Phase 3: Document Generation
- [ ] Generate PDF from template
- [ ] Placeholder replacement logic
- [ ] Batch generation support
- [ ] Download/email functionality

### Phase 4: UI & Integration
- [ ] Admin template editor
- [ ] Student document portal
- [ ] Integration with student/staff profiles

### Phase 5: Testing
- [ ] Unit tests for DocumentGenerator
- [ ] Integration tests for endpoints
- [ ] E2E tests for document generation flow

## Placeholders

### Student Placeholders
- `{{student_name}}`
- `{{student_admission_number}}`
- `{{student_class}}`
- `{{student_stream}}`
- `{{student_dob}}`
- `{{student_gender}}`
- `{{student_parent_name}}`
- `{{student_parent_phone}}`
- `{{student_photo}}`

### Staff Placeholders
- `{{staff_name}}`
- `{{staff_id}}`
- `{{staff_department}}`
- `{{staff_position}}`
- `{{staff_photo}}`

### System Placeholders
- `{{school_name}}`
- `{{school_address}}`
- `{{school_logo}}`
- `{{current_date}}`
- `{{current_year}}`
- `{{signature_headteacher}}`
- `{{signature_registrar}}`

## Acceptance Criteria

1. ✅ Admin can create a template with HTML placeholders
2. ✅ System can generate a PDF for any student without changing the DB
3. ✅ Generated documents are stored and can be downloaded
4. ✅ Templates support all standard placeholders
5. ✅ Tests pass in CI
6. ✅ Non-destructive migrations only

## Security Considerations

- Only authenticated users can generate documents
- Role-based access (Admin/Secretary only for templates)
- Generated documents stored securely
- PDFs include watermarks for security

