# Development Guide

This document provides instructions for setting up and developing the School Management System locally.

## Prerequisites

- **PHP:** 8.2 or higher
- **Composer:** 2.x
- **Node.js:** 18.x or higher
- **MySQL/MariaDB:** 5.7+ or PostgreSQL 12+
- **Git**

## Local Setup

### 1. Clone the Repository

```bash
git clone <repository-url>
cd school-management-system2
```

### 2. Install Dependencies

```bash
# PHP dependencies
composer install

# Node.js dependencies
npm install
```

### 3. Environment Configuration

**IMPORTANT:** Never modify the production `.env` file. Always work with `.env.example` or a local `.env` file.

```bash
# Copy example environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 4. Database Setup

**CRITICAL:** Use a separate development database. Never run migrations against production.

```bash
# Create a local development database
mysql -u root -p
CREATE DATABASE school_management_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;

# Update .env with development database credentials
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=school_management_dev
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 5. Run Migrations (Development Only)

**WARNING:** Only run migrations on development databases with sample data.

```bash
# Run migrations
php artisan migrate

# Seed with sample data (if available)
php artisan db:seed
```

### 6. Start Development Server

```bash
# Using Laravel's built-in server
php artisan serve

# Or using the dev script (includes queue, logs, vite)
composer dev
```

The application will be available at `http://localhost:8000`

## Development Workflow

### Creating a Feature Branch

```bash
# Create and switch to feature branch
git checkout -b feature/your-feature-name

# Make your changes
# ...

# Commit with descriptive message
git commit -m "feat(module): description of changes"

# Push to remote
git push origin feature/your-feature-name
```

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage
```

### Code Style

```bash
# Check code style
./vendor/bin/pint --test

# Fix code style issues
./vendor/bin/pint
```

### Static Analysis

```bash
# Run PHPStan (if configured)
./vendor/bin/phpstan analyse
```

## Database Safety

### Before Running Migrations

**ALWAYS** create a backup before running migrations, especially in staging/production:

```bash
# Create backup
./scripts/backup-db.sh production

# Verify backup was created
ls -lh backups/
```

### Creating Migrations

1. **Always make migrations non-destructive:**
   - Add new columns/tables (don't drop existing ones)
   - Use nullable columns when possible
   - Provide default values for new required fields

2. **For destructive changes, use two-step migration:**
   ```php
   // Step 1: Add new column, keep old one
   $table->string('new_column')->nullable();
   
   // Step 2 (separate migration after testing): Drop old column
   $table->dropColumn('old_column');
   ```

3. **Test migrations on development database first:**
   ```bash
   php artisan migrate:fresh --seed  # Only on dev DB!
   ```

## Testing

### Writing Tests

- **Unit Tests:** Test individual classes/methods in isolation
- **Feature Tests:** Test HTTP endpoints and full workflows
- **Integration Tests:** Test database interactions

Example test structure:
```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
```

### Test Database

Tests use a separate database (configured in `phpunit.xml`). The test database is automatically refreshed between tests.

## Debugging

### Laravel Debugging

```bash
# Enable debug mode (development only)
APP_DEBUG=true

# Use Laravel Telescope (if installed)
php artisan telescope:install
```

### Logging

```bash
# View logs
tail -f storage/logs/laravel.log

# Or use Laravel Pail
php artisan pail
```

## Common Tasks

### Creating a New Controller

```bash
php artisan make:controller Module/ControllerName
```

### Creating a New Model

```bash
php artisan make:model ModelName -m  # -m creates migration
```

### Creating a Migration

```bash
php artisan make:migration create_table_name
```

### Creating a Seeder

```bash
php artisan make:seeder TableNameSeeder
```

## Troubleshooting

### Database Connection Issues

```bash
# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();
```

### Cache Issues

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Permission Issues

```bash
# Fix storage permissions (Linux/Mac)
chmod -R 775 storage bootstrap/cache
```

## Code Quality Standards

1. **Follow PSR-12 coding standards**
2. **Write tests for all new features**
3. **Use type hints and return types**
4. **Document complex logic with comments**
5. **Keep functions small and focused**
6. **Use meaningful variable and function names**

## Security Best Practices

1. **Never commit `.env` file**
2. **Never commit real API keys or secrets**
3. **Use environment variables for sensitive data**
4. **Validate and sanitize all user input**
5. **Use prepared statements (Eloquent does this automatically)**
6. **Implement proper authorization checks**

## Getting Help

- Check the [Laravel Documentation](https://laravel.com/docs)
- Review existing code for patterns
- Ask in team chat or create an issue

## Next Steps

After setting up locally:
1. Read `DEPLOYMENT.md` for deployment procedures
2. Read `DB_README.md` for database schema information
3. Review `AUTOMATION_REPORT.md` for current project status

