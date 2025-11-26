<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_copies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained('books')->cascadeOnDelete();
            $table->string('copy_number'); // e.g., "Copy 1", "Copy 2"
            $table->string('barcode')->unique()->nullable();
            $table->enum('status', ['available', 'borrowed', 'reserved', 'lost', 'damaged'])->default('available');
            $table->enum('condition', ['new', 'good', 'fair', 'poor'])->default('good');
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_price', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('book_id');
            $table->index('status');
            $table->index('barcode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_copies');
    }
};

