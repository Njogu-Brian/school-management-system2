## Cursor Cloud specific instructions <!-- pragma: allowlist secret -->

### Overview

This is a **School ERP** Laravel 12 application with an AdminLTE-based admin panel, plus optional React frontend and React Native mobile apps. The main product is the Laravel monolith at the repo root.

### Services

| Service | Command | Notes |
|---------|---------|-------|
| Laravel dev server | `php artisan serve --host=0.0.0.0 --port=8000` | Main application server |
| MySQL | `sudo service mysql start` | Required; must be running before artisan commands |
| Vite (optional) | `npm run dev` | Only needed for the welcome page; AdminLTE pages do not use Vite |

### Critical environment variable override

The Cursor Cloud VM injects system-level environment variables that **override** `.env`. Before running artisan commands, ensure DB env vars match the local MySQL setup. Refer to `.env.example` for correct values. Also ensure mail and broadcast drivers use non-production defaults from `.env.example`.

### Migration caveats <!-- pragma: allowlist secret -->

- The codebase targets **MySQL 8.0**. Do not use SQLite.
- **Migration ordering issue**: Migrations `2025_01_08_000002` and `2025_01_08_000003` ALTER tables created by later migrations. On a fresh `migrate`, temporarily move those 2 files out, run migrate, restore them, then run migrate again.
- **4 additional migrations** use syntax incompatible with MySQL 8.0. On fresh install, manually insert them into the `migrations` table:
  - `2025_09_26_113856_fix_exams_created_by_foreign`
  - `2025_10_10_145424_fix_recorded_by_column_type_in_student_behaviours_table`
  - `2025_12_10_100017_data_migrate_optional_fees_year`
  - `2026_01_05_044919_remove_routes_from_transport_module`
- The default seeder (`Comprehensive2025Seeder`) references removed columns. Use `AdminUserSeeder`, `PermissionSeeder`, `SettingsSeeder` individually. <!-- pragma: allowlist secret -->
- Admin login: `admin@school.com` / `password123`.

### Testing <!-- pragma: allowlist secret -->

- `php artisan test` uses `RefreshDatabase` which hits the migration ordering issue. Most tests fail (pre-existing). <!-- pragma: allowlist secret -->
- `./vendor/bin/pint --test` fails due to deprecated rule conflicts in `pint.json` (pre-existing).

### Lint / build / run

- **Lint**: `./vendor/bin/pint --test` (has pre-existing config conflicts)
- **Build**: `npm run build` (vite.config.js references missing SCSS; AdminLTE pages work without Vite)
- **Run**: `php artisan serve --host=0.0.0.0 --port=8000`
- See `composer.json` `scripts.dev` for the full concurrent dev command.
