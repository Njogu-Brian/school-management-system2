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
        Schema::create('student_extracurricular_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->enum('activity_type', ['club', 'society', 'sports_team', 'competition', 'leadership_role', 'community_service', 'other'])->default('other');
            $table->string('activity_name');
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('position_role')->nullable(); // e.g., 'Captain', 'Secretary', 'Member'
            $table->string('team_name')->nullable();
            $table->string('competition_name')->nullable();
            $table->string('competition_level')->nullable(); // school, county, national, international
            $table->string('award_achievement')->nullable();
            $table->text('achievement_description')->nullable();
            $table->date('achievement_date')->nullable();
            $table->integer('community_service_hours')->nullable()->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('supervisor_id')->nullable(); // staff member supervising
            $table->timestamps();
            
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('supervisor_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['student_id', 'activity_type']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_extracurricular_activities');
    }
};
