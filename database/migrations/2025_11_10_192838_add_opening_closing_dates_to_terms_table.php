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
            if (!Schema::hasColumn('terms', 'opening_date')) {
                $table->date('opening_date')->nullable()->after('is_current');
            }
            if (!Schema::hasColumn('terms', 'closing_date')) {
                $table->date('closing_date')->nullable()->after('opening_date');
            }
            if (!Schema::hasColumn('terms', 'expected_school_days')) {
                $table->integer('expected_school_days')->nullable()->after('closing_date');
            }
            if (!Schema::hasColumn('terms', 'notes')) {
                $table->text('notes')->nullable()->after('expected_school_days');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('terms', function (Blueprint $table) {
            $table->dropColumn(['opening_date', 'closing_date', 'expected_school_days', 'notes']);
        });
    }
};
