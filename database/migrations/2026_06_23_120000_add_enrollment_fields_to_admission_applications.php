<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admission_applications', function (Blueprint $table) {
            $table->foreignId('preferred_classroom_id')->nullable()->after('desired_class')->constrained('classrooms')->nullOnDelete();
            $table->unsignedSmallInteger('enrollment_year')->nullable()->after('preferred_classroom_id');
            $table->unsignedTinyInteger('enrollment_term')->nullable()->after('enrollment_year');
            $table->timestamp('submitted_at')->nullable()->after('current_step');
        });
    }

    public function down(): void
    {
        Schema::table('admission_applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('preferred_classroom_id');
            $table->dropColumn(['enrollment_year', 'enrollment_term', 'submitted_at']);
        });
    }
};
