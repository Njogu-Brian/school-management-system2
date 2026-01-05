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
        Schema::table('trips', function (Blueprint $table) {
            // Make route_id nullable (remove hard dependency but keep for backward compatibility)
            // Note: This change is intentional per requirements - trips no longer strictly depend on routes
            $table->unsignedBigInteger('route_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            // Note: Cannot safely revert to NOT NULL without data loss
            // This migration is intentionally non-reversible
        });
    }
};
