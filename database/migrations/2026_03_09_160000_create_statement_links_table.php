<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('statement_links', function (Blueprint $table) {
            $table->id();
            $table->string('token', 10)->unique();
            $table->string('scope', 20);
            $table->unsignedBigInteger('student_id')->nullable();
            $table->unsignedBigInteger('family_id')->nullable();
            $table->unsignedInteger('period_year');
            $table->string('period_term')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['scope', 'student_id', 'family_id']);
            $table->index(['period_year', 'period_term']);
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statement_links');
    }
};
