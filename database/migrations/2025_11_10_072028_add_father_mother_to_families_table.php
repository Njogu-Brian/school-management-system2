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
        Schema::table('families', function (Blueprint $table) {
            $table->string('father_name')->nullable()->after('guardian_name');
            $table->string('mother_name')->nullable()->after('father_name');
            $table->string('father_phone')->nullable()->after('phone');
            $table->string('mother_phone')->nullable()->after('father_phone');
            $table->string('father_email')->nullable()->after('email');
            $table->string('mother_email')->nullable()->after('father_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('families', function (Blueprint $table) {
            $table->dropColumn(['father_name', 'mother_name', 'father_phone', 'mother_phone', 'father_email', 'mother_email']);
        });
    }
};
