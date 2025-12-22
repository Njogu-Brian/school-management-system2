<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReverseSeeder extends Seeder
{
    /**
     * Reverse database seeding by truncating seeded tables
     * WARNING: This will delete all data from these tables!
     */
    public function run(): void
    {
        $this->command->warn('⚠️  WARNING: This will delete all data from seeded tables!');
        
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // List of tables to truncate (in reverse order of dependencies)
        $tables = [
            // Demo data tables (most dependent)
            'exam_papers',
            'classroom_subjects',
            'payroll_records',
            'payroll_periods',
            'salary_structures',
            'payment_transactions',
            'requisition_items',
            'requisitions',
            'student_requirements',
            'order_items',
            'orders',
            'product_variants',
            'products',
            'requirement_templates',
            'requirement_types',
            'inventory_transactions',
            'inventory_items',
            'hostel_allocations',
            'hostel_rooms',
            'hostels',
            'book_borrowings',
            'library_cards',
            'book_copies',
            'books',
            'student_assignments',
            'trips',
            'drop_off_points',
            'payments',
            'invoice_items',
            'invoices',
            'fee_charges',
            'fee_structures',
            'voteheads',
            'attendances',
            'students',
            'student_categories',
            'families',
            'parent_infos',
            'staff',
            'users',
            'terms',
            'academic_years',
            'streams',
            'classrooms',
            
            // Reference data tables
            'competencies',
            'cbc_substrands',
            'cbc_strands',
            'learning_areas',
            'exam_grades',
            'subjects',
            'subject_groups',
            'grading_schemes',
            'grading_scheme_mappings',
            'grading_bands',
            'behaviours',
            'communication_templates',
            'email_templates',
            'attendance_notification_templates',
            'settings',
            'vehicles',
            'routes',
            'payment_methods',
            'votehead_categories',
            'bank_accounts',
            'document_counters',
            
            // Permissions and roles (Spatie tables)
            'model_has_permissions',
            'model_has_roles',
            'role_has_permissions',
            'permissions',
            'roles',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                try {
                    DB::table($table)->truncate();
                    $this->command->info("✓ Truncated: {$table}");
                } catch (\Exception $e) {
                    $this->command->warn("⚠ Could not truncate {$table}: " . $e->getMessage());
                }
            }
        }

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('✅ Database seeding reversed successfully!');
    }
}

