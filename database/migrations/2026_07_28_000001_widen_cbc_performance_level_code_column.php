<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cbc_performance_levels', function (Blueprint $table) {
            $table->string('code', 5)->change();
        });
    }

    public function down(): void
    {
        Schema::table('cbc_performance_levels', function (Blueprint $table) {
            $table->string('code', 1)->change();
        });
    }
};
