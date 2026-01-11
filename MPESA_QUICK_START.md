# M-PESA Production - Quick Start Guide

## ✅ Fixed Issues
1. **Duplicate method declarations** - Fixed in `MpesaGateway.php`
2. **Method renamed** - `queryTransactionStatus()` → `queryStkPushStatus()` for STK Push queries
3. **Duplicate `registerC2BUrls()`** - Removed duplicate method

## 🚀 Quick Setup (3 Steps)

### Step 1: Get Production Credentials
Visit https://developer.safaricom.co.ke/ and get:
- Consumer Key
- Consumer Secret
- Business Shortcode (your Paybill number)
- STK Push Passkey
- Initiator credentials (optional, for refunds)

### Step 2: Run Setup Script
```powershell
# Navigate to project root
cd D:\school-management-system2\school-management-system2

# Run the setup script
.\scripts\setup-mpesa-production.ps1
```

The script will:
- ✅ Set environment to production
- ✅ Configure credentials
- ✅ Setup callback URLs
- ✅ Enable security features
- ✅ Clear and rebuild cache

### Step 3: Verify Configuration
```powershell
# Run verification script
.\scripts\verify-mpesa-config.ps1
```

## 📋 Manual Configuration (Alternative)

If you prefer to configure manually, add these to your `.env` file:

```env
MPESA_ENVIRONMENT=production
MPESA_CONSUMER_KEY=your_key_here
MPESA_CONSUMER_SECRET=your_secret_here
MPESA_SHORTCODE=174379
MPESA_PASSKEY=your_passkey_here
MPESA_INITIATOR_NAME=your_initiator
MPESA_INITIATOR_PASSWORD=your_password

# Callback URLs (pre-configured for your domain)
MPESA_CALLBACK_URL=https://erp.royalkingsschools.sc.ke/webhooks/payment/mpesa
MPESA_TIMEOUT_URL=https://erp.royalkingsschools.sc.ke/webhooks/payment/mpesa/timeout
MPESA_RESULT_URL=https://erp.royalkingsschools.sc.ke/webhooks/payment/mpesa/result
MPESA_QUEUE_TIMEOUT_URL=https://erp.royalkingsschools.sc.ke/webhooks/payment/mpesa/queue-timeout

# Security
MPESA_VERIFY_WEBHOOK_IP=true

# Features
MPESA_FEATURE_STK_PUSH=true
MPESA_FEATURE_C2B=true
MPESA_FEATURE_REVERSAL=true
MPESA_LOGGING_ENABLED=true
```

Then clear cache:
```powershell
php artisan config:clear
php artisan cache:clear
php artisan config:cache
```

## ⚠️ Important Post-Setup Tasks

### 1. Register Callback URL on Daraja Portal
- Login to https://developer.safaricom.co.ke/
- Select your production app
- Navigate to "Lipa na M-PESA Online"
- Set Callback URL: `https://erp.royalkingsschools.sc.ke/webhooks/payment/mpesa`

### 2. Whitelist Server IP
Contact Safaricom (apisupport@safaricom.co.ke) with:
- Your server's public IP address
- Request IP whitelisting for production

### 3. Test Payment Flow
1. Navigate to: https://erp.royalkingsschools.sc.ke/finance/mpesa/prompt-payment
2. Initiate a KES 1 test payment
3. Complete payment on your phone
4. Verify transaction in dashboard

## 🔍 Testing Checklist

- [ ] STK Push initiated successfully
- [ ] Payment prompt received on phone
- [ ] Payment completed successfully
- [ ] Webhook callback received
- [ ] Transaction recorded in database
- [ ] Transaction status queryable
- [ ] Receipt/confirmation sent

## 📊 Monitoring

### Check Logs
```powershell
# Real-time log monitoring
Get-Content storage\logs\laravel.log -Tail 50 -Wait | Select-String 'mpesa'

# Check for errors
Get-Content storage\logs\laravel.log -Tail 100 | Select-String 'error|failed' -CaseSensitive
```

### Check Database
```sql
-- Recent M-PESA transactions
SELECT id, reference, amount, status, created_at 
FROM payment_transactions 
WHERE gateway = 'mpesa' 
ORDER BY created_at DESC 
LIMIT 20;

-- Failed transactions
SELECT * FROM payment_transactions 
WHERE gateway = 'mpesa' AND status = 'failed' 
ORDER BY created_at DESC;

-- Success rate
SELECT 
    status,
    COUNT(*) as count,
    ROUND(COUNT(*) * 100.0 / SUM(COUNT(*)) OVER(), 2) as percentage
FROM payment_transactions 
WHERE gateway = 'mpesa'
GROUP BY status;
```

## 🚨 Troubleshooting

### Issue: "Cannot redeclare method" Error
**Status**: ✅ FIXED
The duplicate methods have been resolved in `MpesaGateway.php`

### Issue: "Invalid Access Token"
**Solution**: Check Consumer Key and Secret are for production (not sandbox)

### Issue: "Invalid Shortcode"
**Solution**: Verify Business Shortcode matches your Paybill number

### Issue: "Callback Not Received"
**Solutions**:
1. Verify callback URL is HTTPS
2. Check SSL certificate is valid
3. Confirm server IP is whitelisted
4. Test webhook endpoint directly

### Issue: "Bad Request - Invalid PhoneNumber"
**Solution**: Ensure phone format is 254XXXXXXXXX (not 07XXXXXXXX)

## 📚 Documentation Files

- **MPESA_PRODUCTION_CONFIG.md** - Detailed configuration guide
- **MPESA_MASTER_GUIDE.md** - Complete M-PESA integration documentation
- **scripts/setup-mpesa-production.ps1** - Automated setup script
- **scripts/verify-mpesa-config.ps1** - Configuration verification script

## 🔗 Useful Links

- **Daraja Portal**: https://developer.safaricom.co.ke/
- **API Documentation**: https://developer.safaricom.co.ke/apis-explorer
- **Support**: apisupport@safaricom.co.ke
- **Phone**: +254 20 421 0000

## 📱 M-PESA Routes

| Route | Purpose |
|-------|---------|
| `/finance/mpesa/dashboard` | M-PESA transaction dashboard |
| `/finance/mpesa/prompt-payment` | Admin-initiated STK Push |
| `/finance/mpesa/links/create` | Create payment link |
| `/finance/mpesa/links` | View payment links |
| `/webhooks/payment/mpesa` | Payment callback handler |

## 🎯 Success Criteria

Your M-PESA integration is ready when:
- ✅ Configuration passes verification script
- ✅ Test payment (KES 1) completes successfully
- ✅ Webhook callbacks are received and processed
- ✅ Transactions appear in database
- ✅ Dashboard displays transaction status
- ✅ No errors in application logs

## 📞 Support

**For Configuration Issues**:
- Check logs: `storage/logs/laravel.log`
- Run verification: `.\scripts\verify-mpesa-config.ps1`
- Review: `MPESA_PRODUCTION_CONFIG.md`

**For M-PESA API Issues**:
- Contact: apisupport@safaricom.co.ke
- Phone: +254 20 421 0000
- Portal: https://developer.safaricom.co.ke/support

---

**Version**: 2.0 (January 2026)
**Status**: Production Ready ✅
**Last Updated**: 2026-01-11

