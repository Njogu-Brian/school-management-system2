<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Students with enrollment_year/enrollment_term set join in that term.
     * Until then they are excluded from attendance, communications "all students", etc.
     * Invoices are created for their enrollment term.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->unsignedInteger('enrollment_year')->nullable()->after('admission_date');
            $table->unsignedTinyInteger('enrollment_term')->nullable()->after('enrollment_year')->comment('1, 2, or 3 - term when student joins');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['enrollment_year', 'enrollment_term']);
        });
    }
};
