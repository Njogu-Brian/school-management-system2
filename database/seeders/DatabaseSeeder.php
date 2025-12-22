<?php

namespace Database\Seeders;

use App\Models\Academics\Exam;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
public function run(): void
{
    $this->call([
        // Core auth/permissions
        RolesAndPermissionsSeeder::class,
        PermissionSeeder::class,
        TeacherPermissionsSeeder::class,

        // Reference/config
        PaymentMethodSeeder::class,
        VoteheadCategorySeeder::class,
        CBCComprehensiveSeeder::class,
        GradingSchemeSeeder::class,
        SubjectGroupSeeder::class,
        SubjectSeeder::class,
        ExamGradeSeeder::class,
        BehaviourSeeder::class,
        SettingsSeeder::class,
        AttendanceNotificationTemplateSeeder::class,
        CommunicationTemplateSeeder::class,
        CommunicationTemplatesSeeder::class,
        RouteSeeder::class,
        VehicleSeeder::class,
        DocumentCounterSeeder::class,
        AdminUserSeeder::class,

        // Demo data + dependents
        DemoDataSeeder::class,
        TeacherAssignmentSeeder::class,
        ExamPaperSeeder::class,
    ]);
}


}
