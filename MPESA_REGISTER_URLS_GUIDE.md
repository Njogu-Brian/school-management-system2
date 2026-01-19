# How to Register M-PESA C2B URLs

This guide shows you how to register your C2B (Customer to Business) callback URLs with M-PESA.

## 📋 Prerequisites

1. **M-PESA Daraja Account**: You must have a Daraja account at https://developer.safaricom.co.ke
2. **App Credentials**: Consumer Key, Consumer Secret, and Shortcode configured in your `.env` file
3. **Public URLs**: Your webhook URLs must be publicly accessible via HTTPS (production) or HTTP (sandbox)

## 🔗 Your Callback URLs

Based on your configuration, your URLs should be:

- **Confirmation URL**: `https://erp.royalkingsschools.sc.ke/webhooks/payment/mpesa/c2b`
- **Validation URL**: `https://erp.royalkingsschools.sc.ke/webhooks/payment/mpesa/c2b` (same as confirmation, validation is optional)

## 🚀 Method 1: Using the Built-in API Endpoint (Recommended)

### Step 1: Access the Registration Endpoint

You can register URLs directly from your application:

**Via Browser/Postman:**
```
POST https://erp.royalkingsschools.sc.ke/finance/mpesa/c2b/register-urls
```

**With Custom URLs (optional):**
```json
{
  "confirmation_url": "https://erp.royalkingsschools.sc.ke/webhooks/payment/mpesa/c2b",
  "validation_url": "https://erp.royalkingsschools.sc.ke/webhooks/payment/mpesa/c2b",
  "response_type": "Completed"
}
```

**Response:**
```json
{
  "success": true,
  "message": "C2B URLs registered successfully",
  "data": {
    "confirmation_url": "https://erp.royalkingsschools.sc.ke/webhooks/payment/mpesa/c2b",
    "validation_url": "https://erp.royalkingsschools.sc.ke/webhooks/payment/mpesa/c2b",
    "response_type": "Completed",
    "originator_conversation_id": "6e86-45dd-91ac-fd5d4178ab523408729"
  }
}
```

### Step 2: Verify Registration

After registration, check the Safaricom Daraja Portal:
1. Go to https://developer.safaricom.co.ke/dashboard/urlmanagement
2. Your registered URLs should appear in the list

## 🛠️ Method 2: Using Safaricom Daraja Portal

### Step 1: Log in to Daraja Portal
1. Visit https://developer.safaricom.co.ke
2. Log in with your credentials
3. Navigate to your app

### Step 2: Use the Simulator
1. Go to **APIs** → **Customer To Business (C2B)**
2. Click **"Use API"** button
3. In the simulator, use the **"Register C2B Confirmation and Validation URLs"** request
4. Fill in:
   - **ShortCode**: Your paybill number (from `.env` file)
   - **ResponseType**: `Completed` (or `Cancelled` if you want to reject by default)
   - **ConfirmationURL**: `https://erp.royalkingsschools.sc.ke/webhooks/payment/mpesa/c2b`
   - **ValidationURL**: `https://erp.royalkingsschools.sc.ke/webhooks/payment/mpesa/c2b`
5. Click **Send Request**

### Step 3: Verify Response
You should see:
```json
{
  "OriginatorCoversationID": "6e86-45dd-91ac-fd5d4178ab523408729",
  "ResponseCode": "0",
  "ResponseDescription": "Success"
}
```

## 🔧 Method 3: Using cURL Command

### For Sandbox:
```bash
curl -X POST 'https://sandbox.safaricom.co.ke/mpesa/c2b/v2/registerurl' \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer YOUR_ACCESS_TOKEN' \
  -d '{
    "ShortCode": "YOUR_SHORTCODE",
    "ResponseType": "Completed",
    "ConfirmationURL": "https://erp.royalkingsschools.sc.ke/webhooks/payment/mpesa/c2b",
    "ValidationURL": "https://erp.royalkingsschools.sc.ke/webhooks/payment/mpesa/c2b"
  }'
```

### For Production:
```bash
curl -X POST 'https://api.safaricom.co.ke/mpesa/c2b/v2/registerurl' \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer YOUR_ACCESS_TOKEN' \
  -d '{
    "ShortCode": "YOUR_SHORTCODE",
    "ResponseType": "Completed",
    "ConfirmationURL": "https://erp.royalkingsschools.sc.ke/webhooks/payment/mpesa/c2b",
    "ValidationURL": "https://erp.royalkingsschools.sc.ke/webhooks/payment/mpesa/c2b"
  }'
```

**To get access token:**
```bash
curl -X GET 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials' \
  -H 'Authorization: Basic BASE64(CONSUMER_KEY:CONSUMER_SECRET)'
```

## ⚙️ Configuration in .env File

Make sure these are set in your `.env` file:

```env
# M-PESA Credentials
MPESA_CONSUMER_KEY=your_consumer_key
MPESA_CONSUMER_SECRET=your_consumer_secret
MPESA_SHORTCODE=your_paybill_number
MPESA_PASSKEY=your_passkey

# Environment
MPESA_ENVIRONMENT=production  # or 'sandbox' for testing

# C2B URLs (optional - defaults will be used if not set)
MPESA_CONFIRMATION_URL=https://erp.royalkingsschools.sc.ke/webhooks/payment/mpesa/c2b
MPESA_VALIDATION_URL=https://erp.royalkingsschools.sc.ke/webhooks/payment/mpesa/c2b
MPESA_C2B_RESPONSE_TYPE=Completed
```

## 📝 Important Notes

1. **Sandbox vs Production**:
   - **Sandbox**: You can register URLs multiple times, use HTTP or HTTPS
   - **Production**: URLs can only be registered once. To change, delete first via URL Management portal

2. **ResponseType**:
   - `Completed`: M-PESA will complete the transaction if validation URL is unreachable
   - `Cancelled`: M-PESA will cancel the transaction if validation URL is unreachable

3. **Validation URL**:
   - Optional - only used if External Validation is enabled on your paybill
   - To enable validation, email: `apisupport@safaricom.co.ke` or `M-pesabusiness@safaricom.co.ke`
   - Takes ~6 hours to activate

4. **URL Requirements**:
   - Must be publicly accessible
   - Production must use HTTPS
   - Cannot contain keywords: M-PESA, Safaricom, exe, exec, cmd, SQL, query
   - Cannot use public URL testers (ngrok, mockbin, requestbin) in production

5. **Deleting URLs**:
   - Go to: https://developer.safaricom.co.ke/SelfServices?tab=urlmanagement
   - Requires Business Manager or Business Administrator role on M-PESA Org Portal

## ✅ Verification

After registration:

1. **Check Portal**: Visit https://developer.safaricom.co.ke/dashboard/urlmanagement
2. **Test Payment**: Make a small test payment to your paybill
3. **Check Logs**: Monitor `storage/logs/laravel.log` for webhook calls
4. **Check Dashboard**: Go to Finance → M-PESA → C2B Dashboard to see transactions

## 🐛 Troubleshooting

### Error: "Urls are already registered"
- **Solution**: Delete existing URLs first via URL Management portal, then re-register

### Error: "Invalid Access Token"
- **Solution**: Generate a new access token (they expire after 1 hour)

### Error: "500.003.1001 Internal Server Error"
- **Solution**: Check your server is running and URLs are accessible

### Not Receiving Callbacks
- **Check**: URLs are publicly accessible
- **Check**: Server firewall allows M-PESA IPs
- **Check**: URLs use HTTPS in production
- **Check**: CSRF exemptions are configured (already done in your setup)

## 📞 Support

- **Daraja Chatbot**: Available on developer portal
- **Email**: apisupport@safaricom.co.ke
- **Incident Management**: https://developer.safaricom.co.ke/dashboard/incidentmanagement
