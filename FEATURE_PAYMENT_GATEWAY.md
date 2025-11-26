# Online Payment Gateway Integration

## Overview

This feature adds online payment gateway integration to allow parents to pay fees online via M-Pesa, Stripe, or PayPal. Includes webhook handling, transaction reconciliation, and payment history.

## Architecture

### Components

1. **PaymentGateway Service** - Abstract payment gateway interface
2. **MpesaGateway** - M-Pesa integration
3. **StripeGateway** - Stripe integration
4. **PaypalGateway** - PayPal integration
5. **PaymentTransaction Model** - Stores payment transactions
6. **PaymentWebhookController** - Handles webhook callbacks
7. **PaymentController** - Enhanced with online payment support

### Database Schema

```sql
payment_transactions:
  - id
  - student_id
  - invoice_id
  - gateway (mpesa, stripe, paypal)
  - transaction_id (gateway transaction ID)
  - reference (internal reference)
  - amount
  - currency
  - status (pending, processing, completed, failed, cancelled, refunded)
  - gateway_response (JSON)
  - webhook_data (JSON)
  - paid_at
  - created_at

payment_webhooks:
  - id
  - gateway
  - event_type
  - payload (JSON)
  - processed
  - processed_at
  - created_at
```

## Implementation Plan

### Phase 1: Core Infrastructure
- [ ] Create payment_transactions migration
- [ ] Create payment_webhooks migration
- [ ] Create PaymentTransaction model
- [ ] Create PaymentWebhook model
- [ ] Create PaymentGateway interface

### Phase 2: Gateway Implementations
- [ ] Implement MpesaGateway
- [ ] Implement StripeGateway
- [ ] Implement PaypalGateway
- [ ] Gateway factory pattern

### Phase 3: Webhook Handling
- [ ] Webhook signature verification
- [ ] Idempotency handling
- [ ] Transaction reconciliation
- [ ] Webhook logging

### Phase 4: UI & Integration
- [ ] Payment initiation endpoint
- [ ] Payment status page
- [ ] Payment history for parents
- [ ] Admin payment management

### Phase 5: Testing
- [ ] Unit tests for gateways
- [ ] Integration tests for webhooks
- [ ] E2E tests for payment flow

## Gateway Features

### M-Pesa
- STK Push initiation
- Payment confirmation
- Webhook callbacks
- Transaction status queries

### Stripe
- Payment Intent creation
- Checkout session
- Webhook event handling
- Refund support

### PayPal
- Order creation
- Payment approval
- Webhook notifications
- Refund support

## Security

- Webhook signature verification
- Idempotency keys
- Transaction amount validation
- Rate limiting on webhooks
- Secure storage of gateway credentials

## Acceptance Criteria

1. ✅ Parents can initiate online payments
2. ✅ Webhooks are processed idempotently
3. ✅ Transactions are reconciled automatically
4. ✅ Payment status updates in real-time
5. ✅ Failed payments are logged and retryable
6. ✅ All tests pass

