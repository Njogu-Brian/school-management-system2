# API Documentation

This document describes the API endpoints for the School Management System.

## Base URL

- **Development:** `http://localhost:8000`
- **Production:** `https://your-domain.com`

## Authentication

Most endpoints require authentication. Include the authentication token in the request header:

```
Authorization: Bearer {token}
```

## Payment Webhooks (Public Endpoints)

### M-Pesa Webhook

**Endpoint:** `POST /webhooks/payment/mpesa`

**Description:** Receives M-Pesa STK Push callbacks

**Authentication:** None (public endpoint, verify via IP whitelist in production)

**Request Body:**
```json
{
  "Body": {
    "stkCallback": {
      "CheckoutRequestID": "ws_CO_191220231020123456",
      "ResultCode": 0,
      "ResultDesc": "The service request is processed successfully.",
      "CallbackMetadata": {
        "Item": [
          {
            "Name": "Amount",
            "Value": 100.00
          },
          {
            "Name": "MpesaReceiptNumber",
            "Value": "QGH1234567"
          },
          {
            "Name": "TransactionDate",
            "Value": 20231219102030
          },
          {
            "Name": "PhoneNumber",
            "Value": 254712345678
          }
        ]
      }
    }
  }
}
```

**Response:**
```json
{
  "success": true
}
```

### Stripe Webhook

**Endpoint:** `POST /webhooks/payment/stripe`

**Status:** Not yet implemented

### PayPal Webhook

**Endpoint:** `POST /webhooks/payment/paypal`

**Status:** Not yet implemented

## Document Generation API

### Generate Document for Student

**Endpoint:** `POST /document-templates/{template}/generate/student/{student}`

**Authentication:** Required (Admin/Secretary)

**Request Body:**
```json
{
  "data": {
    "custom_field": "custom_value"
  }
}
```

**Response:** Redirects to generated document view

### Preview Document Template

**Endpoint:** `POST /document-templates/{template}/preview`

**Authentication:** Required (Admin/Secretary)

**Query Parameters:**
- `student_id` (optional)
- `staff_id` (optional)
- `data` (optional JSON)

**Response:** PDF download

## Library Management API

### Borrow Book

**Endpoint:** `POST /library/borrowings`

**Authentication:** Required (Admin/Secretary/Teacher)

**Request Body:**
```json
{
  "library_card_id": 1,
  "book_copy_id": 5,
  "days": 14
}
```

**Response:** Redirects to borrowing details

### Return Book

**Endpoint:** `POST /library/borrowings/{borrowing}/return`

**Authentication:** Required (Admin/Secretary/Teacher)

**Request Body:**
```json
{
  "condition": "good"
}
```

**Response:** Redirects to borrowings list

## Hostel Management API

### Allocate Student to Hostel

**Endpoint:** `POST /hostel/allocations`

**Authentication:** Required (Admin/Secretary)

**Request Body:**
```json
{
  "student_id": 1,
  "room_id": 5,
  "bed_number": "Bed 1"
}
```

**Response:** Redirects to allocation details

### Deallocate Student

**Endpoint:** `POST /hostel/allocations/{allocation}/deallocate`

**Authentication:** Required (Admin/Secretary)

**Response:** Redirects to allocations list

## Payment API

### Initiate Online Payment

**Endpoint:** `POST /finance/payments/initiate-online`

**Authentication:** Required (Admin/Secretary)

**Request Body:**
```json
{
  "student_id": 1,
  "invoice_id": 10,
  "gateway": "mpesa",
  "phone_number": "0712345678"
}
```

**Response:** Redirects to payment transaction view

### Verify Payment Status

**Endpoint:** `POST /finance/payment-transactions/{transaction}/verify`

**Authentication:** Required (Admin/Secretary)

**Response:** Redirects back with status update

## Error Responses

All endpoints may return error responses in the following format:

```json
{
  "error": "Error message",
  "code": "ERROR_CODE"
}
```

**Common HTTP Status Codes:**
- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `500` - Server Error

## Rate Limiting

Webhook endpoints have rate limiting to prevent abuse. Contact administrator if you need higher limits.

## Webhook Security

- M-Pesa: Verify requests come from Safaricom IP ranges
- Stripe: Verify webhook signatures using `PAYMENT_STRIPE_WEBHOOK_SECRET`
- PayPal: Verify webhook signatures using PayPal's verification API

## Testing

For testing webhooks locally, use tools like:
- ngrok for exposing local server
- Postman for manual webhook testing
- Stripe CLI for Stripe webhook testing

## Support

For API support, contact the development team or create an issue in the repository.

