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
        $table->string('first_name');
        $table->string('middle_name')->nullable();
        $table->string('last_name');
        $table->string('phone_number')->nullable();
        $table->string('id_number')->nullable();
        $table->date('date_of_birth')->nullable();
        $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])->nullable();
        $table->enum('gender', ['male', 'female', 'other'])->nullable();
        $table->string('address')->nullable();
        $table->string('emergency_contact_name')->nullable();
        $table->string('emergency_contact_phone')->nullable();
    });
}



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
