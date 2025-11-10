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
        Schema::table('classrooms', function (Blueprint $table) {
            if (!Schema::hasColumn('classrooms', 'next_class_id')) {
                $table->unsignedBigInteger('next_class_id')->nullable()->after('name');
                $table->foreign('next_class_id')->references('id')->on('classrooms')->onDelete('set null');
            }
            if (!Schema::hasColumn('classrooms', 'is_beginner')) {
                $table->boolean('is_beginner')->default(false)->after('next_class_id');
            }
            if (!Schema::hasColumn('classrooms', 'is_alumni')) {
                $table->boolean('is_alumni')->default(false)->after('is_beginner');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('classrooms', function (Blueprint $table) {
            if (Schema::hasColumn('classrooms', 'next_class_id')) {
                $table->dropForeign(['next_class_id']);
                $table->dropColumn('next_class_id');
            }
            if (Schema::hasColumn('classrooms', 'is_beginner')) {
                $table->dropColumn('is_beginner');
            }
            if (Schema::hasColumn('classrooms', 'is_alumni')) {
                $table->dropColumn('is_alumni');
            }
        });
    }
};
