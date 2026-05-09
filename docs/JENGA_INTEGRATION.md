# Jenga (Equity / Finserve) Integration

This project now includes a backend Jenga integration service and secured API routes.
Excluded per current scope: KYC, forex, and top-up.

## Configuration

Set the following variables in `.env`:

- `JENGA_ENVIRONMENT` (`sandbox` or `production`)
- `JENGA_API_KEY`
- `JENGA_MERCHANT_CODE`
- `JENGA_CONSUMER_SECRET`
- `JENGA_PRIVATE_KEY_PATH` (absolute path to your private key PEM file)

Optional:

- `JENGA_SANDBOX_BASE_URL` (default `https://uat.finserve.africa`)
- `JENGA_PRODUCTION_BASE_URL` (default `https://api.finserve.africa`)
- `JENGA_TOKEN_CACHE_TTL_SECONDS`
- `JENGA_TIMEOUT`

## Implemented API Endpoints

All endpoints are under authenticated API routes (`auth:sanctum`) and restricted to finance/admin roles.

- `POST /api/jenga/token` - fetch (or refresh) access token

### Account services

- `GET /api/jenga/accounts/{countryCode}/{accountNumber}/inquiry` - account inquiry
- `GET /api/jenga/accounts/{countryCode}/{accountId}/balance` - account balance
- `GET /api/jenga/accounts/{countryCode}/{accountNumber}/mini-statement` - mini statement
- `POST /api/jenga/accounts/full-statement` - full statement

### Collections / receive money

- `POST /api/jenga/collect/stk-ussd-push` - initiate M-Pesa STK/USSD push (account-based settlement)

### Disbursements / send money

- `POST /api/jenga/disburse/mobile-wallet` - mobile wallet remittance
- `POST /api/jenga/disburse/within-equity` - internal Equity transfer
- `POST /api/jenga/disburse/rtgs` - RTGS transfer
- `GET /api/jenga/disburse/rtgs/payment-purposes` - RTGS purpose codes

### Transaction and catalog queries

- `GET /api/jenga/queries/transactions/{reference}` - query transaction details
- `GET /api/jenga/queries/billers` - list billers
- `GET /api/jenga/queries/merchants` - list EazzyPay merchants

### Generic fallback

- `POST /api/jenga/signed-proxy` - generic signed call to support additional Jenga features

## Generic Signed Proxy

Use this endpoint for Jenga APIs not yet wrapped by dedicated methods.

Request payload:

```json
{
  "method": "POST",
  "endpoint_path": "/v3-apis/transaction-api/v3.0/some/endpoint",
  "signature_string": "rawconcatenatedfields",
  "payload": {}
}
```

The backend signs `signature_string` with your configured private key and sends the request with:

- `Authorization: Bearer <token>`
- `Signature: <base64-signature>`

## Getting Jenga Credentials (Portal Checklist)

Since your account is already created, use this checklist to fetch all `.env` values:

1. Log in to [JengaHQ Portal](https://v3.jengahq.io/).
2. Open your app/project in sandbox first.
3. Copy API credentials:
   - `JENGA_API_KEY` -> API key shown for your app.
   - `JENGA_MERCHANT_CODE` -> merchant code shown for your organization/app.
   - `JENGA_CONSUMER_SECRET` -> app consumer secret.
4. Generate an RSA key pair locally (if not already done), share the public key with Jenga/Finserve, and keep private key on the server:
   - set `JENGA_PRIVATE_KEY_PATH` to the absolute private key path (PEM).
5. Set environment target:
   - `JENGA_ENVIRONMENT=sandbox` for UAT.
   - switch to `production` after go-live approval.
6. Keep base URLs default unless support instructs otherwise.

## Local Key Generation Example

```bash
openssl genrsa -out storage/keys/jenga_private.pem 2048
openssl rsa -in storage/keys/jenga_private.pem -pubout -out storage/keys/jenga_public.pem
```

Share `jenga_public.pem` with Finserve/Jenga support and retain `jenga_private.pem` securely on the backend host.

