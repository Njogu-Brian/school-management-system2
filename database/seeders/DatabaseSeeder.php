<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // 1. Roles and Permissions (must be first)
            RolesAndPermissionsSeeder::class,
            PermissionSeeder::class,
            AcademicPermissionsSeeder::class,
            TeacherPermissionsSeeder::class,

            // 2. Core reference data
            PaymentMethodSeeder::class,
            VoteheadCategorySeeder::class,
            BankAccountSeeder::class,

            // 3. Academic reference data
            CBCComprehensiveSeeder::class,
            GradingSchemeSeeder::class,
            SubjectGroupSeeder::class,
            SubjectSeeder::class,
            ExamGradeSeeder::class,
            BehaviourSeeder::class,

            // 4. Settings and templates
            SettingsSeeder::class,
            AttendanceNotificationTemplateSeeder::class,
            CommunicationTemplateSeeder::class,
            CommunicationTemplatesSeeder::class,

            // 5. Routes and vehicles
            RouteSeeder::class,
            VehicleSeeder::class,

            // 6. Document counters
            DocumentCounterSeeder::class,

            // 7. Admin user (needs roles/permissions to exist)
            AdminUserSeeder::class,

            // 8. Unified demo data (covers all major tables: students, staff, families, invoices, etc.)
            DemoDataSeeder::class,

            // 9. Teacher assignments and exam papers (needs demo data to exist)
            TeacherAssignmentSeeder::class,
            ExamPaperSeeder::class,
        ]);
    }
}
