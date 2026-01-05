<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('requirement_templates')) {
            return;
        }

        Schema::table('requirement_templates', function (Blueprint $table) {
            // Add custody type: 'school_custody' or 'parent_custody'
            if (!Schema::hasColumn('requirement_templates', 'custody_type')) {
                $table->enum('custody_type', ['school_custody', 'parent_custody'])
                    ->default('parent_custody')
                    ->after('leave_with_teacher');
            }
        });

        // Create pivot table for multiple classes per requirement template
        if (!Schema::hasTable('requirement_template_classrooms')) {
            Schema::create('requirement_template_classrooms', function (Blueprint $table) {
                $table->id();
                $table->foreignId('requirement_template_id')
                    ->constrained('requirement_templates')
                    ->onDelete('cascade');
                $table->foreignId('classroom_id')
                    ->constrained('classrooms')
                    ->onDelete('cascade');
                $table->timestamps();

                $table->unique(['requirement_template_id', 'classroom_id'], 'rtc_template_classroom_unique');
            });
        } else {
            // Table exists but unique constraint might be missing or have wrong name - fix it
            $connection = Schema::getConnection();
            $databaseName = $connection->getDatabaseName();
            
            // Check if our desired constraint exists
            $constraints = $connection->select(
                "SELECT constraint_name FROM information_schema.table_constraints 
                 WHERE table_schema = ? AND table_name = 'requirement_template_classrooms' 
                 AND constraint_type = 'UNIQUE' AND constraint_name = 'rtc_template_classroom_unique'",
                [$databaseName]
            );
            
            if (empty($constraints)) {
                // Check for any existing unique constraint on these columns
                $existingConstraints = $connection->select(
                    "SELECT kcu.constraint_name 
                     FROM information_schema.key_column_usage kcu
                     JOIN information_schema.table_constraints tc 
                         ON kcu.constraint_name = tc.constraint_name 
                         AND kcu.table_schema = tc.table_schema
                     WHERE kcu.table_schema = ? 
                     AND kcu.table_name = 'requirement_template_classrooms'
                     AND tc.constraint_type = 'UNIQUE'
                     AND kcu.column_name IN ('requirement_template_id', 'classroom_id')
                     GROUP BY kcu.constraint_name
                     HAVING COUNT(DISTINCT kcu.column_name) = 2",
                    [$databaseName]
                );
                
                // Drop any existing unique constraint on these columns
                foreach ($existingConstraints as $constraint) {
                    $connection->statement("ALTER TABLE `requirement_template_classrooms` DROP INDEX `{$constraint->constraint_name}`");
                }
                
                // Add the constraint with the correct short name using raw SQL
                $connection->statement(
                    "ALTER TABLE `requirement_template_classrooms` 
                     ADD UNIQUE `rtc_template_classroom_unique` (`requirement_template_id`, `classroom_id`)"
                );
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('requirement_template_classrooms')) {
            Schema::dropIfExists('requirement_template_classrooms');
        }

        if (Schema::hasTable('requirement_templates')) {
            Schema::table('requirement_templates', function (Blueprint $table) {
                if (Schema::hasColumn('requirement_templates', 'custody_type')) {
                    $table->dropColumn('custody_type');
                }
            });
        }
    }
};

