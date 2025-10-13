<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('student_behaviours', function (Blueprint $table) {
            // Drop existing foreign key if it exists
            DB::statement("
                ALTER TABLE student_behaviours
                DROP FOREIGN KEY IF EXISTS student_behaviours_recorded_by_foreign
            ");

            // Ensure column is correct type (unsigned)
            DB::statement("
                ALTER TABLE student_behaviours
                MODIFY COLUMN recorded_by BIGINT(20) UNSIGNED NULL
            ");
        });

        // Add correct foreign key
        Schema::table('student_behaviours', function (Blueprint $table) {
            $table->foreign('recorded_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('student_behaviours', function (Blueprint $table) {
            $table->dropForeign(['recorded_by']);
        });
    }
};
