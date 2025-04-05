<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drop_off_points', function (Blueprint $table) {
            $table->foreignId('trip_id')->constrained('trips')->onDelete('cascade')->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('drop_off_points', function (Blueprint $table) {
            $table->dropForeign(['trip_id']);
            $table->dropColumn('trip_id');
        });
    }
};
