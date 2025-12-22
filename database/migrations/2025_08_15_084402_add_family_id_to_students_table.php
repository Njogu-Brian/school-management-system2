<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('students', function (Blueprint $table) {
        if (!Schema::hasColumn('students', 'family_id')) {
            $table->unsignedBigInteger('family_id')->nullable();
        } else {
            $table->unsignedBigInteger('family_id')->nullable()->change();
        }
    });
}

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['family_id']);
        });
    }
};

