# M-PESA Production Setup Script for Windows
# This script helps configure M-PESA for production use

Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "M-PESA Production Configuration Setup" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""

# Check if .env file exists
if (-not (Test-Path ".env")) {
    Write-Host "Error: .env file not found!" -ForegroundColor Red
    Write-Host "Please create a .env file first."
    exit 1
}

Write-Host "This script will help you configure M-PESA for production." -ForegroundColor Yellow
Write-Host ""
Write-Host "Before continuing, ensure you have:"
Write-Host "  1. Production Consumer Key from Daraja Portal"
Write-Host "  2. Production Consumer Secret from Daraja Portal"
Write-Host "  3. Business Shortcode (Paybill Number)"
Write-Host "  4. STK Push Passkey from Daraja Portal"
Write-Host "  5. Initiator credentials (optional)"
Write-Host ""

$response = Read-Host "Do you have all the required credentials? (y/n)"
if ($response -notmatch "^[Yy]$") {
    Write-Host "Please obtain your credentials from https://developer.safaricom.co.ke/ first." -ForegroundColor Yellow
    Write-Host "See MPESA_PRODUCTION_CONFIG.md for detailed instructions."
    exit 0
}

# Function to update or add environment variable
function Update-EnvVariable {
    param (
        [string]$Key,
        [string]$Value
    )
    
    $envContent = Get-Content .env -Raw
    $pattern = "^$Key=.*$"
    
    if ($envContent -match $pattern) {
        # Update existing key
        $envContent = $envContent -replace $pattern, "$Key=$Value"
    } else {
        # Add new key
        $envContent += "`n$Key=$Value"
    }
    
    Set-Content .env -Value $envContent -NoNewline
}

Write-Host ""
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "Step 1: Set Environment to Production" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan
Update-EnvVariable -Key "MPESA_ENVIRONMENT" -Value "production"
Write-Host "✓ Environment set to production" -ForegroundColor Green

Write-Host ""
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "Step 2: Enter Production Credentials" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan

$consumerKey = Read-Host "Enter Consumer Key"
Update-EnvVariable -Key "MPESA_CONSUMER_KEY" -Value $consumerKey

$consumerSecret = Read-Host "Enter Consumer Secret" -AsSecureString
$consumerSecretPlain = [Runtime.InteropServices.Marshal]::PtrToStringAuto(
    [Runtime.InteropServices.Marshal]::SecureStringToBSTR($consumerSecret)
)
Update-EnvVariable -Key "MPESA_CONSUMER_SECRET" -Value $consumerSecretPlain

$shortcode = Read-Host "Enter Business Shortcode (Paybill Number)"
Update-EnvVariable -Key "MPESA_SHORTCODE" -Value $shortcode

$passkey = Read-Host "Enter STK Push Passkey"
Update-EnvVariable -Key "MPESA_PASSKEY" -Value $passkey

Write-Host "✓ Credentials configured" -ForegroundColor Green

Write-Host ""
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "Step 3: Initiator Credentials (Optional)" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan

$response = Read-Host "Do you have initiator credentials? (y/n)"
if ($response -match "^[Yy]$") {
    $initiatorName = Read-Host "Enter Initiator Name"
    Update-EnvVariable -Key "MPESA_INITIATOR_NAME" -Value $initiatorName
    
    $initiatorPassword = Read-Host "Enter Initiator Password" -AsSecureString
    $initiatorPasswordPlain = [Runtime.InteropServices.Marshal]::PtrToStringAuto(
        [Runtime.InteropServices.Marshal]::SecureStringToBSTR($initiatorPassword)
    )
    Update-EnvVariable -Key "MPESA_INITIATOR_PASSWORD" -Value $initiatorPasswordPlain
    
    Write-Host "✓ Initiator credentials configured" -ForegroundColor Green
} else {
    Write-Host "⚠ Skipping initiator credentials (reversals/refunds will not work)" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "Step 4: Configure Callback URLs" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan

$appUrl = "https://erp.royalkingsschools.sc.ke"
Update-EnvVariable -Key "MPESA_CALLBACK_URL" -Value "$appUrl/webhooks/payment/mpesa"
Update-EnvVariable -Key "MPESA_TIMEOUT_URL" -Value "$appUrl/webhooks/payment/mpesa/timeout"
Update-EnvVariable -Key "MPESA_RESULT_URL" -Value "$appUrl/webhooks/payment/mpesa/result"
Update-EnvVariable -Key "MPESA_QUEUE_TIMEOUT_URL" -Value "$appUrl/webhooks/payment/mpesa/queue-timeout"
Update-EnvVariable -Key "MPESA_VALIDATION_URL" -Value "$appUrl/webhooks/payment/mpesa/validation"
Update-EnvVariable -Key "MPESA_CONFIRMATION_URL" -Value "$appUrl/webhooks/payment/mpesa/confirmation"

Write-Host "✓ Callback URLs configured for: $appUrl" -ForegroundColor Green

Write-Host ""
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "Step 5: Security Configuration" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan

# Generate webhook secret
$webhookSecret = -join ((48..57) + (65..90) + (97..122) | Get-Random -Count 32 | ForEach-Object {[char]$_})
Update-EnvVariable -Key "MPESA_WEBHOOK_SECRET" -Value $webhookSecret
Write-Host "✓ Generated webhook secret" -ForegroundColor Green

Update-EnvVariable -Key "MPESA_VERIFY_WEBHOOK_IP" -Value "true"
Write-Host "✓ Webhook IP verification enabled" -ForegroundColor Green

Write-Host ""
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "Step 6: Enable Features" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan

Update-EnvVariable -Key "MPESA_FEATURE_STK_PUSH" -Value "true"
Update-EnvVariable -Key "MPESA_FEATURE_C2B" -Value "true"
Update-EnvVariable -Key "MPESA_FEATURE_REVERSAL" -Value "true"
Update-EnvVariable -Key "MPESA_LOGGING_ENABLED" -Value "true"

Write-Host "✓ Features configured" -ForegroundColor Green

Write-Host ""
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "Step 7: Clear Configuration Cache" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan

php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

Write-Host "✓ Cache cleared" -ForegroundColor Green

Write-Host ""
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "Step 8: Rebuild Cache" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan

php artisan config:cache
php artisan route:cache
php artisan view:cache

Write-Host "✓ Cache rebuilt" -ForegroundColor Green

Write-Host ""
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "Configuration Complete!" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "✓ M-PESA is now configured for PRODUCTION" -ForegroundColor Green
Write-Host ""
Write-Host "IMPORTANT NEXT STEPS:" -ForegroundColor Yellow
Write-Host ""
Write-Host "1. Register callback URLs on Daraja Portal:"
Write-Host "   - Login to https://developer.safaricom.co.ke/"
Write-Host "   - Navigate to your production app"
Write-Host "   - Register callback URL: $appUrl/webhooks/payment/mpesa"
Write-Host ""
Write-Host "2. Whitelist your server IP with Safaricom"
Write-Host "   - Contact: apisupport@safaricom.co.ke"
Write-Host "   - Provide your server's public IP address"
Write-Host ""
Write-Host "3. Test with small amounts first (KES 1)"
Write-Host "   - Navigate to: $appUrl/finance/mpesa/prompt-payment"
Write-Host "   - Test payment flow end-to-end"
Write-Host ""
Write-Host "4. Monitor logs for any issues:"
Write-Host "   Get-Content storage\logs\laravel.log -Tail 50 -Wait | Select-String 'mpesa'"
Write-Host ""
Write-Host "5. Check database for transaction records:"
Write-Host "   SELECT * FROM payment_transactions WHERE gateway='mpesa' ORDER BY created_at DESC LIMIT 10;"
Write-Host ""
Write-Host "See MPESA_PRODUCTION_CONFIG.md for detailed documentation." -ForegroundColor Yellow
Write-Host ""

Write-Host "Press any key to exit..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")

