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
                $table->text('notes')->nullable()->after('academic_year_id');
            }
        });
    }

    public function down()
    {
        Schema::table('student_behaviours', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }

};
