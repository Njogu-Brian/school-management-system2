<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fee_payment_plans', function (Blueprint $table) {
            // Add term and academic year references
            $table->foreignId('term_id')->nullable()->after('invoice_id')->constrained('terms')->onDelete('cascade');
            $table->foreignId('academic_year_id')->nullable()->after('term_id')->constrained('academic_years')->onDelete('cascade');
            
            // Expand status enum (using raw SQL as Laravel doesn't support modifying enum easily)
            // We'll drop and recreate the column
        });
        
        // Modify status column to include new statuses
        DB::statement("ALTER TABLE fee_payment_plans MODIFY COLUMN status ENUM('active', 'compliant', 'overdue', 'completed', 'broken', 'cancelled') DEFAULT 'active'");
        
        Schema::table('fee_payment_plans', function (Blueprint $table) {
            // Add final clearance deadline
            $table->date('final_clearance_deadline')->nullable()->after('end_date');
            
            // Add audit fields
            $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fee_payment_plans', function (Blueprint $table) {
            $table->dropForeign(['term_id']);
            $table->dropForeign(['academic_year_id']);
            $table->dropForeign(['updated_by']);
            $table->dropColumn(['term_id', 'academic_year_id', 'final_clearance_deadline', 'updated_by']);
        });
        
        // Revert status enum
        DB::statement("ALTER TABLE fee_payment_plans MODIFY COLUMN status ENUM('active', 'completed', 'cancelled') DEFAULT 'active'");
    }
};
