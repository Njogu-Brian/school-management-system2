#!/bin/bash
# EC2 Deployment Script for School ERP
# Usage: Run on EC2 or locally (via SSH): ssh -i erp-key.pem ubuntu@13.245.211.78 'bash -s' < scripts/deploy-ec2.sh
# Or SSH in and run: cd /var/www/erp && ./scripts/deploy-ec2.sh

set -e
cd /var/www/erp

echo "=========================================="
echo "  School ERP - EC2 Deployment"
echo "=========================================="

# Step 1: Pull latest code
echo ""
echo "[1/8] Pulling latest code..."
git pull origin main
echo "✓ Code updated"

# Step 2: Composer
echo ""
echo "[2/8] Installing dependencies..."
composer install --no-dev --optimize-autoloader
echo "✓ Composer done"

# Step 3: NPM/Vite (if package.json exists)
if [ -f package.json ]; then
    echo ""
    echo "[3/8] Building frontend assets..."
    npm ci
    npm run build
else
    echo ""
    echo "[3/8] Skipping frontend (no package.json)"
fi
echo "✓ Assets built"

# Step 4: Environment
echo ""
echo "[4/8] Ensuring .env exists..."
if [ ! -f .env ]; then
    cp .env.example .env
    echo "⚠ Created .env from .env.example - EDIT .env with your DB and AWS settings!"
    php artisan key:generate
else
    echo "✓ .env present"
fi

# Step 5: Migrations
echo ""
echo "[5/8] Running migrations..."
php artisan migrate --force
echo "✓ Migrations done"

# Step 6: Storage
echo ""
echo "[6/8] Storage setup..."
php artisan storage:link 2>/dev/null || true
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
echo "✓ Storage linked and permissions set"

# Step 7: Optimize
echo ""
echo "[7/8] Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
echo "✓ Caches rebuilt"

# Step 8: Queue (restart workers if using supervisor)
echo ""
echo "[8/8] Queue workers..."
if command -v supervisorctl &>/dev/null; then
    supervisorctl restart erp-worker:* 2>/dev/null || supervisorctl restart all 2>/dev/null || true
fi
echo "✓ Deployment complete"

echo ""
echo "=========================================="
echo "  Deployment Complete"
echo "=========================================="
echo ""
echo "Next steps:"
echo "  1. Ensure .env has correct DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD"
echo "  2. If using S3: set AWS_*, FILESYSTEM_PUBLIC_DISK=s3_public, FILESYSTEM_PRIVATE_DISK=s3_private"
echo "  3. Run migration of existing files to S3: php artisan storage:migrate-to-s3"
echo ""
