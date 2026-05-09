<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            // Drop FK first so we can make votehead_id nullable
            try {
                $table->dropForeign(['votehead_id']);
            } catch (\Throwable $e) {
                // ignore if missing
            }
        });

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
        Schema::table('invoice_items', function (Blueprint $table) {
            try {
                $table->dropForeign(['votehead_id']);
            } catch (\Throwable $e) {
                // ignore
            }
        });

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

