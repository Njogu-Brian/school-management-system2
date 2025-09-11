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
        Schema::table('staff', function (Blueprint $table) {
            // Add new foreign key columns
            $table->unsignedBigInteger('role_id')->nullable()->after('job_title');
            $table->unsignedBigInteger('department_id')->nullable()->after('role_id');
            $table->unsignedBigInteger('job_title_id')->nullable()->after('department_id');

            // (Optional) add constraints if you want strict relations
            // $table->foreign('role_id')->references('id')->on('staff_roles')->nullOnDelete();
            // $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
            // $table->foreign('job_title_id')->references('id')->on('job_titles')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn(['role_id','department_id','job_title_id']);
        });
    }

};
