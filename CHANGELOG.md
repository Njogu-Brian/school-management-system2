# Changelog

All notable changes to the School Management System will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

#### Infrastructure & Safety
- Database backup script (`scripts/backup-db.sh`)
- GitHub Actions workflows for CI/CD:
  - `ci-tests.yml` - PHP tests with MySQL service
  - `ci-security-scan.yml` - Dependency and secret scanning
  - `backup-before-migrate.yml` - Migration safety checks
- Comprehensive documentation:
  - `DEVELOPMENT.md` - Local development setup guide
  - `DEPLOYMENT.md` - Deployment procedures and safety
  - `DB_README.md` - Database schema documentation
  - `AUTOMATION_REPORT.md` - Project status tracking
- Code quality tools:
  - PHPStan configuration (`phpstan.neon`)
  - Laravel Pint configuration (`pint.json`)
- PR template with migration safety checklist

#### Certificate & Document Generation
- Document templates system with HTML placeholders
- PDF generation using barryvdh/laravel-dompdf
- Support for multiple document types (certificates, transcripts, ID cards, etc.)
- Placeholder replacement for student, staff, and system data
- Generated documents tracking and download
- Routes: `document-templates.*`, `generated-documents.*`
- Unit and feature tests

#### Online Payment Gateway Integration
- Payment gateway infrastructure with interface contract
- M-Pesa STK Push integration
- Payment transaction tracking
- Webhook handling with idempotency
- Automatic invoice updates on successful payment
- Payment status verification
- Routes: `payment.webhook.*`, `payment-transactions.*`
- Stripe and PayPal structure ready for implementation

#### Library Management System
- Complete book cataloging system
- Book copies management with barcode support
- Library card issuance and management
- Book borrowing and returning
- Book renewal functionality
- Fine calculation for overdue books
- Book reservation system
- Routes: `library.*`
- LibraryService for core operations

#### Hostel Management System
- Hostel and room management
- Student room allocation
- Hostel fee structure
- Hostel attendance tracking
- Mess menu management
- Mess subscription system
- Routes: `hostel.*`
- HostelService for allocation management

### Changed
- Enhanced PaymentController with online payment support
- Updated routes with new endpoints

### Security
- Webhook signature verification (M-Pesa)
- Idempotency handling for webhooks
- Secure credential storage guidelines

### Database
- All new migrations are non-destructive (additive only)
- No existing data will be affected
- All foreign keys properly constrained

## Migration Notes

### Required Migrations
Run these migrations in order after creating a database backup:

1. Certificate Generation:
   - `2025_11_26_012514_create_document_templates_table.php`
   - `2025_11_26_012515_create_generated_documents_table.php`

2. Payment Gateway:
   - `2025_11_26_020000_create_payment_transactions_table.php`
   - `2025_11_26_020001_create_payment_webhooks_table.php`

3. Library Management:
   - `2025_11_26_030000_create_books_table.php`
   - `2025_11_26_030001_create_book_copies_table.php`
   - `2025_11_26_030002_create_library_cards_table.php`
   - `2025_11_26_030003_create_book_borrowings_table.php`
   - `2025_11_26_030004_create_book_reservations_table.php`
   - `2025_11_26_030005_create_library_fines_table.php`

4. Hostel Management:
   - `2025_11_26_040000_create_hostels_table.php`
   - `2025_11_26_040001_create_hostel_rooms_table.php`
   - `2025_11_26_040002_create_hostel_allocations_table.php`
   - `2025_11_26_040003_create_hostel_fees_table.php`
   - `2025_11_26_040004_create_hostel_attendance_table.php`
   - `2025_11_26_040005_create_mess_menus_table.php`
   - `2025_11_26_040006_create_mess_subscriptions_table.php`

### Environment Variables Required

Add these to your `.env` file (see `DEPLOYMENT.md` for full list):

```env
# Payment Gateway - M-Pesa
MPESA_CONSUMER_KEY=your_consumer_key
MPESA_CONSUMER_SECRET=your_consumer_secret
MPESA_SHORTCODE=your_shortcode
MPESA_PASSKEY=your_passkey
MPESA_ENVIRONMENT=sandbox

# Payment Gateway - Stripe (optional)
PAYMENT_STRIPE_KEY=pk_test_xxx
PAYMENT_STRIPE_SECRET=sk_test_xxx
PAYMENT_STRIPE_WEBHOOK_SECRET=whsec_xxx

# Payment Gateway - PayPal (optional)
PAYPAL_CLIENT_ID=your_client_id
PAYPAL_CLIENT_SECRET=your_client_secret
PAYPAL_MODE=sandbox
```

## Breaking Changes
None - all changes are additive and backward compatible.

## Deprecated
Nothing deprecated in this release.

## Removed
Nothing removed in this release.

## Fixed
- Enhanced payment controller with proper error handling
- Improved database relationships and constraints

## Security
- All webhook endpoints validate signatures
- Payment transactions use idempotency keys
- No sensitive data committed to repository

