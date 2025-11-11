<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_certifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained()->onDelete('cascade');
            $table->string('certification_name');
            $table->string('certifying_body');
            $table->string('certificate_number')->nullable();
            $table->date('issue_date');
            $table->date('expiry_date')->nullable();
            $table->boolean('does_not_expire')->default(false);
            $table->string('certificate_file')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->date('verification_date')->nullable();
            $table->integer('renewal_reminder_days')->default(30); // Days before expiry to remind
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index('staff_id');
            $table->index('expiry_date');
            $table->index('does_not_expire');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_certifications');
    }
};
