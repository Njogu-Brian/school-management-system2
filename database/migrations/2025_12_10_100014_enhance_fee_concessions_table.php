<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_concessions', function (Blueprint $table) {
            // Add discount type and scope
            if (!Schema::hasColumn('fee_concessions', 'discount_type')) {
                $table->enum('discount_type', ['sibling', 'referral', 'early_repayment', 'transport', 'manual', 'other'])->nullable()->after('type');
            }
            
            if (!Schema::hasColumn('fee_concessions', 'frequency')) {
                $table->enum('frequency', ['termly', 'yearly', 'once', 'manual'])->default('manual')->after('discount_type');
            }
            
            // Add scope
            if (!Schema::hasColumn('fee_concessions', 'scope')) {
                $table->enum('scope', ['votehead', 'invoice', 'student', 'family'])->default('votehead')->after('frequency');
            }
            
            // Add family/invoice links
            if (!Schema::hasColumn('fee_concessions', 'family_id')) {
                $table->foreignId('family_id')->nullable()->after('student_id')->constrained('families')->onDelete('cascade');
            }
            
            if (!Schema::hasColumn('fee_concessions', 'invoice_id')) {
                $table->foreignId('invoice_id')->nullable()->after('votehead_id')->constrained('invoices')->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fee_concessions', function (Blueprint $table) {
            if (Schema::hasColumn('fee_concessions', 'family_id')) {
                $table->dropForeign(['family_id']);
                $table->dropColumn('family_id');
            }
            if (Schema::hasColumn('fee_concessions', 'invoice_id')) {
                $table->dropForeign(['invoice_id']);
                $table->dropColumn('invoice_id');
            }
            
            $columns = ['discount_type', 'frequency', 'scope'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('fee_concessions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

