<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('optional_fee_imports', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('year');
            $table->unsignedTinyInteger('term');
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->foreignId('term_id')->nullable()->constrained('terms')->nullOnDelete();
            $table->unsignedInteger('fees_imported_count')->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('imported_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->boolean('is_reversed')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['year', 'term', 'is_reversed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('optional_fee_imports');
    }
};

