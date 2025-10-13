<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('student_behaviours', function (Blueprint $table) {
            if (Schema::hasColumn('student_behaviours', 'logged_by')) {
                $table->renameColumn('logged_by', 'recorded_by');
            }
        });
    }

    public function down()
    {
        Schema::table('student_behaviours', function (Blueprint $table) {
            if (Schema::hasColumn('student_behaviours', 'recorded_by')) {
                $table->renameColumn('recorded_by', 'logged_by');
            }
        });
    }
};
