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
        Schema::rename('staff_roles', 'staff_categories');
        Schema::table('staff', function (Blueprint $table) {
            $table->renameColumn('role_id', 'staff_category_id');
        });
    }

    public function down()
    {
        Schema::rename('staff_categories', 'staff_roles');
        Schema::table('staff', function (Blueprint $table) {
            $table->renameColumn('staff_category_id', 'role_id');
        });
    }

};
