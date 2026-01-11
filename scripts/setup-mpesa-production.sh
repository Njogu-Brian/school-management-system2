#!/bin/bash

# M-PESA Production Setup Script
# This script helps configure M-PESA for production use

echo "=========================================="
echo "M-PESA Production Configuration Setup"
echo "=========================================="
echo ""

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if .env file exists
if [ ! -f .env ]; then
    echo -e "${RED}Error: .env file not found!${NC}"
    echo "Please create a .env file first."
    exit 1
fi

echo -e "${YELLOW}This script will help you configure M-PESA for production.${NC}"
echo ""
echo "Before continuing, ensure you have:"
echo "  1. Production Consumer Key from Daraja Portal"
echo "  2. Production Consumer Secret from Daraja Portal"
echo "  3. Business Shortcode (Paybill Number)"
echo "  4. STK Push Passkey from Daraja Portal"
echo "  5. Initiator credentials (optional)"
echo ""
read -p "Do you have all the required credentials? (y/n): " -n 1 -r
echo ""

if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo -e "${YELLOW}Please obtain your credentials from https://developer.safaricom.co.ke/ first.${NC}"
    echo "See MPESA_PRODUCTION_CONFIG.md for detailed instructions."
    exit 0
fi

# Function to update or add environment variable
update_env() {
    local key=$1
    local value=$2
    
    if grep -q "^${key}=" .env; then
        # Update existing key
        if [[ "$OSTYPE" == "darwin"* ]]; then
            # macOS
            sed -i '' "s|^${key}=.*|${key}=${value}|" .env
        else
            # Linux
            sed -i "s|^${key}=.*|${key}=${value}|" .env
        fi
    else
        # Add new key
        echo "${key}=${value}" >> .env
    fi
}

echo ""
echo "=========================================="
echo "Step 1: Set Environment to Production"
echo "=========================================="
update_env "MPESA_ENVIRONMENT" "production"
echo -e "${GREEN}✓ Environment set to production${NC}"

echo ""
echo "=========================================="
echo "Step 2: Enter Production Credentials"
echo "=========================================="

read -p "Enter Consumer Key: " consumer_key
update_env "MPESA_CONSUMER_KEY" "$consumer_key"

read -sp "Enter Consumer Secret: " consumer_secret
echo ""
update_env "MPESA_CONSUMER_SECRET" "$consumer_secret"

read -p "Enter Business Shortcode (Paybill Number): " shortcode
update_env "MPESA_SHORTCODE" "$shortcode"

read -p "Enter STK Push Passkey: " passkey
update_env "MPESA_PASSKEY" "$passkey"

echo -e "${GREEN}✓ Credentials configured${NC}"

echo ""
echo "=========================================="
echo "Step 3: Initiator Credentials (Optional)"
echo "=========================================="
read -p "Do you have initiator credentials? (y/n): " -n 1 -r
echo ""

if [[ $REPLY =~ ^[Yy]$ ]]; then
    read -p "Enter Initiator Name: " initiator_name
    update_env "MPESA_INITIATOR_NAME" "$initiator_name"
    
    read -sp "Enter Initiator Password: " initiator_password
    echo ""
    update_env "MPESA_INITIATOR_PASSWORD" "$initiator_password"
    
    echo -e "${GREEN}✓ Initiator credentials configured${NC}"
else
    echo -e "${YELLOW}⚠ Skipping initiator credentials (reversals/refunds will not work)${NC}"
fi

echo ""
echo "=========================================="
echo "Step 4: Configure Callback URLs"
echo "=========================================="

# Set default callback URLs for Royal Kings School
APP_URL="https://erp.royalkingsschools.sc.ke"
update_env "MPESA_CALLBACK_URL" "${APP_URL}/webhooks/payment/mpesa"
update_env "MPESA_TIMEOUT_URL" "${APP_URL}/webhooks/payment/mpesa/timeout"
update_env "MPESA_RESULT_URL" "${APP_URL}/webhooks/payment/mpesa/result"
update_env "MPESA_QUEUE_TIMEOUT_URL" "${APP_URL}/webhooks/payment/mpesa/queue-timeout"
update_env "MPESA_VALIDATION_URL" "${APP_URL}/webhooks/payment/mpesa/validation"
update_env "MPESA_CONFIRMATION_URL" "${APP_URL}/webhooks/payment/mpesa/confirmation"

echo -e "${GREEN}✓ Callback URLs configured for: ${APP_URL}${NC}"

echo ""
echo "=========================================="
echo "Step 5: Security Configuration"
echo "=========================================="

# Generate webhook secret if not exists
if ! grep -q "^MPESA_WEBHOOK_SECRET=" .env || grep -q "^MPESA_WEBHOOK_SECRET=$" .env; then
    webhook_secret=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-32)
    update_env "MPESA_WEBHOOK_SECRET" "$webhook_secret"
    echo -e "${GREEN}✓ Generated webhook secret${NC}"
fi

update_env "MPESA_VERIFY_WEBHOOK_IP" "true"
echo -e "${GREEN}✓ Webhook IP verification enabled${NC}"

echo ""
echo "=========================================="
echo "Step 6: Enable Features"
echo "=========================================="

update_env "MPESA_FEATURE_STK_PUSH" "true"
update_env "MPESA_FEATURE_C2B" "true"
update_env "MPESA_FEATURE_REVERSAL" "true"
update_env "MPESA_LOGGING_ENABLED" "true"

echo -e "${GREEN}✓ Features configured${NC}"

echo ""
echo "=========================================="
echo "Step 7: Clear Configuration Cache"
echo "=========================================="

php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo -e "${GREEN}✓ Cache cleared${NC}"

echo ""
echo "=========================================="
echo "Step 8: Rebuild Cache"
echo "=========================================="

php artisan config:cache
php artisan route:cache
php artisan view:cache

echo -e "${GREEN}✓ Cache rebuilt${NC}"

echo ""
echo "=========================================="
echo "Configuration Complete!"
echo "=========================================="
echo ""
echo -e "${GREEN}✓ M-PESA is now configured for PRODUCTION${NC}"
echo ""
echo -e "${YELLOW}IMPORTANT NEXT STEPS:${NC}"
echo ""
echo "1. Register callback URLs on Daraja Portal:"
echo "   - Login to https://developer.safaricom.co.ke/"
echo "   - Navigate to your production app"
echo "   - Register callback URL: ${APP_URL}/webhooks/payment/mpesa"
echo ""
echo "2. Whitelist your server IP with Safaricom"
echo "   - Contact: apisupport@safaricom.co.ke"
echo "   - Provide your server's public IP address"
echo ""
echo "3. Test with small amounts first (KES 1)"
echo "   - Navigate to: ${APP_URL}/finance/mpesa/prompt-payment"
echo "   - Test payment flow end-to-end"
echo ""
echo "4. Monitor logs for any issues:"
echo "   tail -f storage/logs/laravel.log | grep -i mpesa"
echo ""
echo "5. Check database for transaction records:"
echo "   mysql> SELECT * FROM payment_transactions WHERE gateway='mpesa' ORDER BY created_at DESC LIMIT 10;"
echo ""
echo -e "${YELLOW}See MPESA_PRODUCTION_CONFIG.md for detailed documentation.${NC}"
echo ""

