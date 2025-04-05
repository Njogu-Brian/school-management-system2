<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_assignments', function (Blueprint $table) {
            // Add morning and evening trip assignment support
            $table->unsignedBigInteger('morning_trip_id')->nullable()->after('student_id');
            $table->unsignedBigInteger('evening_trip_id')->nullable()->after('morning_trip_id');
            $table->unsignedBigInteger('morning_drop_off_point_id')->nullable()->after('morning_trip_id');
            $table->unsignedBigInteger('evening_drop_off_point_id')->nullable()->after('evening_trip_id');

            // Foreign keys to maintain referential integrity
            $table->foreign('morning_trip_id')->references('id')->on('trips')->onDelete('set null');
            $table->foreign('evening_trip_id')->references('id')->on('trips')->onDelete('set null');
            $table->foreign('morning_drop_off_point_id')->references('id')->on('drop_off_points')->onDelete('set null');
            $table->foreign('evening_drop_off_point_id')->references('id')->on('drop_off_points')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('student_assignments', function (Blueprint $table) {
            $table->dropForeign(['morning_trip_id']);
            $table->dropForeign(['evening_trip_id']);
            $table->dropForeign(['morning_drop_off_point_id']);
            $table->dropForeign(['evening_drop_off_point_id']);

            $table->dropColumn([
                'morning_trip_id',
                'evening_trip_id',
                'morning_drop_off_point_id',
                'evening_drop_off_point_id',
            ]);
        });
    }
};
