# M-PESA Troubleshooting Guide

## ❌ Error: "Invalid Access Token" (404.001.03)

This is the most common M-PESA error. Here's how to fix it:

---

## 🔧 Quick Fix (Run These Commands)

### Step 1: Test Your Credentials

```bash
php artisan mpesa:test-credentials --clear-cache
```

This will:
- ✅ Check if all credentials are configured
- ✅ Test authentication with M-PESA API
- ✅ Show exactly what's wrong
- ✅ Clear any cached bad tokens

### Step 2: Clear All Caches

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### Step 3: Restart Queue Workers (if using queues)

```bash
php artisan queue:restart
```

---

## 🔍 Common Causes & Solutions

### 1. **Wrong Environment**

**Problem**: Using sandbox credentials with `MPESA_ENVIRONMENT=production`

**Solution**:
```env
# In your .env file
MPESA_ENVIRONMENT=production  # Must match your credential type!

# Production credentials
MPESA_CONSUMER_KEY=your_production_key
MPESA_CONSUMER_SECRET=your_production_secret
MPESA_SHORTCODE=your_production_paybill
MPESA_PASSKEY=your_production_passkey
```

### 2. **Incorrect Credentials**

**Problem**: Typo in Consumer Key or Secret

**Check**:
- No extra spaces before/after credentials
- No quotes around credentials
- Correct case (credentials are case-sensitive)

**Verify from Daraja Portal**:
1. Go to https://developer.safaricom.co.ke
2. Click on your app
3. Copy credentials exactly as shown

### 3. **Cached Old Token**

**Problem**: Old/expired token cached

**Solution**:
```bash
php artisan cache:clear
php artisan config:clear
```

Or in code:
```php
$mpesaGateway = app(\App\Services\PaymentGateways\MpesaGateway::class);
$mpesaGateway->clearAccessToken();
```

### 4. **IP Not Whitelisted (Production Only)**

**Problem**: Your server IP is not whitelisted with Safaricom

**Solution**:
1. Get your server's public IP:
   ```bash
   curl ifconfig.me
   ```
2. Contact Safaricom via mpesabusiness@safaricom.co.ke
3. Request IP whitelisting for production
4. Provide: Business name, paybill number, server IP

### 5. **Firewall Blocking Outbound Requests**

**Problem**: Server can't reach M-PESA API

**Test**:
```bash
curl -X GET "https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials" \
  -u "YOUR_CONSUMER_KEY:YOUR_CONSUMER_SECRET"
```

**If it fails**, check:
- Firewall rules
- Proxy settings
- SSL certificates

---

## 📋 Checklist: Verify Each Item

### .env File Configuration

```env
# ✅ Check these settings
MPESA_ENVIRONMENT=production          # or sandbox
MPESA_CONSUMER_KEY=ABC123...          # No spaces, no quotes
MPESA_CONSUMER_SECRET=XYZ789...       # No spaces, no quotes
MPESA_SHORTCODE=123456                # Your paybill number
MPESA_PASSKEY=your_passkey_here       # From Daraja Portal
```

### Config File (config/mpesa.php)

Make sure it reads from .env correctly:

```php
'consumer_key' => env('MPESA_CONSUMER_KEY'),
'consumer_secret' => env('MPESA_CONSUMER_SECRET'),
'shortcode' => env('MPESA_SHORTCODE'),
'passkey' => env('MPESA_PASSKEY'),
'environment' => env('MPESA_ENVIRONMENT', 'sandbox'),
```

---

## 🧪 Test in Stages

### Stage 1: Test Authentication Only

```bash
php artisan mpesa:test-credentials
```

Expected output:
```
✅ M-PESA credentials are VALID and working!
```

### Stage 2: Test STK Push (Small Amount)

```php
// In tinker: php artisan tinker
$gateway = app(\App\Services\PaymentGateways\MpesaGateway::class);
$results = $gateway->testCredentials();
print_r($results);
```

### Stage 3: Test Via UI

1. Go to Finance → M-PESA → Prompt Parent to Pay
2. Enter a test phone number (your number)
3. Amount: KES 1
4. Submit
5. Check your phone for STK Push

---

## 📝 Detailed Error Log Analysis

### Where to Find Logs

```bash
tail -f storage/logs/laravel.log
```

### What to Look For

**Good Authentication:**
```
[2026-01-11 10:30:45] local.INFO: M-PESA access token obtained successfully
```

**Failed Authentication:**
```
[2026-01-11 10:30:45] local.ERROR: Failed to get M-PESA access token
Response: {"error_description":"Invalid authentication credentials"}
```

---

## 🔄 Production vs Sandbox

### Sandbox (Testing)
- **Base URL**: `https://sandbox.safaricom.co.ke`
- **Credentials**: From test app in Daraja Portal
- **No IP whitelisting needed**
- **Test paybill**: 174379
- **Test passkey**: Provided by Safaricom

### Production (Live)
- **Base URL**: `https://api.safaricom.co.ke`
- **Credentials**: From production app in Daraja Portal
- **IP whitelisting REQUIRED**
- **Your paybill number**
- **Your production passkey**

### Switching from Sandbox to Production

1. Update .env:
   ```env
   MPESA_ENVIRONMENT=production
   MPESA_CONSUMER_KEY=prod_key_here
   MPESA_CONSUMER_SECRET=prod_secret_here
   MPESA_SHORTCODE=your_paybill
   MPESA_PASSKEY=your_passkey
   ```

2. Clear all caches:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

3. Test credentials:
   ```bash
   php artisan mpesa:test-credentials --clear-cache
   ```

4. Verify IP is whitelisted with Safaricom

---

## 🆘 Still Not Working?

### Check Laravel Logs

```bash
tail -100 storage/logs/laravel.log | grep -i mpesa
```

### Enable Debug Mode Temporarily

```env
# In .env - ONLY for testing, disable after!
APP_DEBUG=true
LOG_LEVEL=debug
```

### Check HTTP Response

Look in logs for the actual M-PESA response:

```
[2026-01-11 10:30:45] local.ERROR: Failed to get M-PESA access token
{
    "status": 401,
    "response": {
        "errorCode": "404.001.03",
        "errorMessage": "Invalid Access Token"
    }
}
```

### Contact Support

If still failing, gather this info:

1. **Environment**: Production or Sandbox?
2. **Consumer Key** (first 10 characters only): `ABC1234567...`
3. **Shortcode**: Your paybill number
4. **Server IP**: Run `curl ifconfig.me`
5. **Error message**: From `storage/logs/laravel.log`
6. **Test result**: Output of `php artisan mpesa:test-credentials`

**Email to**: mpesabusiness@safaricom.co.ke

---

## ✅ Prevention Tips

### 1. Use Environment Variables

Never hardcode credentials in code:

```php
// ❌ BAD
$consumerKey = 'ABC123XYZ789';

// ✅ GOOD
$consumerKey = config('mpesa.consumer_key');
```

### 2. Cache Tokens

Tokens are valid for 1 hour. Our system now caches them automatically for 55 minutes.

### 3. Add Retry Logic

Our updated system retries once if it gets an invalid token error.

### 4. Monitor Logs

Set up log monitoring to catch authentication failures:

```bash
# Add to cron
* * * * * grep -i "Invalid Access Token" /path/to/storage/logs/laravel.log | mail -s "M-PESA Auth Error" admin@school.com
```

### 5. Test After Deployment

Always run after deploying:

```bash
php artisan config:clear
php artisan cache:clear
php artisan mpesa:test-credentials
```

---

## 📞 Safaricom M-PESA Support

- **Email**: mpesabusiness@safaricom.co.ke
- **Phone**: +254 711 051 000
- **Portal**: https://developer.safaricom.co.ke
- **Documentation**: https://developer.safaricom.co.ke/apis-explorer

---

## 🎯 Quick Command Reference

```bash
# Test credentials
php artisan mpesa:test-credentials

# Clear caches
php artisan config:clear && php artisan cache:clear

# View logs
tail -f storage/logs/laravel.log | grep -i mpesa

# Test in tinker
php artisan tinker
>>> $g = app(\App\Services\PaymentGateways\MpesaGateway::class);
>>> $g->testCredentials();

# Restart workers
php artisan queue:restart

# Get server IP
curl ifconfig.me
```

---

**Updated**: January 11, 2026  
**Version**: 2.0 with token caching and retry logic

