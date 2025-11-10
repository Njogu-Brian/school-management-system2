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
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'is_alumni')) {
                $table->boolean('is_alumni')->default(false)->after('archive');
            }
            if (!Schema::hasColumn('students', 'alumni_date')) {
                $table->date('alumni_date')->nullable()->after('is_alumni');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'is_alumni')) {
                $table->dropColumn('is_alumni');
            }
            if (Schema::hasColumn('students', 'alumni_date')) {
                $table->dropColumn('alumni_date');
            }
        });
    }
};
