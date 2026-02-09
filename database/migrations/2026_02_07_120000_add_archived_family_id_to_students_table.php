<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('students', 'archived_family_id')) {
            return;
        }
        Schema::table('students', function (Blueprint $table) {
            $table->unsignedBigInteger('archived_family_id')->nullable()->after('family_id');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'archived_family_id')) {
                $table->dropColumn('archived_family_id');
            }
        });
    }
};
