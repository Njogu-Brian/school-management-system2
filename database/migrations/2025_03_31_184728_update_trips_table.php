<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->enum('type', ['Morning', 'Evening'])->after('name');
            $table->string('area_name')->after('type');
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade')->after('area_name');
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->dropColumn('area_name');
            $table->dropForeign(['vehicle_id']);
            $table->dropColumn('vehicle_id');
        });
    }
};
