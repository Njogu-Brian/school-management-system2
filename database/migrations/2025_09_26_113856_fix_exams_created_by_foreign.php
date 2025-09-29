<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
{
    DB::statement('ALTER TABLE exams DROP FOREIGN KEY IF EXISTS exams_created_by_foreign');

    Schema::table('exams', function (Blueprint $table) {
        $table->foreign('created_by')
              ->references('id')
              ->on('users')
              ->nullOnDelete();
    });
}


    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
        });
    }
};
