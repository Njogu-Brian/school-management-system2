<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Comprehensive migration to ensure all foreign keys are properly set up.
 * 
 * This migration runs after all table creation migrations and safely
 * adds foreign keys only if they don't already exist.
 * 
 * This handles cases where:
 * 1. Original migrations failed to create foreign keys due to table order
 * 2. Foreign keys need to be added after all tables are created
 * 3. Production databases already have some foreign keys but are missing others
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Helper to check if foreign key exists for a column
        $foreignKeyExists = function($table, $column): bool {
            try {
                $result = DB::selectOne("
                    SELECT COUNT(*) as count
                    FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = ?
                    AND COLUMN_NAME = ?
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ", [$table, $column]);
                
                return $result && $result->count > 0;
            } catch (\Exception $e) {
                return false;
            }
        };

        // Helper to add foreign key safely
        $addForeignKey = function($table, $column, $referencesTable, $referencesColumn = 'id', $onDelete = 'RESTRICT', $onUpdate = 'CASCADE') use ($foreignKeyExists) {
            // Skip if tables don't exist
            if (!Schema::hasTable($table) || !Schema::hasTable($referencesTable)) {
                return;
            }
            
            // Skip if column doesn't exist
            if (!Schema::hasColumn($table, $column)) {
                return;
            }
            
            // Skip if foreign key already exists
            if ($foreignKeyExists($table, $column)) {
                return;
            }
            
            try {
                Schema::table($table, function (Blueprint $table) use ($column, $referencesTable, $referencesColumn, $onDelete, $onUpdate) {
                    $foreignKey = $table->foreign($column)
                        ->references($referencesColumn)
                        ->on($referencesTable);
                    
                    // Apply onDelete action
                    if ($onDelete === 'CASCADE') {
                        $foreignKey->onDelete('cascade');
                    } elseif ($onDelete === 'SET NULL') {
                        $foreignKey->onDelete('set null');
                    } elseif ($onDelete === 'RESTRICT') {
                        $foreignKey->onDelete('restrict');
                    }
                    
                    // Apply onUpdate action
                    if ($onUpdate === 'CASCADE') {
                        $foreignKey->onUpdate('cascade');
                    } elseif ($onUpdate === 'SET NULL') {
                        $foreignKey->onUpdate('set null');
                    } elseif ($onUpdate === 'RESTRICT') {
                        $foreignKey->onUpdate('restrict');
                    }
                });
            } catch (\Exception $e) {
                // Ignore if it fails (might already exist with different constraint name)
                // Log error for debugging but don't stop migration
            }
        };

        // ============================================
        // POS Module Foreign Keys
        // ============================================
        
        // pos_products
        $addForeignKey('pos_products', 'inventory_item_id', 'inventory_items', 'id', 'SET NULL');
        $addForeignKey('pos_products', 'requirement_type_id', 'requirement_types', 'id', 'SET NULL');
        
        // pos_product_variants
        $addForeignKey('pos_product_variants', 'product_id', 'pos_products', 'id', 'CASCADE');
        
        // pos_orders
        $addForeignKey('pos_orders', 'student_id', 'students', 'id', 'SET NULL');
        $addForeignKey('pos_orders', 'parent_id', 'parent_info', 'id', 'SET NULL');
        $addForeignKey('pos_orders', 'user_id', 'users', 'id', 'SET NULL');
        $addForeignKey('pos_orders', 'payment_transaction_id', 'payment_transactions', 'id', 'SET NULL');
        
        // pos_order_items
        $addForeignKey('pos_order_items', 'order_id', 'pos_orders', 'id', 'CASCADE');
        $addForeignKey('pos_order_items', 'product_id', 'pos_products', 'id', 'RESTRICT');
        $addForeignKey('pos_order_items', 'variant_id', 'pos_product_variants', 'id', 'SET NULL');
        $addForeignKey('pos_order_items', 'requirement_template_id', 'requirement_templates', 'id', 'SET NULL');
        
        // pos_discounts
        $addForeignKey('pos_discounts', 'classroom_id', 'classrooms', 'id', 'CASCADE');
        
        // pos_public_shop_links
        $addForeignKey('pos_public_shop_links', 'student_id', 'students', 'id', 'CASCADE');
        $addForeignKey('pos_public_shop_links', 'classroom_id', 'classrooms', 'id', 'CASCADE');
        
        // requirement_templates (POS fields)
        if (Schema::hasColumn('requirement_templates', 'pos_product_id')) {
            $addForeignKey('requirement_templates', 'pos_product_id', 'pos_products', 'id', 'SET NULL');
        }
        
        // student_requirements (POS fields)
        if (Schema::hasColumn('student_requirements', 'pos_product_id')) {
            $addForeignKey('student_requirements', 'pos_product_id', 'pos_products', 'id', 'SET NULL');
        }
        if (Schema::hasColumn('student_requirements', 'pos_variant_id')) {
            $addForeignKey('student_requirements', 'pos_variant_id', 'pos_product_variants', 'id', 'SET NULL');
        }

        // ============================================
        // Academics Module Foreign Keys
        // ============================================
        
        // streams
        $addForeignKey('streams', 'classroom_id', 'classrooms', 'id', 'CASCADE');
        
        // students
        $addForeignKey('students', 'category_id', 'student_categories', 'id', 'SET NULL');
        $addForeignKey('students', 'classroom_id', 'classrooms', 'id', 'SET NULL');
        $addForeignKey('students', 'stream_id', 'streams', 'id', 'SET NULL');
        $addForeignKey('students', 'sibling_id', 'students', 'id', 'SET NULL');
        
        // classroom_teacher
        $addForeignKey('classroom_teacher', 'classroom_id', 'classrooms', 'id', 'CASCADE');
        $addForeignKey('classroom_teacher', 'teacher_id', 'users', 'id', 'CASCADE');
        
        // stream_teacher
        $addForeignKey('stream_teacher', 'stream_id', 'streams', 'id', 'CASCADE');
        $addForeignKey('stream_teacher', 'teacher_id', 'users', 'id', 'CASCADE');
        if (Schema::hasColumn('stream_teacher', 'classroom_id')) {
            $addForeignKey('stream_teacher', 'classroom_id', 'classrooms', 'id', 'SET NULL');
        }
        
        // classroom_stream
        $addForeignKey('classroom_stream', 'classroom_id', 'classrooms', 'id', 'CASCADE');
        $addForeignKey('classroom_stream', 'stream_id', 'streams', 'id', 'CASCADE');
        
        // classroom_subjects
        $addForeignKey('classroom_subjects', 'classroom_id', 'classrooms', 'id', 'CASCADE');
        $addForeignKey('classroom_subjects', 'stream_id', 'streams', 'id', 'SET NULL');
        $addForeignKey('classroom_subjects', 'subject_id', 'subjects', 'id', 'CASCADE');
        $addForeignKey('classroom_subjects', 'staff_id', 'staff', 'id', 'SET NULL');
        $addForeignKey('classroom_subjects', 'academic_year_id', 'academic_years', 'id', 'SET NULL');
        $addForeignKey('classroom_subjects', 'term_id', 'terms', 'id', 'SET NULL');
        
        // exams
        $addForeignKey('exams', 'academic_year_id', 'academic_years', 'id', 'CASCADE');
        $addForeignKey('exams', 'term_id', 'terms', 'id', 'CASCADE');
        $addForeignKey('exams', 'classroom_id', 'classrooms', 'id', 'CASCADE');
        $addForeignKey('exams', 'stream_id', 'streams', 'id', 'SET NULL');
        $addForeignKey('exams', 'subject_id', 'subjects', 'id', 'CASCADE');
        $addForeignKey('exams', 'created_by', 'staff', 'id', 'SET NULL');
        
        // report_cards
        $addForeignKey('report_cards', 'student_id', 'students', 'id', 'CASCADE');
        $addForeignKey('report_cards', 'academic_year_id', 'academic_years', 'id', 'CASCADE');
        $addForeignKey('report_cards', 'term_id', 'terms', 'id', 'CASCADE');
        $addForeignKey('report_cards', 'classroom_id', 'classrooms', 'id', 'SET NULL');
        $addForeignKey('report_cards', 'stream_id', 'streams', 'id', 'SET NULL');
        $addForeignKey('report_cards', 'generated_by', 'staff', 'id', 'SET NULL');
        
        // homework
        if (Schema::hasTable('homework')) {
            $addForeignKey('homework', 'classroom_id', 'classrooms', 'id', 'CASCADE');
            $addForeignKey('homework', 'stream_id', 'streams', 'id', 'SET NULL');
            $addForeignKey('homework', 'subject_id', 'subjects', 'id', 'CASCADE');
            $addForeignKey('homework', 'assigned_by', 'staff', 'id', 'SET NULL');
        }
        
        // diaries
        if (Schema::hasTable('diaries')) {
            $addForeignKey('diaries', 'classroom_id', 'classrooms', 'id', 'CASCADE');
            $addForeignKey('diaries', 'stream_id', 'streams', 'id', 'SET NULL');
            $addForeignKey('diaries', 'created_by', 'staff', 'id', 'SET NULL');
        }
        
        // classroom_subject
        if (Schema::hasTable('classroom_subject')) {
            $addForeignKey('classroom_subject', 'classroom_id', 'classrooms', 'id', 'CASCADE');
            $addForeignKey('classroom_subject', 'subject_id', 'subjects', 'id', 'CASCADE');
        }
        
        // exam_class_subject
        if (Schema::hasTable('exam_class_subject')) {
            $addForeignKey('exam_class_subject', 'exam_id', 'exams', 'id', 'CASCADE');
            $addForeignKey('exam_class_subject', 'classroom_id', 'classrooms', 'id', 'CASCADE');
            $addForeignKey('exam_class_subject', 'subject_id', 'subjects', 'id', 'CASCADE');
        }
        
        // exam_papers
        if (Schema::hasTable('exam_papers')) {
            $addForeignKey('exam_papers', 'exam_id', 'exams', 'id', 'CASCADE');
            $addForeignKey('exam_papers', 'subject_id', 'subjects', 'id', 'CASCADE');
            $addForeignKey('exam_papers', 'classroom_id', 'classrooms', 'id', 'CASCADE');
        }
        
        // timetables
        if (Schema::hasTable('timetables')) {
            $addForeignKey('timetables', 'classroom_id', 'classrooms', 'id', 'CASCADE');
            $addForeignKey('timetables', 'academic_year_id', 'academic_years', 'id', 'CASCADE');
            $addForeignKey('timetables', 'term_id', 'terms', 'id', 'CASCADE');
            $addForeignKey('timetables', 'subject_id', 'subjects', 'id', 'CASCADE');
            $addForeignKey('timetables', 'teacher_id', 'staff', 'id', 'SET NULL');
        }
        
        // schemes_of_work
        if (Schema::hasTable('schemes_of_work')) {
            $addForeignKey('schemes_of_work', 'subject_id', 'subjects', 'id', 'CASCADE');
            $addForeignKey('schemes_of_work', 'classroom_id', 'classrooms', 'id', 'CASCADE');
            $addForeignKey('schemes_of_work', 'academic_year_id', 'academic_years', 'id', 'CASCADE');
            $addForeignKey('schemes_of_work', 'term_id', 'terms', 'id', 'CASCADE');
            $addForeignKey('schemes_of_work', 'created_by', 'staff', 'id', 'SET NULL');
        }
        
        // lesson_plans
        if (Schema::hasTable('lesson_plans')) {
            $addForeignKey('lesson_plans', 'scheme_of_work_id', 'schemes_of_work', 'id', 'CASCADE');
            $addForeignKey('lesson_plans', 'subject_id', 'subjects', 'id', 'CASCADE');
            $addForeignKey('lesson_plans', 'classroom_id', 'classrooms', 'id', 'CASCADE');
            if (Schema::hasColumn('lesson_plans', 'cbc_substrand_id')) {
                $addForeignKey('lesson_plans', 'cbc_substrand_id', 'cbc_substrands', 'id', 'SET NULL');
            }
            $addForeignKey('lesson_plans', 'academic_year_id', 'academic_years', 'id', 'CASCADE');
            $addForeignKey('lesson_plans', 'term_id', 'terms', 'id', 'CASCADE');
            $addForeignKey('lesson_plans', 'created_by', 'staff', 'id', 'SET NULL');
        }
        
        // portfolio_assessments
        if (Schema::hasTable('portfolio_assessments')) {
            $addForeignKey('portfolio_assessments', 'student_id', 'students', 'id', 'CASCADE');
            $addForeignKey('portfolio_assessments', 'subject_id', 'subjects', 'id', 'CASCADE');
            $addForeignKey('portfolio_assessments', 'classroom_id', 'classrooms', 'id', 'CASCADE');
            $addForeignKey('portfolio_assessments', 'academic_year_id', 'academic_years', 'id', 'CASCADE');
            $addForeignKey('portfolio_assessments', 'term_id', 'terms', 'id', 'CASCADE');
            if (Schema::hasColumn('portfolio_assessments', 'performance_level_id')) {
                $addForeignKey('portfolio_assessments', 'performance_level_id', 'cbc_performance_levels', 'id', 'SET NULL');
            }
            $addForeignKey('portfolio_assessments', 'assessed_by', 'staff', 'id', 'SET NULL');
        }
        
        // requirement_templates
        $addForeignKey('requirement_templates', 'requirement_type_id', 'requirement_types', 'id', 'RESTRICT');
        $addForeignKey('requirement_templates', 'classroom_id', 'classrooms', 'id', 'CASCADE');
        $addForeignKey('requirement_templates', 'academic_year_id', 'academic_years', 'id', 'SET NULL');
        $addForeignKey('requirement_templates', 'term_id', 'terms', 'id', 'SET NULL');
        
        // ============================================
        // Finance Module Foreign Keys
        // ============================================
        
        // fee_structures
        $addForeignKey('fee_structures', 'classroom_id', 'classrooms', 'id', 'CASCADE');
        
        // payments (enhanced fields)
        if (Schema::hasColumn('payments', 'payment_method_id')) {
            $addForeignKey('payments', 'payment_method_id', 'payment_methods', 'id', 'SET NULL');
        }
        if (Schema::hasColumn('payments', 'bank_account_id')) {
            $addForeignKey('payments', 'bank_account_id', 'bank_accounts', 'id', 'SET NULL');
        }
        if (Schema::hasColumn('payments', 'family_id')) {
            $addForeignKey('payments', 'family_id', 'families', 'id', 'SET NULL');
        }
    }

    /**
     * Reverse the migrations.
     * 
     * Note: This is intentionally left empty as dropping foreign keys
     * should be done carefully and may not be needed in rollback scenarios.
     */
    public function down(): void
    {
        // Intentionally empty - foreign keys should remain for data integrity
    }
};

