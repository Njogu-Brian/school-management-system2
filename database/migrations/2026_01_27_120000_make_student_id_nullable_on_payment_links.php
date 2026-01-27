<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Allows one payment link per family (student_id null) like profile-update links.
     */
    public function up(): void
    {
        Schema::table('payment_links', function (Blueprint $table) {
            $table->foreignId('student_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_links', function (Blueprint $table) {
            $table->foreignId('student_id')->nullable(false)->change();
        });
    }
};
