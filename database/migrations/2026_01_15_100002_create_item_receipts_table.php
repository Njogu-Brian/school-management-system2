<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_requirement_id')
                ->constrained('student_requirements')
                ->onDelete('cascade');
            $table->foreignId('student_id')
                ->constrained('students')
                ->onDelete('cascade');
            $table->foreignId('classroom_id')
                ->constrained('classrooms')
                ->onDelete('cascade');
            $table->foreignId('received_by')
                ->constrained('users')
                ->onDelete('restrict'); // Who received the item (teacher/admin)
            $table->decimal('quantity_received', 10, 2);
            $table->enum('receipt_status', ['fully_received', 'partially_received', 'not_received'])
                ->default('fully_received');
            $table->text('notes')->nullable();
            $table->timestamp('received_at');
            $table->timestamps();

            $table->index(['student_id', 'classroom_id'], 'ir_student_classroom_idx');
            $table->index(['received_by', 'received_at'], 'ir_received_by_date_idx');
            $table->index('student_requirement_id', 'ir_student_req_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_receipts');
    }
};

