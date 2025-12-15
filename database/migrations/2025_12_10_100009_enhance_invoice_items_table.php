<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            // Add discount and tracking
            $table->decimal('discount_amount', 10, 2)->default(0)->after('amount');
            $table->decimal('original_amount', 10, 2)->nullable()->after('discount_amount'); // For tracking changes
            
            // Add posting tracking (fee_posting_runs table created in earlier migration)
            $postingRunIdColumn = $table->foreignId('posting_run_id')->nullable()->after('source');
            if (Schema::hasTable('fee_posting_runs')) {
                $postingRunIdColumn->constrained('fee_posting_runs')->onDelete('set null');
            }
            $table->timestamp('posted_at')->nullable()->after('posting_run_id');
            
            // Add unique constraint to prevent duplicate voteheads per invoice
            $table->unique(['invoice_id', 'votehead_id'], 'unique_invoice_votehead');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropForeign(['posting_run_id']);
            $table->dropUnique('unique_invoice_votehead');
            
            $table->dropColumn([
                'discount_amount', 'original_amount',
                'posting_run_id', 'posted_at'
            ]);
        });
    }
};

