<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_term_fee_clearances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('term_id')->constrained('terms')->onDelete('cascade');

            $table->enum('status', ['cleared', 'pending'])->index();
            $table->timestamp('computed_at')->nullable()->index();

            $table->decimal('percentage_paid', 6, 2)->nullable();
            $table->decimal('minimum_percentage', 6, 2)->nullable();

            $table->boolean('has_valid_payment_plan')->default(false)->index();
            $table->foreignId('payment_plan_id')->nullable()->constrained('fee_payment_plans')->nullOnDelete();
            $table->string('payment_plan_status')->nullable()->index();

            $table->date('final_clearance_deadline')->nullable()->index();
            $table->string('reason_code')->nullable()->index();
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->unique(['student_id', 'term_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_term_fee_clearances');
    }
};

