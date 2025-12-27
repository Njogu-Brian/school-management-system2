<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->unsignedInteger('year');
            $table->unsignedTinyInteger('term');
            $table->foreignId('drop_off_point_id')->nullable()->constrained('drop_off_points')->nullOnDelete();
            $table->string('drop_off_point_name')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('source')->default('manual');
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['student_id', 'year', 'term'], 'unique_transport_fee_per_term');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_fees');
    }
};

