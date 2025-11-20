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
        Schema::table('stream_teacher', function (Blueprint $table) {
            if (!Schema::hasColumn('stream_teacher', 'classroom_id')) {
                $table->foreignId('classroom_id')->nullable()->after('stream_id')->constrained('classrooms')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stream_teacher', function (Blueprint $table) {
            if (Schema::hasColumn('stream_teacher', 'classroom_id')) {
                $table->dropForeign(['classroom_id']);
                $table->dropColumn('classroom_id');
            }
        });
    }
};
