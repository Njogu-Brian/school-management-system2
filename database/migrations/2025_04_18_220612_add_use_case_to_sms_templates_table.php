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
        Schema::table('sms_templates', function (Blueprint $table) {
            $table->string('use_case')->nullable(); // e.g. 'attendance_absent', 'fee_reminder', etc.
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sms_templates', function (Blueprint $table) {
            //
        });
    }
};
