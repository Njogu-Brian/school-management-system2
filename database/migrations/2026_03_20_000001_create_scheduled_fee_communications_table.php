<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_fee_communications', function (Blueprint $table) {
            $table->id();
            $table->string('target'); // one_parent, specific_students, class, all
            $table->unsignedBigInteger('student_id')->nullable();
            $table->json('selected_student_ids')->nullable();
            $table->json('classroom_ids')->nullable();
            $table->string('filter_type')->default('all'); // outstanding_fees, upcoming_invoices, swimming_balance, all
            $table->decimal('balance_min', 12, 2)->nullable();
            $table->decimal('balance_max', 12, 2)->nullable();
            $table->decimal('balance_percent_min', 5, 2)->nullable();
            $table->decimal('balance_percent_max', 5, 2)->nullable();
            $table->json('channels'); // ["sms","email","whatsapp"]
            $table->unsignedBigInteger('template_id')->nullable();
            $table->text('custom_message')->nullable();
            $table->timestamp('send_at');
            $table->enum('status', ['pending', 'sent', 'cancelled'])->default('pending');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_fee_communications');
    }
};
