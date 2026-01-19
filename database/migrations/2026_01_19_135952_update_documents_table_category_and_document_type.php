<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Changes category and document_type from ENUM to VARCHAR to support more values.
     */
    public function up(): void
    {
        // Change category from ENUM to VARCHAR
        Schema::table('documents', function (Blueprint $table) {
            // Drop the enum constraint and change to string
            DB::statement("ALTER TABLE `documents` MODIFY COLUMN `category` VARCHAR(100) NOT NULL DEFAULT 'other'");
        });

        // Change document_type from ENUM to VARCHAR
        Schema::table('documents', function (Blueprint $table) {
            // Drop the enum constraint and change to string
            DB::statement("ALTER TABLE `documents` MODIFY COLUMN `document_type` VARCHAR(100) NOT NULL DEFAULT 'other'");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Convert back to ENUM (with original values)
        Schema::table('documents', function (Blueprint $table) {
            DB::statement("ALTER TABLE `documents` MODIFY COLUMN `category` ENUM('student', 'staff', 'academic', 'financial', 'administrative', 'other') NOT NULL DEFAULT 'other'");
        });

        Schema::table('documents', function (Blueprint $table) {
            DB::statement("ALTER TABLE `documents` MODIFY COLUMN `document_type` ENUM('report', 'certificate', 'letter', 'form', 'policy', 'other') NOT NULL DEFAULT 'other'");
        });
    }
};
