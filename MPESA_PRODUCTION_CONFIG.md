# M-PESA Production Configuration Guide

## Overview
This guide explains how to configure M-PESA for production use in the Royal Kings School ERP system.

## Prerequisites
1. **Safaricom Daraja Account**: You must have an active Daraja account with production credentials
2. **Go-Live Approval**: Ensure your application has been approved for production by Safaricom
3. **Production Credentials**: Have your production credentials ready:
   - Consumer Key
   - Consumer Secret
   - Business Shortcode (Paybill number)
   - Passkey (Lipa na M-PESA Online Passkey)
   - Initiator Name (for advanced features)
   - Initiator Password (for advanced features)

## Step 1: Configure Environment Variables

Add the following to your `.env` file:

```env
# ============================================
# M-PESA PRODUCTION CONFIGURATION
# ============================================

# Environment: Set to 'production' for live transactions
MPESA_ENVIRONMENT=production

# API Credentials (Get from Daraja Production Portal)
MPESA_CONSUMER_KEY=your_production_consumer_key_here
MPESA_CONSUMER_SECRET=your_production_consumer_secret_here

# Business Shortcode (Your Paybill Number)
MPESA_SHORTCODE=your_paybill_number

# STK Push Passkey (Get from Daraja Portal)
MPESA_PASSKEY=your_production_passkey_here

# Initiator Credentials (Required for refunds, reversals, and balance checks)
MPESA_INITIATOR_NAME=your_initiator_name
MPESA_INITIATOR_PASSWORD=your_initiator_password

# Callback URLs (Must be publicly accessible HTTPS URLs)
MPESA_CALLBACK_URL=https://erp.royalkingsschools.sc.ke/webhooks/payment/mpesa
MPESA_TIMEOUT_URL=https://erp.royalkingsschools.sc.ke/webhooks/payment/mpesa/timeout
MPESA_RESULT_URL=https://erp.royalkingsschools.sc.ke/webhooks/payment/mpesa/result
MPESA_QUEUE_TIMEOUT_URL=https://erp.royalkingsschools.sc.ke/webhooks/payment/mpesa/queue-timeout
MPESA_VALIDATION_URL=https://erp.royalkingsschools.sc.ke/webhooks/payment/mpesa/validation
MPESA_CONFIRMATION_URL=https://erp.royalkingsschools.sc.ke/webhooks/payment/mpesa/confirmation

# Optional: Webhook Security
MPESA_WEBHOOK_SECRET=generate_a_random_secret_key_here
MPESA_VERIFY_WEBHOOK_IP=true

# Optional: C2B Configuration
MPESA_C2B_RESPONSE_TYPE=Completed
MPESA_C2B_CONFIRMATION_REQUIRED=true

# Optional: Timeout Settings (in seconds)
MPESA_STK_PUSH_TIMEOUT=120
MPESA_TRANSACTION_QUERY_TIMEOUT=30
MPESA_DEFAULT_TIMEOUT=60

# Optional: Feature Toggles
MPESA_FEATURE_STK_PUSH=true
MPESA_FEATURE_C2B=true
MPESA_FEATURE_B2C=false
MPESA_FEATURE_B2B=false
MPESA_FEATURE_REVERSAL=true
MPESA_FEATURE_ACCOUNT_BALANCE=false

# Optional: Logging
MPESA_LOGGING_ENABLED=true
MPESA_LOG_CHANNEL=daily
MPESA_LOG_LEVEL=info
```

## Step 2: Obtain Production Credentials

### 2.1 Access Daraja Production Portal
1. Visit https://developer.safaricom.co.ke/
2. Login with your production account credentials
3. Navigate to your production app

### 2.2 Get Consumer Key and Secret
1. Go to "My Apps" → Select your production app
2. Copy the **Consumer Key** and **Consumer Secret**
3. Add them to your `.env` file

### 2.3 Get STK Push Passkey
1. In your app details, navigate to "Lipa na M-PESA Online"
2. Copy the **Passkey**
3. Add it to your `.env` file as `MPESA_PASSKEY`

### 2.4 Get Business Shortcode
- Your shortcode is your **Paybill number**
- Add it to your `.env` file as `MPESA_SHORTCODE`

### 2.5 Setup Initiator Credentials (Optional but Recommended)
These are required for:
- Transaction reversals (refunds)
- Transaction status queries
- Account balance checks

Contact Safaricom support to get these credentials.

## Step 3: Configure Callback URLs on Daraja Portal

### 3.1 Register URLs in Daraja Portal
For STK Push (Lipa na M-PESA Online):
- **Callback URL**: `https://erp.royalkingsschools.sc.ke/webhooks/payment/mpesa`

### 3.2 Whitelist Your IPs
- Safaricom requires you to whitelist your server's public IP address
- Contact Safaricom support to add your production server IP to their whitelist

## Step 4: Test in Sandbox First

⚠️ **IMPORTANT**: Always test in sandbox before switching to production!

### 4.1 Sandbox Testing
```env
MPESA_ENVIRONMENT=sandbox
# Use sandbox credentials...
```

### 4.2 Test These Scenarios
1. ✅ Successful payment
2. ✅ Failed payment (user cancels)
3. ✅ Timeout scenarios
4. ✅ Webhook callbacks
5. ✅ Transaction status queries

## Step 5: Switch to Production

### 5.1 Update Environment
In your `.env` file:
```env
MPESA_ENVIRONMENT=production
```

### 5.2 Clear Configuration Cache
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### 5.3 Verify Configuration
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Step 6: Register C2B URLs (Optional)

If you want to accept direct payments to your Paybill (not just STK Push), you need to register C2B URLs.

### Option 1: Using Artisan Command (Create if needed)
```bash
php artisan mpesa:register-urls
```

### Option 2: Manual Registration via API
Access your application and use the M-PESA dashboard to register URLs.

## Step 7: Monitor Production Transactions

### 7.1 Check Logs
Monitor M-PESA transaction logs:
```bash
tail -f storage/logs/laravel.log
```

### 7.2 Database Monitoring
Check transaction records:
```sql
SELECT * FROM payment_transactions 
WHERE gateway = 'mpesa' 
ORDER BY created_at DESC 
LIMIT 20;
```

## Security Best Practices

### 1. **Webhook Security**
- Enable webhook IP verification in production
- Use a strong webhook secret
- Only allow requests from Safaricom IPs (configured in `config/mpesa.php`)

### 2. **Credential Security**
- Never commit `.env` file to version control
- Use environment variables for all sensitive data
- Rotate credentials periodically

### 3. **HTTPS Required**
- All callback URLs must use HTTPS
- Ensure SSL certificate is valid
- Safaricom will reject HTTP callbacks

### 4. **IP Whitelisting**
Safaricom production IPs (already configured):
- 196.201.214.200
- 196.201.214.206
- 196.201.213.114
- 196.201.214.207
- 196.201.214.208
- 196.201.213.44
- 196.201.212.127
- 196.201.212.138
- 196.201.212.129
- 196.201.212.136
- 196.201.212.74
- 196.201.212.69

## Troubleshooting

### Issue: "Invalid Access Token"
**Solution**: Check that your Consumer Key and Secret are correct for production.

### Issue: "Invalid Shortcode"
**Solution**: Verify your Business Shortcode (Paybill number) is correct.

### Issue: "Bad Request - Invalid PhoneNumber"
**Solution**: Ensure phone numbers are in format 254XXXXXXXXX (not 07XXXXXXXX).

### Issue: "Callback Never Received"
**Solutions**:
1. Verify your callback URL is publicly accessible via HTTPS
2. Check SSL certificate is valid
3. Verify your server IP is whitelisted by Safaricom
4. Check firewall rules allow incoming connections from Safaricom IPs

### Issue: "Transaction Timeout"
**Solutions**:
1. Increase timeout values in `.env`
2. Customer may not have completed payment on their phone
3. Network issues on customer's side

## Testing Production

### Test with Small Amounts First
Start with KES 1 to verify everything works before processing real payments.

### Test Users
Use real Safaricom phone numbers for production testing (not test numbers from sandbox).

### Verify End-to-End Flow
1. ✅ Payment initiation
2. ✅ STK Push received on phone
3. ✅ Customer enters PIN
4. ✅ Payment confirmation received
5. ✅ Webhook callback processed
6. ✅ Transaction recorded in database
7. ✅ Receipt/SMS sent to customer

## Quick Reference: Environment Values

| Variable | Description | Example |
|----------|-------------|---------|
| `MPESA_ENVIRONMENT` | Environment mode | `production` |
| `MPESA_CONSUMER_KEY` | API Consumer Key | `xxxxx` |
| `MPESA_CONSUMER_SECRET` | API Consumer Secret | `xxxxx` |
| `MPESA_SHORTCODE` | Business Paybill Number | `174379` |
| `MPESA_PASSKEY` | STK Push Passkey | `bfb279f9aa9bdbcf158e...` |
| `MPESA_INITIATOR_NAME` | Initiator Username | `testapi` |
| `MPESA_INITIATOR_PASSWORD` | Initiator Password | `xxxxx` |

## Support

### Safaricom Support
- **Email**: apisupport@safaricom.co.ke
- **Phone**: +254 20 421 0000
- **Portal**: https://developer.safaricom.co.ke/

### Internal Support
Check logs and transaction tables for debugging:
```bash
# Application logs
tail -f storage/logs/laravel.log | grep -i mpesa

# Database queries
mysql> SELECT * FROM payment_transactions WHERE gateway='mpesa' ORDER BY created_at DESC;
```

## Next Steps

After configuring production:
1. ✅ Test with small amounts
2. ✅ Monitor first few transactions closely
3. ✅ Set up alerts for failed transactions
4. ✅ Train staff on monitoring dashboard
5. ✅ Document any custom configurations
6. ✅ Schedule regular credential rotation

---

**Last Updated**: January 2026
**System Version**: Laravel 12.38.1 | PHP 8.2.29
**M-PESA API Version**: V1

