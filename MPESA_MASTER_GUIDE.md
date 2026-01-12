# M-PESA Integration - Master Guide

> **Complete M-PESA Payment Integration Documentation**  
> Everything you need in one place - from setup to production deployment

**Last Updated**: January 8, 2025  
**Version**: 2.0  
**System**: School Management System

---

## 📚 Table of Contents

### Part 1: Quick Start & Overview
1. [Quick Start (5 Minutes)](#quick-start-5-minutes)
2. [Recent Updates (January 2025)](#recent-updates-january-2025)
3. [Features Overview](#features-overview)
4. [System Requirements](#system-requirements)

### Part 2: Setup & Configuration
5. [Complete Setup Guide](#complete-setup-guide)
6. [Environment Variables](#environment-variables)
7. [Configuration Reference](#configuration-reference)
8. [Getting Your Passkey](#getting-your-passkey)

### Part 3: User Guides
9. [Admin User Flows](#admin-user-flows)
10. [Parent User Flows](#parent-user-flows)
11. [Payment Channels](#payment-channels)
12. [Partial Payments](#partial-payments)

### Part 4: Production Deployment
13. [Pre-Deployment Checklist](#pre-deployment-checklist)
14. [Getting Production Credentials](#getting-production-credentials)
15. [Production URLs Configuration](#production-urls-configuration)
16. [Deployment Steps](#deployment-steps)
17. [Go-Live Process](#go-live-process)

### Part 5: Operations & Maintenance
18. [Monitoring & Logging](#monitoring-logging)
19. [Troubleshooting](#troubleshooting)
20. [Security Best Practices](#security-best-practices)
21. [API Reference](#api-reference)

### Part 6: Advanced Topics
22. [Additional Features](#additional-features)
23. [Scaling Considerations](#scaling-considerations)
24. [Compliance & Regulations](#compliance-regulations)

---

# Part 1: Quick Start & Overview

## Quick Start (5 Minutes)

Get M-PESA payments working in your school management system in just 5 minutes!

### Step 1: Run Migrations

```bash
cd /path/to/your/project
php artisan migrate
```

This creates the necessary database tables:
- `payment_links` - For shareable payment links
- Adds M-PESA specific fields to `payments` and `payment_transactions` tables

### Step 2: Configure Environment Variables

Add to your `.env` file:

```env
# M-PESA Configuration (Sandbox for Testing)
MPESA_ENVIRONMENT=sandbox
MPESA_CONSUMER_KEY=your_consumer_key_here
MPESA_CONSUMER_SECRET=your_consumer_secret_here
MPESA_SHORTCODE=174379
MPESA_PASSKEY=bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919
MPESA_CALLBACK_URL=${APP_URL}/webhooks/payment/mpesa
```

**For sandbox testing, use these credentials:**
- Shortcode: `174379`
- Passkey: `bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919`
- Test Phone: `254708374149`

### Step 3: Set Up Ngrok (For Local Testing)

```bash
# Install ngrok (if not already installed)
# Visit: https://ngrok.com/download

# Start ngrok
ngrok http 8000

# Copy the HTTPS URL (e.g., https://abc123.ngrok.io)
# Update your .env
MPESA_CALLBACK_URL=https://abc123.ngrok.io/webhooks/payment/mpesa
```

### Step 4: Clear Cache

```bash
php artisan config:cache
php artisan cache:clear
```

### Step 5: Test Payment

1. Navigate to: **Finance → M-PESA Payments → Prompt Parent to Pay**
2. Select a student
3. Enter test phone number: `254708374149`
4. Enter amount: `10` (KES 10)
5. Click **"Send Payment Request"**

✅ **You should receive an STK Push on the test phone!**

---

## Recent Updates (January 2025)

### 🎉 New Features Added

#### 1. **Phone Number Selection for Admins**
- Admins can now select from:
  - Father's phone number
  - Mother's phone number
  - Primary/Guardian phone
  - Custom phone number

#### 2. **Phone Number Editing for Parents**
- Parents can edit phone numbers before payment
- Useful when paying from a different M-PESA account
- Available on all payment pages

#### 3. **Pay Now from Student Statement**
- "Pay Now" button on balance card
- Quick payment modal
- Supports full or partial payments

#### 4. **Enhanced Navigation**
- Dedicated M-PESA submenu in Finance
- Quick access to all M-PESA features
- Green phone icon for easy identification

#### 5. **Production URLs Integration**
- All Safaricom production URLs configured
- Automatic environment switching
- 20+ API endpoints ready

#### 6. **Advanced Payment Methods**
- Transaction status query
- C2B URL registration
- Account balance checking
- Payment reversals/refunds

### 📊 Technical Improvements

- Enhanced webhook processing
- Better error handling
- Comprehensive logging
- Payment channel tracking
- M-PESA receipt number storage
- Production-ready security

---

## Features Overview

### Core Payment Features

#### 🔔 **Admin-Prompted STK Push**
- Admin selects student and parent's phone
- Choose father, mother, or custom number
- Enter amount (full or partial)
- Optional invoice selection
- Add notes for tracking
- Instant STK Push to parent's phone

#### 🔗 **Payment Links**
- Generate secure, shareable payment links
- Send via SMS, Email, or WhatsApp
- Set expiry dates
- Limit number of uses
- Track link status and usage
- Parents can pay at their convenience

#### 💰 **Pay Now from Invoices**
- "Pay Now" button on unpaid invoices
- Public payment page (no login required)
- Parents can edit phone number
- Support for partial payments
- Real-time payment confirmation

#### 📊 **Pay Now from Statement**
- "Pay Now" button on balance card
- Quick payment modal
- Full or partial payment options
- Phone number input
- Optional notes field

### Payment Tracking Features

#### 📱 **Payment Channels Tracked**
The system tracks and displays:
- **STK Push**: Admin-initiated payments
- **Payment Link**: Payments via shared links
- **Paybill Manual**: Direct C2B payments
- **Invoice Pay Now**: Payments from invoice view
- **Statement Pay Now**: Payments from statement view

#### 🧾 **Payment Details Stored**
- M-PESA receipt number
- Phone number used
- Transaction date and time
- Payment channel
- Initiator (if admin-prompted)
- Admin notes
- Payment link reference

#### 🔍 **Real-Time Updates**
- Instant statement updates
- Automatic payment allocation
- Receipt generation
- SMS notifications
- Email confirmations

### Administrative Features

#### 📈 **M-PESA Dashboard**
- Today's collections
- Transaction count
- Pending transactions
- Active payment links
- Recent transactions list
- Quick action buttons

#### 🔗 **Payment Link Management**
- Create payment links
- View all links (active/expired/used)
- Send links to parents
- Cancel links if needed
- Track link usage
- Generate reports

#### 📊 **Transaction Management**
- View transaction details
- Query transaction status
- Retry failed transactions
- Process refunds
- Export transaction data
- Reconciliation reports

### Parent-Facing Features

#### 📱 **Multiple Payment Entry Points**
Parents can pay from:
1. Invoice view ("Pay Now" button)
2. Student statement ("Pay Now" on balance)
3. Payment links (SMS/Email/WhatsApp)
4. Direct paybill (automatic detection)

#### ✏️ **Flexible Payment Options**
- Pay full amount
- Pay partial amount
- Edit phone number
- Choose payment method
- View payment history
- Download receipts

#### 🔒 **Secure Payment Process**
- HTTPS encryption
- M-PESA secure gateway
- No PIN storage
- Transaction verification
- Immediate confirmations

---

## System Requirements

### Server Requirements
- PHP 8.0 or higher
- Laravel 9.0 or higher
- MySQL 5.7+ or PostgreSQL 9.6+
- HTTPS/SSL certificate (for production)
- Public IP address (for webhooks)

### M-PESA Requirements
- Registered Safaricom Paybill/Till Number
- Daraja Portal account
- API credentials (Consumer Key & Secret)
- Passkey for STK Push
- KYC completion with Safaricom

### Network Requirements
- Outbound HTTPS access to api.safaricom.co.ke
- Inbound HTTPS access for webhooks
- Server IP whitelisted in Daraja portal
- Stable internet connection

---

# Part 2: Setup & Configuration

## Complete Setup Guide

### Prerequisites

Before starting, ensure you have:

1. ✅ **Safaricom Account**
   - Visit https://developer.safaricom.co.ke
   - Create an account
   - Verify your email

2. ✅ **Create Daraja App**
   - Log in to Daraja portal
   - Click "Create App"
   - Choose "Sandbox" for testing
   - Select "M-PESA Express (STK Push)"
   - Note your Consumer Key and Secret

3. ✅ **Laravel Application**
   - Working Laravel installation
   - Database configured
   - Basic authentication working

### Database Setup

#### Step 1: Run Migrations

```bash
php artisan migrate
```

**Migrations include:**
- `create_payment_links_table` - Payment link storage
- `add_payment_source_to_payments_table` - Payment channel tracking
- `add_mpesa_fields_to_payment_transactions_table` - M-PESA specific data

#### Step 2: Verify Tables

```bash
php artisan tinker
```

```php
// Check if tables exist
Schema::hasTable('payment_links'); // Should return true
Schema::hasTable('payments'); // Should return true
Schema::hasTable('payment_transactions'); // Should return true
```

### Environment Configuration

#### Step 1: Add M-PESA Variables to .env

```env
# =============================================================================
# M-PESA CONFIGURATION
# =============================================================================

# Environment: sandbox (testing) or production (live)
MPESA_ENVIRONMENT=sandbox

# API Credentials from Daraja Portal
MPESA_CONSUMER_KEY=your_consumer_key_here
MPESA_CONSUMER_SECRET=your_consumer_secret_here

# Business Shortcode (Paybill/Till Number)
# Sandbox: 174379 | Production: Your actual paybill number
MPESA_SHORTCODE=174379

# Passkey for STK Push
# Sandbox passkey (public, works for all sandbox apps)
MPESA_PASSKEY=bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919

# Initiator credentials (for B2C, reversals, etc.)
MPESA_INITIATOR_NAME=testapi
MPESA_INITIATOR_PASSWORD=Safaricom999!*!

# Callback URLs (must be publicly accessible)
MPESA_CALLBACK_URL=${APP_URL}/webhooks/payment/mpesa
MPESA_TIMEOUT_URL=${APP_URL}/webhooks/payment/mpesa/timeout
MPESA_RESULT_URL=${APP_URL}/webhooks/payment/mpesa/result
MPESA_QUEUE_TIMEOUT_URL=${APP_URL}/webhooks/payment/mpesa/queue-timeout
MPESA_VALIDATION_URL=${APP_URL}/webhooks/payment/mpesa/validation
MPESA_CONFIRMATION_URL=${APP_URL}/webhooks/payment/mpesa/confirmation

# Feature Toggles
MPESA_FEATURE_STK_PUSH=true
MPESA_FEATURE_C2B=true
MPESA_FEATURE_B2C=false
MPESA_FEATURE_REVERSAL=false

# Security
MPESA_VERIFY_WEBHOOK_IP=false  # Set to true in production

# Logging
MPESA_LOGGING_ENABLED=true
MPESA_LOG_LEVEL=info
```

#### Step 2: Configure for Local Testing with Ngrok

```bash
# Start ngrok
ngrok http 8000

# Output will show:
# Forwarding  https://abc123def456.ngrok.io -> http://localhost:8000

# Update .env with ngrok URL
MPESA_CALLBACK_URL=https://abc123def456.ngrok.io/webhooks/payment/mpesa
MPESA_TIMEOUT_URL=https://abc123def456.ngrok.io/webhooks/payment/mpesa/timeout
MPESA_RESULT_URL=https://abc123def456.ngrok.io/webhooks/payment/mpesa/result
```

#### Step 3: Clear Configuration Cache

```bash
php artisan config:cache
php artisan cache:clear
php artisan route:clear
```

### Payment Method Setup

#### Create M-PESA Payment Method

```bash
php artisan tinker
```

```php
\App\Models\PaymentMethod::firstOrCreate(
    ['code' => 'mpesa'],
    [
        'name' => 'M-PESA',
        'description' => 'M-PESA Mobile Money',
        'is_online' => true,
        'requires_reference' => true,
        'is_active' => true,
    ]
);
```

### Testing the Setup

#### Test 1: Check Configuration

```bash
php artisan tinker
```

```php
// Verify configuration
config('mpesa.environment'); // Should return 'sandbox'
config('mpesa.consumer_key'); // Should show your key
config('mpesa.shortcode'); // Should show 174379
config('mpesa.callback_url'); // Should show your callback URL
```

#### Test 2: Test Access Token

```bash
php artisan tinker
```

```php
$mpesa = app(\App\Services\PaymentGateways\MpesaGateway::class);
// This will test OAuth authentication
// Should not throw an error
```

#### Test 3: Send Test STK Push

1. Go to: **Finance → M-PESA Payments → Prompt Parent to Pay**
2. Select any student
3. Use test phone: `254708374149`
4. Amount: `10`
5. Click "Send Payment Request"

**Expected Result**: Success message and STK push received on test phone

---

## Environment Variables

### Complete .env Template

```env
# =============================================================================
# M-PESA DARAJA API CONFIGURATION
# =============================================================================

# -----------------------------------------------------------------------------
# Environment Selection
# -----------------------------------------------------------------------------
# Options: sandbox | production
MPESA_ENVIRONMENT=sandbox

# -----------------------------------------------------------------------------
# API Credentials (from Daraja Portal)
# -----------------------------------------------------------------------------
MPESA_CONSUMER_KEY=your_consumer_key_here
MPESA_CONSUMER_SECRET=your_consumer_secret_here
MPESA_SHORTCODE=174379
MPESA_PASSKEY=bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919
MPESA_INITIATOR_NAME=testapi
MPESA_INITIATOR_PASSWORD=Safaricom999!*!

# -----------------------------------------------------------------------------
# Callback URLs (Must be publicly accessible via HTTPS)
# -----------------------------------------------------------------------------
MPESA_CALLBACK_URL=${APP_URL}/webhooks/payment/mpesa
MPESA_TIMEOUT_URL=${APP_URL}/webhooks/payment/mpesa/timeout
MPESA_RESULT_URL=${APP_URL}/webhooks/payment/mpesa/result
MPESA_QUEUE_TIMEOUT_URL=${APP_URL}/webhooks/payment/mpesa/queue-timeout
MPESA_VALIDATION_URL=${APP_URL}/webhooks/payment/mpesa/validation
MPESA_CONFIRMATION_URL=${APP_URL}/webhooks/payment/mpesa/confirmation

# -----------------------------------------------------------------------------
# C2B Configuration
# -----------------------------------------------------------------------------
MPESA_C2B_RESPONSE_TYPE=Completed
MPESA_C2B_CONFIRMATION_REQUIRED=true

# -----------------------------------------------------------------------------
# Timeouts (in seconds)
# -----------------------------------------------------------------------------
MPESA_STK_PUSH_TIMEOUT=120
MPESA_TRANSACTION_QUERY_TIMEOUT=30
MPESA_DEFAULT_TIMEOUT=60

# -----------------------------------------------------------------------------
# Security Settings
# -----------------------------------------------------------------------------
MPESA_VERIFY_WEBHOOK_IP=false  # Set to true in production

# -----------------------------------------------------------------------------
# Logging
# -----------------------------------------------------------------------------
MPESA_LOGGING_ENABLED=true
MPESA_LOG_CHANNEL=daily
MPESA_LOG_LEVEL=info

# -----------------------------------------------------------------------------
# Feature Toggles
# -----------------------------------------------------------------------------
MPESA_FEATURE_STK_PUSH=true
MPESA_FEATURE_C2B=true
MPESA_FEATURE_B2C=false
MPESA_FEATURE_B2B=false
MPESA_FEATURE_REVERSAL=false
MPESA_FEATURE_ACCOUNT_BALANCE=false
```

### Variable Descriptions

| Variable | Required | Description | Example |
|----------|----------|-------------|---------|
| `MPESA_ENVIRONMENT` | Yes | Environment (sandbox/production) | `sandbox` |
| `MPESA_CONSUMER_KEY` | Yes | From Daraja portal | `abc123...` |
| `MPESA_CONSUMER_SECRET` | Yes | From Daraja portal | `xyz789...` |
| `MPESA_SHORTCODE` | Yes | Paybill/Till number | `174379` |
| `MPESA_PASSKEY` | Yes | For STK Push password | `bfb279...` |
| `MPESA_CALLBACK_URL` | Yes | Webhook URL | `https://...` |
| `MPESA_FEATURE_STK_PUSH` | No | Enable STK Push | `true` |

---

## Configuration Reference

### Config File: config/mpesa.php

The system includes a comprehensive configuration file with all settings:

```php
<?php
return [
    'environment' => env('MPESA_ENVIRONMENT', 'sandbox'),
    'consumer_key' => env('MPESA_CONSUMER_KEY'),
    'consumer_secret' => env('MPESA_CONSUMER_SECRET'),
    'shortcode' => env('MPESA_SHORTCODE'),
    'passkey' => env('MPESA_PASSKEY'),
    // ... more configuration
];
```

### Accessing Configuration

```php
// In your code
$environment = config('mpesa.environment');
$shortcode = config('mpesa.shortcode');
$stkPushUrl = config('mpesa.production_urls.stk_push');
```

### URL Configuration

#### Sandbox URLs
```php
'sandbox_urls' => [
    'oauth' => 'https://sandbox.safaricom.co.ke/oauth/v1/generate',
    'stk_push' => 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest',
    'stk_push_query' => 'https://sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query',
    // ... more endpoints
]
```

#### Production URLs
```php
'production_urls' => [
    'oauth' => 'https://api.safaricom.co.ke/oauth/v1/generate',
    'stk_push' => 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest',
    'stk_push_query' => 'https://api.safaricom.co.ke/mpesa/stkpushquery/v1/query',
    // ... 20+ endpoints configured
]
```

The system **automatically selects** the correct URLs based on `MPESA_ENVIRONMENT`.

---

## Getting Your Passkey

### For Sandbox (Testing)

Use the **default sandbox passkey** (publicly available):

```env
MPESA_PASSKEY=bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919
```

This works for all sandbox applications.

### For Production (Live)

The production passkey is **NOT** shown in the Daraja portal. You must request it:

#### Method 1: Email Safaricom

```
To: apisupport@safaricom.co.ke
Subject: Request for M-PESA Express Passkey - [Your School Name]

Dear Safaricom API Support,

I am requesting the M-PESA Express (STK Push) Passkey for my production app.

Details:
- Company Name: [Your School Name]
- App Name: [Your App Name from Daraja]
- Shortcode: [Your Paybill Number]
- Consumer Key: [First 10 characters]
- Contact Person: [Your Name]
- Phone: [Your Number]
- Email: [Your Email]

Please provide the passkey at your earliest convenience.

Thank you,
[Your Name]
```

#### Method 2: Call Safaricom

📞 **Phone**: +254 722 000 000
- Ask for "M-PESA API Support"
- Request "M-PESA Express Passkey"
- Provide your company details

#### Response Time
⏱️ **1-3 business days** for email
⚡ **Immediate** for phone (during business hours)

### What is the Passkey?

- **64-character hexadecimal string**
- Used to generate password for STK Push
- Different for sandbox and production
- Never expires (but can be regenerated if compromised)

### How It's Used

```php
// System automatically generates password
$timestamp = now()->format('YmdHis');
$password = base64_encode($shortcode . $passkey . $timestamp);
```

You don't need to do this manually - just set the passkey in `.env`.

---

# Part 3: User Guides

## Admin User Flows

### Flow 1: Admin-Initiated STK Push

**Scenario**: Admin wants to prompt a parent to pay fees

```
STEP 1: Navigate to M-PESA Dashboard
├─ Finance Menu
├─ M-PESA Payments
└─ Prompt Parent to Pay

STEP 2: Select Student
├─ Search student by name/admission number
├─ System loads family phone numbers
│  ├─ Father's Phone: 0712345678 (John Doe)
│  ├─ Mother's Phone: 0723456789 (Jane Doe)
│  └─ Primary Phone: 0734567890
└─ Select invoice (optional)

STEP 3: Choose Phone Number
├─ Option 1: Father's phone
├─ Option 2: Mother's phone
├─ Option 3: Primary phone
└─ Option 4: Enter different number

STEP 4: Enter Payment Details
├─ Amount: KES [____] (can be partial)
├─ Invoice: [Optional]
└─ Notes: [Optional admin notes]

STEP 5: Send Payment Request
├─ Click "Send Payment Request"
├─ System creates PaymentTransaction
└─ Initiates STK Push to selected phone

STEP 6: Parent Receives STK Push
├─ Parent's phone shows M-PESA prompt
├─ Parent enters M-PESA PIN
└─ Payment processed

STEP 7: Webhook Processing
├─ M-PESA sends callback
├─ System updates transaction status
├─ Creates Payment record (channel: stk_push)
├─ Auto-allocates to invoices
└─ Updates student statement

RESULT: Payment Recorded
├─ Statement shows "STK Push" badge
├─ M-PESA receipt number displayed
└─ Balance updated immediately
```

### Flow 2: Generate Payment Link

**Scenario**: Admin generates shareable payment link

```
STEP 1: Navigate to Generate Link
└─ Finance → M-PESA Payments → Generate Payment Link

STEP 2: Fill Link Details
├─ Student: [Select student]
├─ Invoice: [Optional]
├─ Amount: KES [____]
├─ Description: "Term 1 Fees"
├─ Expiry: [Date/Time]
└─ Max Uses: [1 or more]

STEP 3: Generate Link
├─ Click "Generate Payment Link"
├─ System creates PaymentLink record
├─ Generates unique token
└─ Creates shareable URL

STEP 4: Share Link
├─ Copy to clipboard
├─ Send via SMS
├─ Send via Email
└─ Share via WhatsApp

RESULT: Link Created
├─ Appears in "Active Payment Links"
├─ Parent can click to pay
└─ Admin can track usage
```

### Flow 3: View M-PESA Dashboard

```
DASHBOARD SECTIONS:

1. Statistics Cards
   ├─ Today's Collections (KES)
   ├─ Today's Transactions (Count)
   ├─ Pending Transactions
   └─ Active Payment Links

2. Quick Actions
   ├─ Prompt Parent to Pay (STK Push)
   ├─ Generate Payment Link
   └─ View All Payment Links

3. Recent Transactions (Last 20)
   ├─ Time | Student | Amount | Phone | Status
   ├─ View details button
   └─ Query status option

4. Active Payment Links (Latest 10)
   ├─ Student | Amount | Actions
   ├─ View link details
   └─ Send link again
```

---

## Parent User Flows

### Flow 1: Pay from Invoice

**Scenario**: Parent clicks "Pay Now" on an invoice

```
STEP 1: View Invoice
├─ Parent/Admin views invoice
├─ Shows outstanding balance
└─ "Pay Now" button visible if balance > 0

STEP 2: Click Pay Now
├─ Redirects to invoice payment page
├─ No login required (public page)
└─ Shows invoice details

STEP 3: Payment Page Display
┌─────────────────────────────────┐
│ Invoice: INV-2024-001           │
│ Student: John Doe (ADM-001)     │
│ Balance: KES 15,000.00          │
│                                 │
│ Amount: [15000.00] (Editable)  │
│ [Pay Full] [Pay Half]          │
│                                 │
│ Phone: [0712345678] [Edit 📝]   │
│ ℹ️ Can change phone number      │
│                                 │
│ [🔒 PAY NOW WITH M-PESA]        │
└─────────────────────────────────┘

STEP 4: Customize (Optional)
├─ Edit amount (partial payment)
├─ Change phone number
└─ Click quick amount buttons

STEP 5: Initiate Payment
├─ Click "PAY NOW WITH M-PESA"
├─ System validates inputs
└─ Sends STK Push request

STEP 6: Complete Payment
├─ Parent receives STK Push
├─ Enters M-PESA PIN
└─ Confirms payment

RESULT: Payment Processed
├─ Success message displayed
├─ Payment recorded (channel: payment_link)
├─ Invoice balance updated
└─ Receipt generated
```

### Flow 2: Pay from Statement

**Scenario**: Parent pays balance from student statement

```
STEP 1: View Statement
└─ Navigate to student fee statement

STEP 2: Balance Card
┌─────────────────────────────┐
│ Balance                     │
│ Ksh 15,000.00              │
│                            │
│ [💰 Pay Now] ← Click here  │
└─────────────────────────────┘

STEP 3: Payment Modal Opens
┌───────────────────────────────┐
│ Pay Outstanding Balance       │
│                              │
│ Balance: KES 15,000.00       │
│                              │
│ Amount: [15000] (Editable)  │
│ Max: KES 15,000.00          │
│                              │
│ Phone: [0712345678]         │
│                              │
│ Notes: [Optional]           │
│                              │
│ [Cancel] [📱 Send Request]  │
└───────────────────────────────┘

STEP 4: Process Payment
├─ Click "Send Payment Request"
├─ System initiates STK Push
├─ Parent receives prompt
└─ Enters PIN and confirms

RESULT: Balance Updated
├─ Statement refreshes
├─ New payment appears
├─ Shows "STK Push" badge
└─ Balance reduced
```

### Flow 3: Pay via Payment Link

**Scenario**: Parent receives and uses payment link

```
STEP 1: Receive Link
├─ Parent gets SMS/Email/WhatsApp
└─ Message: "Pay KES 15,000 for John. Click: https://..."

STEP 2: Click Link
├─ Opens in browser
├─ No login required
└─ Beautiful payment page

STEP 3: Payment Page
┌──────────────────────────────────┐
│     [M-PESA LOGO]               │
│   School Fee Payment            │
│                                 │
│ Student: John Doe               │
│ Class: Form 1A                  │
│ Description: Term 1 Fees        │
│                                 │
│ Amount: KES 15,000.00          │
│                                 │
│ Pay: [15000] (Editable)        │
│ [Pay Full] [Pay Half]          │
│                                 │
│ Phone: [0712345678] [Edit 📝]   │
│ ℹ️ Change if paying from        │
│    different account            │
│                                 │
│ [🔒 PAY NOW WITH M-PESA]        │
│                                 │
│ 🛡️ Secure Payment Gateway       │
└──────────────────────────────────┘

STEP 4: Customize Payment
├─ Edit amount (partial)
├─ Change phone number
└─ Use quick buttons

STEP 5: Initiate
├─ Click "PAY NOW"
├─ Button shows loading
└─ "Processing..." message

STEP 6: Receive STK Push
┌──────────────────────────────┐
│ ✅ Payment request sent!      │
│                              │
│ Check your phone and enter   │
│ M-PESA PIN to complete.      │
│                              │
│ Page will refresh when       │
│ payment is confirmed.        │
└──────────────────────────────┘

STEP 7: Complete on Phone
├─ Phone shows M-PESA prompt
├─ "Pay KES 15,000 to [School]"
├─ Enter M-PESA PIN
└─ Confirm payment

STEP 8: Confirmation
├─ M-PESA SMS confirmation
├─ Webhook updates system
└─ Page auto-refreshes

RESULT: Success Page
┌────────────────────────────┐
│ ✅ Payment Successful!     │
│                           │
│ Receipt: REC-2024-001     │
│ M-PESA: ABC123XYZ        │
│ Amount: KES 15,000       │
│ Date: 08 Jan 2025        │
│                           │
│ Thank you!               │
│                           │
│ [📄 Receipt] [🏠 Home]    │
└────────────────────────────┘
```

---

## Payment Channels

The system tracks different payment sources with distinct badges:

### 1. STK Push (Admin-Initiated)
- **Badge**: 🟢 STK Push
- **Triggered by**: Admin prompts payment
- **Channel Code**: `stk_push`
- **Shows**: Admin who initiated, notes

### 2. Payment Link
- **Badge**: 🔗 Payment Link
- **Triggered by**: Parent clicks payment link
- **Channel Code**: `payment_link`
- **Shows**: Link used, expiry status

### 3. Paybill Manual
- **Badge**: 💵 Paybill
- **Triggered by**: Direct C2B payment
- **Channel Code**: `paybill_manual`
- **Shows**: Paybill number used

### 4. Invoice Pay Now
- **Badge**: 📄 Invoice Payment
- **Triggered by**: Payment from invoice page
- **Channel Code**: `payment_link`
- **Shows**: Invoice number

### 5. Statement Pay Now
- **Badge**: 📊 Statement Payment
- **Triggered by**: Payment from statement
- **Channel Code**: `stk_push`
- **Shows**: Balance paid

### Statement Display

```
Transaction History
┌──────────┬─────────┬────────┬─────────────────────┐
│ Date     │ Type    │ Amount │ Source              │
├──────────┼─────────┼────────┼─────────────────────┤
│ 08/01/25 │ Payment │ 15,000 │ [📱 STK Push]       │
│          │         │        │ Ref: ABC123XYZ      │
├──────────┼─────────┼────────┼─────────────────────┤
│ 07/01/25 │ Payment │ 10,000 │ [🔗 Payment Link]   │
│          │         │        │ Ref: DEF456UVW      │
├──────────┼─────────┼────────┼─────────────────────┤
│ 06/01/25 │ Payment │ 5,000  │ [💵 Paybill]        │
│          │         │        │ Ref: GHI789RST      │
└──────────┴─────────┴────────┴─────────────────────┘
```

---

## Partial Payments

All payment methods support partial payments. Parents can pay any amount from KES 1 up to the outstanding balance.

### How It Works

#### 1. Set Custom Amount
```
Balance: KES 15,000.00

Amount to Pay: [_____]
Min: KES 1 | Max: KES 15,000

Quick Options:
[Pay Full - 15,000] [Pay Half - 7,500]
```

#### 2. System Validates
- Amount must be ≥ 1
- Amount must be ≤ balance
- Real-time validation feedback

#### 3. Payment Allocation
System automatically allocates partial payments:
1. To oldest unpaid invoice first
2. Reduces invoice balance
3. Updates overall balance
4. Generates receipt for amount paid

#### 4. Balance Tracking
```
Before Payment:
├─ Invoice 1: KES 10,000 (unpaid)
├─ Invoice 2: KES 5,000 (unpaid)
└─ Total Balance: KES 15,000

Parent Pays: KES 7,000

After Payment:
├─ Invoice 1: KES 3,000 (partial)
├─ Invoice 2: KES 5,000 (unpaid)
└─ Total Balance: KES 8,000
```

### Partial Payment Examples

#### Example 1: Pay Half
```
Balance: KES 10,000
Pay: KES 5,000
Remaining: KES 5,000
```

#### Example 2: Pay What You Can
```
Balance: KES 15,000
Pay: KES 3,000 (Custom amount)
Remaining: KES 12,000
```

#### Example 3: Multiple Partial Payments
```
Day 1: Balance KES 20,000 → Pay KES 5,000 → Remaining KES 15,000
Day 2: Balance KES 15,000 → Pay KES 7,000 → Remaining KES 8,000
Day 3: Balance KES 8,000 → Pay KES 8,000 → Remaining KES 0 (Paid!)
```

---

# Part 4: Production Deployment

## Pre-Deployment Checklist

Before deploying to production, verify:

### 1. Technical Requirements
- [ ] ✅ Valid SSL certificate installed
- [ ] ✅ Public static IP address available
- [ ] ✅ Server firewall configured
- [ ] ✅ HTTPS working on all URLs
- [ ] ✅ Database backups configured
- [ ] ✅ Monitoring tools set up

### 2. M-PESA Requirements
- [ ] ✅ Registered Paybill/Till number
- [ ] ✅ KYC completed with Safaricom
- [ ] ✅ Production app created on Daraja portal
- [ ] ✅ Production credentials received
- [ ] ✅ Passkey obtained from Safaricom
- [ ] ✅ Server IP whitelisted in Daraja

### 3. Testing Requirements
- [ ] ✅ Sandbox testing complete
- [ ] ✅ All features tested
- [ ] ✅ Error handling verified
- [ ] ✅ Receipt generation working
- [ ] ✅ SMS notifications working
- [ ] ✅ Email notifications working

### 4. Documentation Requirements
- [ ] ✅ Admin training completed
- [ ] ✅ Support team trained
- [ ] ✅ Parent communication prepared
- [ ] ✅ FAQ document ready
- [ ] ✅ Troubleshooting guide available

### 5. Backup & Recovery
- [ ] ✅ Backup procedure documented
- [ ] ✅ Rollback plan prepared
- [ ] ✅ Database backup tested
- [ ] ✅ Recovery tested
- [ ] ✅ Emergency contacts list ready

---

## Getting Production Credentials

### Step 1: Register on Daraja Portal

1. Visit https://developer.safaricom.co.ke
2. Create account (if not done)
3. Verify email address
4. Complete profile

### Step 2: Create Production App

1. Click **"Create App"**
2. Fill in details:
   - **App Name**: [Your School] Management System
   - **Description**: School fee payment system
   - **Environment**: **Production** ⚠️
3. Select APIs:
   - ✅ M-PESA Express (STK Push) - **REQUIRED**
   - ✅ C2B (Optional)
   - ✅ Transaction Status (Optional)
   - ✅ Account Balance (Optional)
   - ✅ Reversal (Optional)

### Step 3: Note Your Credentials

After creating app, save:
- **Consumer Key**: `xxxxxxxxxxxxxxxxxxxxxx`
- **Consumer Secret**: `yyyyyyyyyyyyyyyyyyyyyyy`
- **Shortcode**: Your business paybill number
- **Passkey**: Request from Safaricom (see below)

### Step 4: Request Passkey

**Email Template**:

```
To: apisupport@safaricom.co.ke
Subject: Request for M-PESA Express Passkey - [School Name]

Dear Safaricom API Support,

I am requesting the M-PESA Express (STK Push) Passkey for production.

Company Details:
- Company Name: [School Name]
- Registration Number: [If applicable]
- App Name: [From Daraja Portal]
- Shortcode: [Your Paybill Number]
- Consumer Key: [First 10 characters]

Contact Information:
- Name: [Your Full Name]
- Position: [Your Title]
- Phone: +254 [Your Number]
- Email: [Your Email]

Expected Go-Live Date: [Date]

Thank you for your assistance.

Best regards,
[Your Name]
[School Name]
```

**Processing Time**: 1-3 business days

### Step 5: Request Additional APIs (If Needed)

Some APIs require manual approval:

**Email Template**:

```
To: apisupport@safaricom.co.ke
Subject: Production API Access Request - [School Name]

Dear Safaricom API Support,

I request access to the following APIs for production:
- Transaction Status Query
- Account Balance Query
- Reversal API

Company: [School Name]
Paybill: [Number]
App Name: [From Portal]
Expected Transaction Volume: [e.g., 500/month]

Use Case: School fee collection and reconciliation

Thank you,
[Your Name]
```

**Processing Time**: 3-5 business days

---

## Production URLs Configuration

### All Production URLs Pre-Configured

When you set `MPESA_ENVIRONMENT=production`, the system automatically uses:

| Feature | Production URL |
|---------|---------------|
| OAuth Token | `https://api.safaricom.co.ke/oauth/v1/generate` |
| STK Push | `https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest` |
| STK Query | `https://api.safaricom.co.ke/mpesa/stkpushquery/v1/query` |
| C2B Register | `https://api.safaricom.co.ke/mpesa/c2b/v1/registerurl` |
| Transaction Status | `https://api.safaricom.co.ke/mpesa/transactionstatus/v1/query` |
| Account Balance | `https://api.safaricom.co.ke/mpesa/accountbalance/v1/query` |
| Reversal | `https://api.safaricom.co.ke/mpesa/reversal/v1/request` |

**20+ URLs configured** - No manual URL updates needed!

### Callback URL Requirements

Your callback URLs must:
- ✅ Use HTTPS (SSL certificate required)
- ✅ Be publicly accessible
- ✅ Respond within 30 seconds
- ✅ Return HTTP 200 status
- ✅ Be whitelisted in Daraja portal

**Production Callback URLs**:
```
https://yourdomain.com/webhooks/payment/mpesa
https://yourdomain.com/webhooks/payment/mpesa/timeout
https://yourdomain.com/webhooks/payment/mpesa/result
https://yourdomain.com/webhooks/payment/mpesa/queue-timeout
https://yourdomain.com/webhooks/payment/mpesa/validation
https://yourdomain.com/webhooks/payment/mpesa/confirmation
```

### Whitelist Your Server IP

1. Log in to Daraja Portal
2. Go to your production app
3. Click **"IP Whitelist"**
4. Add your server's public IP
5. Save changes

**Find your IP**:
```bash
curl ifconfig.me
```

---

## Deployment Steps

### Step 1: Backup Everything

```bash
# Backup database
php artisan backup:run --only-db

# Backup code
git stash
git pull origin main
```

### Step 2: Update .env for Production

```env
# Switch to production
MPESA_ENVIRONMENT=production

# Production credentials
MPESA_CONSUMER_KEY=your_production_consumer_key
MPESA_CONSUMER_SECRET=your_production_consumer_secret
MPESA_SHORTCODE=your_actual_paybill_number
MPESA_PASSKEY=your_production_passkey_from_safaricom

# Production callbacks (HTTPS required!)
MPESA_CALLBACK_URL=https://yourdomain.com/webhooks/payment/mpesa
MPESA_TIMEOUT_URL=https://yourdomain.com/webhooks/payment/mpesa/timeout
MPESA_RESULT_URL=https://yourdomain.com/webhooks/payment/mpesa/result

# Enable security
MPESA_VERIFY_WEBHOOK_IP=true

# Production logging
MPESA_LOGGING_ENABLED=true
MPESA_LOG_LEVEL=info
```

### Step 3: Verify Configuration

```bash
# Clear caches
php artisan config:cache
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Verify configuration
php artisan tinker
```

```php
// Check environment
config('mpesa.environment'); // Should be 'production'

// Check URLs
config('mpesa.production_urls.oauth');
// Should be: https://api.safaricom.co.ke/oauth/v1/generate

config('mpesa.production_urls.stk_push');
// Should be: https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest

// Check credentials
config('mpesa.consumer_key'); // Your production key
config('mpesa.shortcode'); // Your paybill number
```

### Step 4: Test Access Token

```bash
php artisan tinker
```

```php
$mpesa = app(\App\Services\PaymentGateways\MpesaGateway::class);
// Should not throw an error
// If it works, OAuth is configured correctly
```

### Step 5: Register C2B URLs (If Using C2B)

```bash
php artisan tinker
```

```php
$mpesa = app(\App\Services\PaymentGateways\MpesaGateway::class);
$result = $mpesa->registerC2BUrls();
dd($result);
// Should show success message
```

### Step 6: Test with Small Amount

1. Go to M-PESA Dashboard
2. Click "Prompt Parent to Pay"
3. **Use your own phone number**
4. Amount: **KES 10** (small test)
5. Send payment request
6. Complete payment on your phone
7. Verify:
   - Payment received
   - Webhook processed
   - Payment recorded
   - Statement updated
   - Receipt generated

### Step 7: Monitor First Transactions

```bash
# Watch logs in real-time
tail -f storage/logs/laravel.log | grep MPESA

# Check recent transactions
php artisan tinker
>>> \App\Models\PaymentTransaction::where('gateway', 'mpesa')
    ->whereDate('created_at', today())
    ->get();
```

---

## Go-Live Process

### Phase 1: Internal Testing (Days 1-3)

**Participants**: 5-10 staff members

**Process**:
1. Send announcement to staff
2. Each makes a test payment
3. Use real amounts but small (KES 100-500)
4. Monitor all transactions closely
5. Fix any issues immediately

**Success Criteria**:
- 100% success rate
- No webhook issues
- Receipts generated correctly
- SMS notifications working

### Phase 2: Pilot Group (Days 4-7)

**Participants**: 20-30 selected parents

**Process**:
1. Select tech-savvy, cooperative parents
2. Send personal invitation
3. Provide direct support contact
4. Monitor each transaction
5. Collect feedback

**Success Criteria**:
- >95% success rate
- Positive feedback
- Issues resolved quickly
- Support system working

### Phase 3: Grade-Level Rollout (Days 8-21)

**Process**:
1. **Week 1**: Grade 1 only
2. **Week 2**: Grades 1-3
3. **Week 3**: All grades

**For each phase**:
1. Send announcement 2 days before
2. Monitor transactions hourly
3. Respond to issues within 1 hour
4. Daily reconciliation
5. Adjust based on feedback

**Success Criteria**:
- >95% success rate maintained
- Decreasing support requests
- Positive parent feedback
- Smooth operations

### Phase 4: Full Launch (Day 22+)

**Process**:
1. Announce to all remaining parents
2. Continue intensive monitoring (first week)
3. Gradually reduce to normal monitoring
4. Collect and analyze data
5. Make improvements

**Ongoing**:
- Daily reconciliation
- Weekly reports
- Monthly reviews
- Quarterly audits

---

## Monitoring & Logging

### Daily Monitoring Tasks

#### 1. Check Transaction Success Rate

```bash
php artisan tinker
```

```php
$today = today();
$total = \App\Models\PaymentTransaction::where('gateway', 'mpesa')
    ->whereDate('created_at', $today)
    ->count();

$successful = \App\Models\PaymentTransaction::where('gateway', 'mpesa')
    ->whereDate('created_at', $today)
    ->where('status', 'completed')
    ->count();

$successRate = $total > 0 ? ($successful / $total) * 100 : 0;

echo "Today: {$successful}/{$total} = {$successRate}%\n";
// Target: >95%
```

#### 2. Monitor Failed Transactions

```php
$failed = \App\Models\PaymentTransaction::where('gateway', 'mpesa')
    ->whereDate('created_at', today())
    ->where('status', 'failed')
    ->get();

foreach ($failed as $txn) {
    echo "Failed: {$txn->student->full_name} - {$txn->failure_reason}\n";
}
```

#### 3. Check Pending Transactions

```php
// Transactions pending >30 minutes
$stuck = \App\Models\PaymentTransaction::where('gateway', 'mpesa')
    ->where('status', 'pending')
    ->where('created_at', '<', now()->subMinutes(30))
    ->get();

if ($stuck->count() > 0) {
    echo "WARNING: {$stuck->count()} stuck transactions!\n";
}
```

#### 4. View Recent Activity

```bash
# Live log monitoring
tail -f storage/logs/laravel.log | grep "MPESA"

# Today's payments summary
php artisan tinker
>>> \App\Models\Payment::whereDate('created_at', today())
    ->where('payment_method_id', 
        \App\Models\PaymentMethod::where('code', 'mpesa')->first()->id)
    ->sum('amount');
```

### Weekly Tasks

#### 1. Reconciliation Report

```php
// Compare system records with M-PESA statement
$systemTotal = \App\Models\Payment::whereBetween('payment_date', [
    now()->startOfWeek(),
    now()->endOfWeek()
])->where('payment_channel', 'like', '%mpesa%')
  ->sum('amount');

echo "System Total: KES " . number_format($systemTotal, 2) . "\n";
// Compare with M-PESA statement from portal
```

#### 2. Performance Metrics

```php
$metrics = [
    'total_transactions' => PaymentTransaction::where('gateway', 'mpesa')
        ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
        ->count(),
    'successful' => PaymentTransaction::where('gateway', 'mpesa')
        ->where('status', 'completed')
        ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
        ->count(),
    'total_amount' => Payment::whereBetween('payment_date', [now()->startOfWeek(), now()->endOfWeek()])
        ->sum('amount'),
    'average_response_time' => '...' // From logs
];
```

### Alerting

Set up alerts for:

**Critical (Immediate)**:
- Success rate <90%
- No transactions for >1 hour (during business hours)
- Webhook errors >10 in 1 hour
- System exceptions

**Warning (Within 1 hour)**:
- Success rate <95%
- Pending transactions >30 minutes
- Failed transactions >5%

**Info (Daily)**:
- Daily transaction summary
- Reconciliation status
- Performance metrics

---

## Troubleshooting

### Common Issues

#### 1. "Invalid Access Token"

**Symptoms**:
- STK Push fails immediately
- Error: "Invalid Access Token" or "Authentication failed"

**Causes**:
- Wrong Consumer Key/Secret
- Environment mismatch
- Expired token

**Solution**:
```bash
php artisan tinker
```

```php
// Verify credentials
config('mpesa.consumer_key');
config('mpesa.consumer_secret');
config('mpesa.environment'); // Should be 'production'

// Test token generation
$mpesa = app(\App\Services\PaymentGateways\MpesaGateway::class);
// Will throw error if credentials are wrong
```

**Fix**:
1. Verify credentials in Daraja portal
2. Ensure using production credentials
3. Clear config cache: `php artisan config:cache`

#### 2. "Callback URL Not Reachable"

**Symptoms**:
- STK Push sent but no webhook received
- Payment completes but system not updated

**Causes**:
- URL not publicly accessible
- HTTPS not working
- Firewall blocking Safaricom IPs
- Server not responding

**Solution**:
```bash
# Test callback URL externally
curl -X POST https://yourdomain.com/webhooks/payment/mpesa \
  -H "Content-Type: application/json" \
  -d '{"test": "data"}'

# Should return HTTP 200

# Check server logs
tail -f /var/log/nginx/error.log
tail -f storage/logs/laravel.log
```

**Fix**:
1. Verify HTTPS certificate is valid
2. Test URL is accessible from internet
3. Check firewall allows Safaricom IPs:
   - 196.201.214.200
   - 196.201.214.206
   - 196.201.213.114
   - (and 9 more - see config)
4. Ensure webhook route exists
5. Check server has enough resources

#### 3. "Transaction Timeout"

**Symptoms**:
- User completes payment
- System doesn't update after 2-3 minutes

**Causes**:
- Webhook processing failing
- Database connection issue
- Queue not running
- Payment allocation error

**Solution**:
```bash
# Check webhook logs
grep "MPESA.*webhook" storage/logs/laravel.log | tail -20

# Check for errors
grep "MPESA.*ERROR" storage/logs/laravel.log | tail -20

# Verify transaction in database
php artisan tinker
>>> $txn = \App\Models\PaymentTransaction::latest()->first();
>>> $txn->status;
>>> $txn->webhook_data;
```

**Fix**:
1. Ensure queues are running: `php artisan queue:work`
2. Check database connection
3. Manually process stuck transaction:
   ```php
   $txn = PaymentTransaction::find($id);
   // Check M-PESA status
   $mpesa = app(\App\Services\PaymentGateways\MpesaGateway::class);
   $status = $mpesa->verifyPayment($txn->transaction_id);
   ```

#### 4. "Invalid Phone Number"

**Symptoms**:
- STK Push fails immediately
- Error: "Invalid phone number"

**Causes**:
- Wrong format (must be 254XXXXXXXXX)
- Not a Kenyan number
- Number not M-PESA registered

**Solution**:
```php
// Test phone formatting
$phone = '0712345678';
$formatted = \App\Services\PaymentGateways\MpesaGateway::formatPhoneNumber($phone);
echo $formatted; // Should be 254712345678

// Validate phone
$isValid = \App\Services\PaymentGateways\MpesaGateway::isValidKenyanPhone($phone);
```

**Fix**:
1. Ensure phone format: 254XXXXXXXXX
2. Verify phone is M-PESA registered
3. Check phone network (Safaricom, Airtel, Telkom)

#### 5. "Duplicate Transaction"

**Symptoms**:
- Same payment recorded twice
- Balance deducted multiple times

**Causes**:
- Webhook called multiple times
- No unique constraint on receipt number
- Race condition in processing

**Solution**:
```bash
# Check for duplicates
php artisan tinker
```

```php
$duplicates = \App\Models\Payment::selectRaw('mpesa_receipt_number, COUNT(*) as count')
    ->whereNotNull('mpesa_receipt_number')
    ->groupBy('mpesa_receipt_number')
    ->having('count', '>', 1)
    ->get();
```

**Fix**:
1. Add unique constraint:
   ```php
   Schema::table('payments', function (Blueprint $table) {
       $table->unique('mpesa_receipt_number');
   });
   ```
2. Implement idempotency check in webhook handler
3. Reverse duplicate payments manually

---

## Security Best Practices

### 1. Credential Management

**DO**:
- ✅ Use environment variables
- ✅ Different credentials for sandbox/production
- ✅ Store securely in password manager
- ✅ Rotate credentials periodically
- ✅ Limit access to credentials

**DON'T**:
- ❌ Commit credentials to Git
- ❌ Share credentials via email
- ❌ Use production credentials in sandbox
- ❌ Hard-code credentials
- ❌ Log credentials

### 2. HTTPS Enforcement

```php
// In AppServiceProvider.php
public function boot()
{
    if (config('app.env') === 'production') {
        URL::forceScheme('https');
    }
}
```

### 3. Webhook IP Verification

The system automatically verifies Safaricom IPs when enabled:

```env
MPESA_VERIFY_WEBHOOK_IP=true  # Enable in production
```

Allowed IPs (pre-configured):
- 196.201.214.200
- 196.201.214.206
- 196.201.213.114
- ... and 9 more

### 4. Rate Limiting

```php
// routes/web.php
Route::post('/webhooks/payment/mpesa', [PaymentWebhookController::class, 'handleMpesa'])
    ->middleware('throttle:60,1'); // 60 requests per minute
```

### 5. Database Security

```php
// Encrypt sensitive fields
protected $casts = [
    'phone_number' => 'encrypted',
    'mpesa_phone_number' => 'encrypted',
];
```

### 6. Logging Sensitive Data

**DO**:
- ✅ Log transaction IDs
- ✅ Log amounts
- ✅ Log statuses
- ✅ Log timestamps

**DON'T**:
- ❌ Log M-PESA PINs (never sent to you anyway)
- ❌ Log full phone numbers in public logs
- ❌ Log consumer secrets
- ❌ Log passkeys

### 7. Access Control

```php
// Restrict M-PESA routes
Route::middleware(['auth', 'role:Super Admin,Admin,Finance'])
    ->prefix('finance/mpesa')
    ->group(function () {
        // M-PESA routes
    });
```

### 8. Audit Trail

Log all M-PESA operations:
- Who initiated payment
- When it was initiated
- What amount
- Which student
- What happened (success/failure)

---

## API Reference

### MpesaGateway Methods

#### `initiatePayment($transaction, $options)`

Initiate STK Push payment.

**Parameters**:
- `$transaction` (PaymentTransaction): Transaction record
- `$options` (array): Options including phone_number

**Returns**: `array`
```php
[
    'success' => true/false,
    'checkout_request_id' => 'ws_CO_...',
    'message' => 'STK Push sent successfully'
]
```

**Example**:
```php
$mpesa = app(\App\Services\PaymentGateways\MpesaGateway::class);
$result = $mpesa->initiatePayment($transaction, [
    'phone_number' => '254712345678'
]);
```

#### `verifyPayment($transactionId)`

Query STK Push status.

**Parameters**:
- `$transactionId` (string): CheckoutRequestID

**Returns**: `array`

**Example**:
```php
$status = $mpesa->verifyPayment('ws_CO_08012025123045');
```

#### `queryTransactionStatus($transactionId)`

Query any M-PESA transaction status.

**Parameters**:
- `$transactionId` (string): M-PESA receipt number

**Returns**: `array`

**Example**:
```php
$status = $mpesa->queryTransactionStatus('ABC123XYZ');
```

#### `registerC2BUrls()`

Register C2B validation and confirmation URLs.

**Returns**: `array`

**Example**:
```php
$result = $mpesa->registerC2BUrls();
```

#### `queryAccountBalance()`

Check M-PESA account balance.

**Returns**: `array`

**Example**:
```php
$balance = $mpesa->queryAccountBalance();
// Result sent to callback URL
```

#### `reverseTransaction($transactionId, $amount, $remarks)`

Reverse/refund a payment.

**Parameters**:
- `$transactionId` (string): M-PESA receipt number
- `$amount` (float): Amount to reverse
- `$remarks` (string): Reason for reversal

**Returns**: `array`

**Example**:
```php
$result = $mpesa->reverseTransaction('ABC123XYZ', 1000, 'Duplicate payment');
```

---

# Part 6: Advanced Topics

## Additional Features

### 1. Transaction Status Query

Query the status of any M-PESA transaction:

```php
$mpesa = app(\App\Services\PaymentGateways\MpesaGateway::class);
$status = $mpesa->queryTransactionStatus('ABC123XYZ');
```

**Use Cases**:
- Verify old transactions
- Check payment status for reconciliation
- Investigate disputed payments

### 2. C2B Direct Paybill

Enable parents to pay directly to paybill:

**Setup (One-time)**:
```php
$mpesa = app(\App\Services\PaymentGateways\MpesaGateway::class);
$result = $mpesa->registerC2BUrls();
```

**How It Works**:
1. Parent goes to M-PESA menu
2. Selects "Lipa na M-PESA"
3. Selects "Paybill"
4. Enters your business number
5. Account: Student admission number
6. Amount: Any amount
7. System auto-detects and allocates

### 3. Account Balance Monitoring

Check your M-PESA balance:

```php
$mpesa = app(\App\Services\PaymentGateways\MpesaGateway::class);
$mpesa->queryAccountBalance();
```

Result sent to callback URL for security.

**Dashboard Display**:
```php
// Display on admin dashboard
Route::get('/admin/mpesa/balance', function() {
    // Retrieve from last callback
    $lastBalance = Cache::get('mpesa_balance');
    return view('admin.mpesa-balance', compact('lastBalance'));
});
```

### 4. Payment Reversals

Reverse payments for refunds:

```php
$mpesa = app(\App\Services\PaymentGateways\MpesaGateway::class);
$result = $mpesa->reverseTransaction(
    'ABC123XYZ',  // M-PESA receipt
    1000,         // Amount
    'Duplicate payment - refund requested'
);
```

**Requirements**:
- Reversal API access from Safaricom
- Within reversal window (usually 24 hours)
- Valid reason

### 5. Bulk Payment Links

Generate links for multiple students:

```php
$students = Student::where('classroom_id', $classroomId)->get();

foreach ($students as $student) {
    $link = PaymentLink::create([
        'student_id' => $student->id,
        'amount' => 10000,
        'description' => 'Term 1 Fees',
        'expires_at' => now()->addDays(30),
        // ...
    ]);
    
    // Send via SMS
    SMS::send($student->family->phone, 
        "Pay fees for {$student->full_name}: " . $link->getUrl()
    );
}
```

---

## Scaling Considerations

### When to Scale

Monitor these metrics:

| Metric | Warning | Critical |
|--------|---------|----------|
| Response Time | >1s | >3s |
| CPU Usage | >70% | >85% |
| Memory Usage | >80% | >90% |
| Queue Backlog | >100 | >500 |
| Success Rate | <95% | <90% |

### Scaling Strategies

#### 1. Vertical Scaling

Upgrade server resources:
- More CPU cores
- More RAM
- Faster disk (SSD)

**When**: Small to medium schools (<2000 students)

#### 2. Horizontal Scaling

Add more application servers:
```
Load Balancer
├─ App Server 1
├─ App Server 2
└─ App Server 3
```

**When**: Large schools (>2000 students)

#### 3. Database Optimization

```php
// Add indexes
Schema::table('payments', function (Blueprint $table) {
    $table->index('payment_date');
    $table->index('student_id');
    $table->index('mpesa_receipt_number');
});

// Use read replicas
config(['database.connections.mysql_read' => [...]]);
```

#### 4. Caching

```php
// Cache expensive queries
$balance = Cache::remember('student_'.$id.'_balance', 3600, function() use ($id) {
    return Student::find($id)->calculateBalance();
});
```

#### 5. Queue Processing

```bash
# Run multiple queue workers
supervisorctl start laravel-worker:*

# Scale workers based on load
php artisan queue:work --max-jobs=1000
```

---

## Compliance & Regulations

### Data Protection

#### What Data is Stored

**Personal Data**:
- Phone numbers (encrypted)
- Student names
- Family relationships

**Financial Data**:
- Payment amounts
- Transaction dates
- M-PESA receipt numbers

#### GDPR/Data Protection Act Compliance

```php
// Data export (user request)
Route::get('/my-data/export', function() {
    $user = auth()->user();
    $data = [
        'payments' => Payment::where('user_id', $user->id)->get(),
        'transactions' => PaymentTransaction::where('user_id', $user->id)->get(),
    ];
    
    return response()->json($data)->download('my-data.json');
});

// Data deletion (user request)
Route::post('/my-data/delete', function() {
    $user = auth()->user();
    // Anonymize instead of delete (for audit trail)
    Payment::where('user_id', $user->id)
        ->update(['phone_number' => 'DELETED']);
});
```

### PCI DSS Compliance

**What M-PESA Handles**:
- Payment processing
- PIN collection
- Card data (if applicable)

**What You Handle**:
- Transaction records
- Phone numbers
- Receipt generation

**Your Responsibilities**:
- ✅ Use HTTPS for all communications
- ✅ Encrypt sensitive data at rest
- ✅ Implement access controls
- ✅ Maintain audit logs
- ✅ Regular security audits

### Financial Reporting

#### Daily Reconciliation
```php
// Automated daily reconciliation
Schedule::daily()->at('23:00', function() {
    $reconciliation = new MpesaReconciliation();
    $report = $reconciliation->reconcileDay(today());
    
    // Email report to finance team
    Mail::to('finance@school.com')->send(
        new DailyReconciliationReport($report)
    );
});
```

#### Monthly Reports
- Total collections
- Payment methods breakdown
- Outstanding balances
- Payment trends

### Audit Requirements

**What to Log**:
- All payment attempts
- All successful payments
- All failures and reasons
- All refunds/reversals
- Administrative actions

**Retention Period**:
- Transaction logs: 7 years (minimum)
- Financial records: As per local regulations
- Audit trails: Indefinitely

---

## Conclusion

This master guide covers everything you need for M-PESA integration:

✅ **Quick start** in 5 minutes
✅ **Complete setup** guide
✅ **User flows** for admins and parents
✅ **Production deployment** process
✅ **Monitoring and troubleshooting**
✅ **Security best practices**
✅ **Advanced features**
✅ **Scaling strategies**
✅ **Compliance requirements**

### Support Resources

**Safaricom M-PESA Support**:
- 📧 Email: apisupport@safaricom.co.ke
- 📱 Phone: +254 722 000 000
- 🌐 Portal: https://developer.safaricom.co.ke

**Documentation**:
- Daraja Docs: https://developer.safaricom.co.ke/Documentation
- API Reference: https://developer.safaricom.co.ke/APIs

### Final Checklist

Before going live:
- [ ] ✅ Tested in sandbox thoroughly
- [ ] ✅ Production credentials obtained
- [ ] ✅ Server configured with HTTPS
- [ ] ✅ IP whitelisted in Daraja
- [ ] ✅ Webhooks tested and working
- [ ] ✅ Team trained
- [ ] ✅ Documentation ready
- [ ] ✅ Monitoring set up
- [ ] ✅ Backup plan in place
- [ ] ✅ Support team ready

---

**Version**: 2.0  
**Last Updated**: January 8, 2025  
**Maintained by**: School Management System Team

🚀 **You're ready for production!**







