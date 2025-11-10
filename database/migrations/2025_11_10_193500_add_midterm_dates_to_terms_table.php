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
        Schema::table('terms', function (Blueprint $table) {
            if (!Schema::hasColumn('terms', 'midterm_start_date')) {
                $table->date('midterm_start_date')->nullable()->after('closing_date');
            }
            if (!Schema::hasColumn('terms', 'midterm_end_date')) {
                $table->date('midterm_end_date')->nullable()->after('midterm_start_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('terms', function (Blueprint $table) {
            $table->dropColumn(['midterm_start_date', 'midterm_end_date']);
        });
    }
};

