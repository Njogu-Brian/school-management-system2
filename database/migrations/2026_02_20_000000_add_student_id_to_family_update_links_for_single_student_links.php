<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('family_update_links', function (Blueprint $table) {
            $table->dropForeign(['family_id']);
        });

        Schema::table('family_update_links', function (Blueprint $table) {
            $table->unsignedBigInteger('family_id')->nullable()->change();
            $table->foreign('family_id')->references('id')->on('families')->onDelete('cascade');
        });

        Schema::table('family_update_links', function (Blueprint $table) {
            $table->foreignId('student_id')->nullable()->after('family_id')->constrained('students')->onDelete('cascade');
        });

        // Allow audits for student-only updates (no family)
        Schema::table('family_update_audits', function (Blueprint $table) {
            $table->dropForeign(['family_id']);
        });
        Schema::table('family_update_audits', function (Blueprint $table) {
            $table->unsignedBigInteger('family_id')->nullable()->change();
            $table->foreign('family_id')->references('id')->on('families')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('family_update_links', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
        });

        Schema::table('family_update_links', function (Blueprint $table) {
            $table->dropForeign(['family_id']);
            $table->unsignedBigInteger('family_id')->nullable(false)->change();
            $table->foreign('family_id')->references('id')->on('families')->onDelete('cascade');
        });

        Schema::table('family_update_audits', function (Blueprint $table) {
            $table->dropForeign(['family_id']);
            $table->unsignedBigInteger('family_id')->nullable(false)->change();
            $table->foreign('family_id')->references('id')->on('families')->onDelete('cascade');
        });
    }
};
