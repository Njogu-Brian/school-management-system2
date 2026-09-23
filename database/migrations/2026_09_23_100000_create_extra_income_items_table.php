<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extra_income_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('kind', 32)->default('other');
            $table->foreignId('classroom_id')->nullable()->constrained('classrooms')->nullOnDelete();
            $table->foreignId('votehead_id')->nullable()->constrained('voteheads')->nullOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('term');
            $table->decimal('amount', 12, 2);
            $table->date('event_date')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['year', 'term', 'is_active']);
        });

        Schema::create('activity_fee_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('extra_income_item_id')->constrained('extra_income_items')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->unsignedBigInteger('bank_statement_transaction_id')->nullable();
            $table->unsignedBigInteger('mpesa_c2b_transaction_id')->nullable();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->foreignId('invoice_item_id')->nullable()->constrained('invoice_items')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('status', 32)->default('allocated');
            $table->text('notes')->nullable();
            $table->foreignId('allocated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('allocated_at')->nullable();
            $table->timestamps();

            $table->index('bank_statement_transaction_id', 'act_fee_alloc_bank_idx');
            $table->index('mpesa_c2b_transaction_id', 'act_fee_alloc_c2b_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_fee_allocations');
        Schema::dropIfExists('extra_income_items');
    }
};
