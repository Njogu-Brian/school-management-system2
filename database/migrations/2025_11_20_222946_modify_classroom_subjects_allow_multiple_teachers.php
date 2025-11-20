<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('classroom_subjects', function (Blueprint $table) {
            // Drop the existing unique constraint that doesn't include staff_id
            $table->dropUnique('cls_sub_unique');
            
            // Add a new unique constraint that includes staff_id
            // This allows multiple teachers for the same subject-classroom combination
            // Each teacher gets their own record
            $table->unique(
                ['classroom_id', 'stream_id', 'subject_id', 'staff_id', 'academic_year_id', 'term_id'],
                'cls_sub_staff_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('classroom_subjects', function (Blueprint $table) {
            // Drop the new constraint
            $table->dropUnique('cls_sub_staff_unique');
            
            // Restore the original constraint
            $table->unique(
                ['classroom_id', 'stream_id', 'subject_id', 'academic_year_id', 'term_id'],
                'cls_sub_unique'
            );
        });
    }
};
