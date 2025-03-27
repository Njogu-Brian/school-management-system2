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
        Schema::create('online_admissions', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->date('dob');
            $table->enum('gender', ['Male', 'Female']);
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('guardian_name')->nullable();
            $table->string('father_phone')->nullable();
            $table->string('mother_phone')->nullable();
            $table->string('guardian_phone')->nullable();
            $table->string('father_email')->nullable();
            $table->string('mother_email')->nullable();
            $table->string('guardian_email')->nullable();
            $table->string('father_id_number')->nullable();
            $table->string('mother_id_number')->nullable();
            $table->string('guardian_id_number')->nullable();
            $table->string('nemis_number')->nullable();
            $table->string('knec_assessment_number')->nullable();
            $table->string('passport_photo')->nullable();
            $table->string('birth_certificate')->nullable();
            $table->string('parent_id_card')->nullable();
            $table->enum('form_status', ['Submitted', 'Not Submitted'])->default('Not Submitted');
            $table->enum('payment_status', ['Paid', 'Unpaid'])->default('Unpaid');
            $table->boolean('enrolled')->default(false);
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('online_admissions');
    }
};
