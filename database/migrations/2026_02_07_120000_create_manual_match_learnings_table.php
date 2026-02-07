<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_match_learnings', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_type', 20); // 'bank' or 'c2b'
            $table->string('reference_text', 255)->nullable(); // reference_number or trans_id
            $table->text('description_text')->nullable(); // description or bill_ref_number / payer text
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('match_reason', 500)->nullable();
            $table->timestamps();

            $table->index(['transaction_type', 'reference_text']);
            $table->index(['transaction_type', 'student_id']);
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_match_learnings');
    }
};
