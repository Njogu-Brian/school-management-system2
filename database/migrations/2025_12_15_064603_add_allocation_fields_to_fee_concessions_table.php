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
        Schema::table('fee_concessions', function (Blueprint $table) {
            // Link to discount template
            $table->foreignId('discount_template_id')->nullable()->after('id')->constrained('discount_templates')->onDelete('set null');
            
            // Allocation fields (term, year, academic_year_id)
            $table->integer('term')->nullable()->after('student_id');
            $table->integer('year')->nullable()->after('term');
            $table->foreignId('academic_year_id')->nullable()->after('year')->constrained('academic_years')->onDelete('set null');
            
            // Make start_date nullable (will be set during allocation)
            $table->date('start_date')->nullable()->change();
            
            // Add status for approvals
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending')->after('is_active');
            $table->text('rejection_reason')->nullable()->after('approval_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fee_concessions', function (Blueprint $table) {
            $table->dropForeign(['discount_template_id']);
            $table->dropForeign(['academic_year_id']);
            $table->dropColumn(['discount_template_id', 'term', 'year', 'academic_year_id', 'approval_status', 'rejection_reason']);
            $table->date('start_date')->nullable(false)->change();
        });
    }
};
