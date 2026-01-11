# M-PESA Configuration Verification Script
# This script checks if M-PESA is properly configured

Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "M-PESA Configuration Verification" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""

$issues = @()
$warnings = @()

# Check .env file exists
if (-not (Test-Path ".env")) {
    Write-Host "✗ .env file not found!" -ForegroundColor Red
    exit 1
}

# Load .env file
$envVars = @{}
Get-Content .env | ForEach-Object {
    if ($_ -match '^([^=]+)=(.*)$') {
        $envVars[$matches[1]] = $matches[2]
    }
}

# Check required environment variables
$requiredVars = @{
    "MPESA_ENVIRONMENT" = "Environment setting"
    "MPESA_CONSUMER_KEY" = "Consumer Key"
    "MPESA_CONSUMER_SECRET" = "Consumer Secret"
    "MPESA_SHORTCODE" = "Business Shortcode"
    "MPESA_PASSKEY" = "STK Push Passkey"
}

Write-Host "Checking required configuration..." -ForegroundColor Yellow
Write-Host ""

foreach ($var in $requiredVars.Keys) {
    $description = $requiredVars[$var]
    if ($envVars.ContainsKey($var) -and $envVars[$var] -ne "") {
        Write-Host "✓ $description is set" -ForegroundColor Green
        
        # Specific checks
        if ($var -eq "MPESA_ENVIRONMENT") {
            $env = $envVars[$var]
            if ($env -eq "production") {
                Write-Host "  → Environment: PRODUCTION" -ForegroundColor Yellow
            } elseif ($env -eq "sandbox") {
                Write-Host "  → Environment: SANDBOX" -ForegroundColor Cyan
            } else {
                $issues += "Invalid MPESA_ENVIRONMENT value: $env (must be 'production' or 'sandbox')"
            }
        }
        
        if ($var -eq "MPESA_SHORTCODE") {
            $shortcode = $envVars[$var]
            if ($shortcode -notmatch '^\d+$') {
                $issues += "MPESA_SHORTCODE should be numeric"
            }
        }
    } else {
        Write-Host "✗ $description is NOT set" -ForegroundColor Red
        $issues += "$description is required but not set"
    }
}

Write-Host ""
Write-Host "Checking optional configuration..." -ForegroundColor Yellow
Write-Host ""

# Check optional variables
$optionalVars = @{
    "MPESA_INITIATOR_NAME" = "Initiator Name (required for refunds/reversals)"
    "MPESA_INITIATOR_PASSWORD" = "Initiator Password (required for refunds/reversals)"
    "MPESA_WEBHOOK_SECRET" = "Webhook Secret"
    "MPESA_CALLBACK_URL" = "Callback URL"
}

foreach ($var in $optionalVars.Keys) {
    $description = $optionalVars[$var]
    if ($envVars.ContainsKey($var) -and $envVars[$var] -ne "") {
        Write-Host "✓ $description is set" -ForegroundColor Green
    } else {
        Write-Host "⚠ $description is NOT set" -ForegroundColor Yellow
        $warnings += $description
    }
}

Write-Host ""
Write-Host "Checking callback URLs..." -ForegroundColor Yellow
Write-Host ""

$callbackUrls = @(
    "MPESA_CALLBACK_URL",
    "MPESA_TIMEOUT_URL",
    "MPESA_RESULT_URL",
    "MPESA_QUEUE_TIMEOUT_URL",
    "MPESA_VALIDATION_URL",
    "MPESA_CONFIRMATION_URL"
)

foreach ($url in $callbackUrls) {
    if ($envVars.ContainsKey($url) -and $envVars[$url] -ne "") {
        $urlValue = $envVars[$url]
        if ($urlValue -match '^https://') {
            Write-Host "✓ $url is HTTPS" -ForegroundColor Green
        } elseif ($urlValue -match '^http://') {
            Write-Host "✗ $url is HTTP (must be HTTPS in production)" -ForegroundColor Red
            if ($envVars["MPESA_ENVIRONMENT"] -eq "production") {
                $issues += "$url must use HTTPS in production"
            }
        } else {
            Write-Host "⚠ $url format may be invalid" -ForegroundColor Yellow
            $warnings += "$url format should be checked"
        }
    }
}

Write-Host ""
Write-Host "Checking security settings..." -ForegroundColor Yellow
Write-Host ""

if ($envVars.ContainsKey("MPESA_VERIFY_WEBHOOK_IP") -and $envVars["MPESA_VERIFY_WEBHOOK_IP"] -eq "true") {
    Write-Host "✓ Webhook IP verification is enabled" -ForegroundColor Green
} else {
    Write-Host "⚠ Webhook IP verification is disabled" -ForegroundColor Yellow
    if ($envVars["MPESA_ENVIRONMENT"] -eq "production") {
        $warnings += "Consider enabling webhook IP verification in production"
    }
}

Write-Host ""
Write-Host "Checking Laravel configuration..." -ForegroundColor Yellow
Write-Host ""

# Check if config files exist
if (Test-Path "config/mpesa.php") {
    Write-Host "✓ config/mpesa.php exists" -ForegroundColor Green
} else {
    Write-Host "✗ config/mpesa.php is missing" -ForegroundColor Red
    $issues += "M-PESA config file is missing"
}

if (Test-Path "config/services.php") {
    Write-Host "✓ config/services.php exists" -ForegroundColor Green
} else {
    Write-Host "✗ config/services.php is missing" -ForegroundColor Red
    $issues += "Services config file is missing"
}

Write-Host ""
Write-Host "Checking database tables..." -ForegroundColor Yellow
Write-Host ""

try {
    $tablesCheck = php artisan tinker --execute="echo Schema::hasTable('payment_transactions') ? 'yes' : 'no';" 2>&1
    if ($tablesCheck -match 'yes') {
        Write-Host "✓ payment_transactions table exists" -ForegroundColor Green
    } else {
        Write-Host "✗ payment_transactions table is missing" -ForegroundColor Red
        $issues += "Run migrations: php artisan migrate"
    }
} catch {
    Write-Host "⚠ Could not verify database tables" -ForegroundColor Yellow
    $warnings += "Manually verify database tables exist"
}

Write-Host ""
Write-Host "Checking routes..." -ForegroundColor Yellow
Write-Host ""

try {
    $routeCheck = php artisan route:list --name=mpesa 2>&1
    if ($routeCheck -match 'finance.mpesa') {
        Write-Host "✓ M-PESA routes are registered" -ForegroundColor Green
    } else {
        Write-Host "⚠ M-PESA routes may not be registered" -ForegroundColor Yellow
        $warnings += "Run: php artisan route:cache"
    }
} catch {
    Write-Host "⚠ Could not verify routes" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "Verification Summary" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""

if ($issues.Count -eq 0) {
    Write-Host "✓ No critical issues found!" -ForegroundColor Green
} else {
    Write-Host "✗ Found $($issues.Count) critical issue(s):" -ForegroundColor Red
    foreach ($issue in $issues) {
        Write-Host "  - $issue" -ForegroundColor Red
    }
}

Write-Host ""

if ($warnings.Count -gt 0) {
    Write-Host "⚠ Found $($warnings.Count) warning(s):" -ForegroundColor Yellow
    foreach ($warning in $warnings) {
        Write-Host "  - $warning" -ForegroundColor Yellow
    }
}

Write-Host ""

if ($issues.Count -eq 0) {
    Write-Host "Configuration Status: " -NoNewline
    if ($envVars["MPESA_ENVIRONMENT"] -eq "production") {
        Write-Host "READY FOR PRODUCTION" -ForegroundColor Green
    } else {
        Write-Host "CONFIGURED FOR SANDBOX" -ForegroundColor Cyan
    }
    
    Write-Host ""
    Write-Host "Next Steps:" -ForegroundColor Yellow
    Write-Host "1. Register callback URLs on Daraja Portal"
    Write-Host "2. Whitelist your server IP with Safaricom"
    Write-Host "3. Test with small amounts (KES 1)"
    Write-Host "4. Monitor logs: Get-Content storage\logs\laravel.log -Tail 50 -Wait"
} else {
    Write-Host "Configuration Status: " -NoNewline
    Write-Host "INCOMPLETE - Fix issues above" -ForegroundColor Red
}

Write-Host ""
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""

if ($issues.Count -gt 0) {
    exit 1
} else {
    exit 0
}

