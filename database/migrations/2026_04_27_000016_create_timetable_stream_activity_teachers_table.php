<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timetable_stream_activity_teachers', function (Blueprint $table) {
            $table->id();
            // Use explicit short FK names to satisfy MySQL 64-char limit.
            $table->unsignedBigInteger('activity_requirement_id');
            $table->foreign('activity_requirement_id', 'tsat_act_req_fk')
                ->references('id')
                ->on('timetable_stream_activity_requirements')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('staff_id');
            $table->foreign('staff_id', 'tsat_staff_fk')
                ->references('id')
                ->on('staff')
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('periods_per_week')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['activity_requirement_id', 'staff_id'], 'stream_activity_teacher_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_stream_activity_teachers');
    }
};

