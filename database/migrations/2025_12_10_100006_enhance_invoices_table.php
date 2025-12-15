<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Add FK relationships
            $table->foreignId('academic_year_id')->nullable()->after('student_id')->constrained('academic_years')->onDelete('cascade');
            $table->foreignId('term_id')->nullable()->after('academic_year_id')->constrained('terms')->onDelete('cascade');
            $table->foreignId('family_id')->nullable()->after('student_id')->constrained('families')->onDelete('set null');
            
            // Add payment tracking
            $table->decimal('paid_amount', 10, 2)->default(0)->after('total');
            $table->decimal('balance', 10, 2)->default(0)->after('paid_amount');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('balance');
            
            // Add dates
            $table->date('due_date')->nullable()->after('status');
            $table->date('issued_date')->nullable()->after('due_date');
            
            // Add posting tracking (fee_posting_runs table created in earlier migration)
            // Note: If migration fails, ensure fee_posting_runs migration ran first
            if (Schema::hasTable('fee_posting_runs')) {
                $table->foreignId('posting_run_id')->nullable()->after('reversed_at')->constrained('fee_posting_runs')->onDelete('set null');
            }
            $table->foreignId('posted_by')->nullable()->after('posting_run_id')->constrained('users')->onDelete('set null');
            $table->timestamp('posted_at')->nullable()->after('posted_by');
            
            // Add notes
            $table->text('notes')->nullable()->after('posted_at');
            
            // Note: Data migration required to populate academic_year_id and term_id from year/term integers
            // This should be done in a separate data migration after this structural migration
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['academic_year_id']);
            $table->dropForeign(['term_id']);
            $table->dropForeign(['family_id']);
            $table->dropForeign(['posting_run_id']);
            $table->dropForeign(['posted_by']);
            
            $table->dropColumn([
                'academic_year_id', 'term_id', 'family_id',
                'paid_amount', 'balance', 'discount_amount',
                'due_date', 'issued_date',
                'posting_run_id', 'posted_by', 'posted_at',
                'notes'
            ]);
        });
    }
};

