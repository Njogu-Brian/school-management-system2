<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hostel_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hostel_id')->constrained('hostels')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->enum('fee_type', ['accommodation', 'mess', 'utilities', 'other'])->default('accommodation');
            $table->decimal('amount', 10, 2);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('hostel_id');
            $table->index(['academic_year_id', 'term_id']);
            $table->index('fee_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hostel_fees');
    }
};

