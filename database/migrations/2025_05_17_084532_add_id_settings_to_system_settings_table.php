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
    Schema::table('system_settings', function (Blueprint $table) {
        $table->string('staff_id_prefix')->default('STAFF');
        $table->unsignedBigInteger('staff_id_start')->default(1001);
        $table->string('student_id_prefix')->default('STD');
        $table->unsignedBigInteger('student_id_start')->default(5001);
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            //
        });
    }
};
