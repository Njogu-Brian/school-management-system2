<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('optional_fees', function (Blueprint $table) {
            // Add FK relationships
            $table->foreignId('academic_year_id')->nullable()->after('student_id')->constrained('academic_years')->onDelete('cascade');
            
            // Add audit fields
            $table->foreignId('assigned_by')->nullable()->after('status')->constrained('users')->onDelete('set null');
            $table->timestamp('assigned_at')->nullable()->after('assigned_by');
            
            // Add unique constraint
            $table->unique(['student_id', 'votehead_id', 'term', 'year'], 'unique_optional_fee_assignment');
            
            // Migrate year to academic_year_id if possible
        });
    }

    public function down(): void
    {
        Schema::table('optional_fees', function (Blueprint $table) {
            $table->dropForeign(['academic_year_id']);
            $table->dropForeign(['assigned_by']);
            $table->dropUnique('unique_optional_fee_assignment');
            
            $table->dropColumn(['academic_year_id', 'assigned_by', 'assigned_at']);
        });
    }
};

