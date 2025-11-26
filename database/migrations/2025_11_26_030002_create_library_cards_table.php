<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->unique()->constrained('students')->cascadeOnDelete();
            $table->string('card_number')->unique();
            $table->date('issued_date');
            $table->date('expiry_date');
            $table->enum('status', ['active', 'expired', 'suspended', 'lost'])->default('active');
            $table->integer('max_borrow_limit')->default(3);
            $table->integer('current_borrow_count')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('student_id');
            $table->index('card_number');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_cards');
    }
};

