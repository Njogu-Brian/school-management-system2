<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Invoices created by fee posting stay unpublished (draft) until staff publish them.
 * Existing invoices are backfilled as published so parents are not suddenly locked out.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'published_at')) {
                $table->timestamp('published_at')->nullable()->after('posted_at');
            }
            if (! Schema::hasColumn('invoices', 'published_by')) {
                $table->foreignId('published_by')
                    ->nullable()
                    ->after('published_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });

        // Preserve current parent visibility for already-issued invoices.
        DB::table('invoices')
            ->whereNull('published_at')
            ->whereNull('reversed_at')
            ->update([
                'published_at' => DB::raw('COALESCE(posted_at, issued_date, created_at)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'published_by')) {
                $table->dropConstrainedForeignId('published_by');
            }
            if (Schema::hasColumn('invoices', 'published_at')) {
                $table->dropColumn('published_at');
            }
        });
    }
};
