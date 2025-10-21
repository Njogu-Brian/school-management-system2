<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('student_behaviours', function (Blueprint $table) {
            if (!Schema::hasColumn('student_behaviours', 'notes')) {
                // Remove the 'after' clause to avoid referencing a non-existent column
                $table->text('notes')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('student_behaviours', function (Blueprint $table) {
            if (Schema::hasColumn('student_behaviours', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};
