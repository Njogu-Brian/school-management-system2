<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop FK first so we can make votehead_id nullable.
        // NOTE: wrapping Blueprint operations in try/catch does NOT reliably catch SQL errors because the SQL executes
        // after the closure returns. So we check information_schema and drop via raw SQL if present.
        $fk = DB::selectOne(
            "SELECT CONSTRAINT_NAME AS name
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'invoice_items'
               AND COLUMN_NAME = 'votehead_id'
               AND REFERENCED_TABLE_NAME IS NOT NULL
             LIMIT 1"
        );

        if (!empty($fk?->name)) {
            DB::statement("ALTER TABLE `invoice_items` DROP FOREIGN KEY `{$fk->name}`");
        }

        Schema::table('invoice_items', function (Blueprint $table) {
            if (!Schema::hasColumn('invoice_items', 'custom_votehead_name')) {
                $table->string('custom_votehead_name', 255)->nullable()->after('votehead_id');
            }
            // Allow custom invoice lines without creating a votehead record
            $table->unsignedBigInteger('votehead_id')->nullable()->change();
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            // Re-add FK with set null so custom lines remain intact even if a votehead is deleted
            $table->foreign('votehead_id')->references('id')->on('voteheads')->onDelete('set null');
        });
    }

    public function down(): void
    {
        $fk = DB::selectOne(
            "SELECT CONSTRAINT_NAME AS name
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'invoice_items'
               AND COLUMN_NAME = 'votehead_id'
               AND REFERENCED_TABLE_NAME IS NOT NULL
             LIMIT 1"
        );

        if (!empty($fk?->name)) {
            DB::statement("ALTER TABLE `invoice_items` DROP FOREIGN KEY `{$fk->name}`");
        }

        Schema::table('invoice_items', function (Blueprint $table) {
            if (Schema::hasColumn('invoice_items', 'custom_votehead_name')) {
                $table->dropColumn('custom_votehead_name');
            }
            // Revert: votehead_id required again
            $table->unsignedBigInteger('votehead_id')->nullable(false)->change();
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->foreign('votehead_id')->references('id')->on('voteheads')->onDelete('cascade');
        });
    }
};

