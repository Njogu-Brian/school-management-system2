#!/bin/bash
# Senior Teacher Bug Fixes - Deployment Script
# Run this on your production server after pulling the latest code

echo "=========================================="
echo "Senior Teacher Bug Fixes - Deployment"
echo "=========================================="
echo ""

# Step 1: Pull latest code
echo "Step 1: Pulling latest code from GitHub..."
git pull origin main

if [ $? -ne 0 ]; then
    echo "❌ Error pulling code. Please check your git status."
    exit 1
fi
echo "✅ Code updated successfully"
echo ""

# Step 2: Fix storage permissions (prevents "Permission denied" on view compilation)
echo "Step 2: Fixing storage/bootstrap permissions..."
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
# Use www-data on Ubuntu/Debian, apache on CentOS, or $USER on cPanel/shared hosting
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
echo "✅ Permissions set"
echo ""

# Step 3: Clear all caches
echo "Step 3: Clearing all caches..."
php artisan route:clear
php artisan view:clear
php artisan config:clear
php artisan cache:clear
echo "✅ Caches cleared successfully"
echo ""

# Step 4: Run migrations (if needed)
echo "Step 4: Running migrations (if any)..."
php artisan migrate --force
echo "✅ Migrations completed"
echo ""

# Step 5: Seed permissions (if not already seeded)
echo "Step 5: Seeding Senior Teacher permissions..."
php artisan db:seed --class=SeniorTeacherPermissionsSeeder --force
echo "✅ Permissions seeded"
echo ""

# Step 6: Optimize for production
echo "Step 6: Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo "✅ Optimization complete"
echo ""

# Step 7: Verify routes
echo "Step 7: Verifying routes are registered..."
echo ""
echo "Senior Teacher Routes:"
php artisan route:list --path=senior-teacher
echo ""
echo "Diary Routes:"
php artisan route:list --name=diaries
echo ""

echo "=========================================="
echo "✅ Deployment Complete!"
echo "=========================================="
echo ""
echo "Next Steps:"
echo "1. Test the admin dashboard: https://erp.royalkingsschools.sc.ke/admin/home"
echo "2. Assign Senior Teacher role to staff using the admin panel"
echo "3. Configure supervisory assignments for Senior Teachers"
echo "4. Clear your browser cache (Ctrl+Shift+R)"
echo ""
echo "If you encounter any issues, check:"
echo "- storage/logs/laravel.log for error details"
echo "- Ensure APP_ENV=production in .env"
echo "- Run 'php artisan cache:clear' if needed"
echo ""

