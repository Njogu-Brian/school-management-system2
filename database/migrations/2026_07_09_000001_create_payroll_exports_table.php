<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_period_id')->constrained()->cascadeOnDelete();

            // e.g. imbank_salary_upload, nssf, shif, kra_paye
            $table->string('export_type', 64)->index();
            $table->string('original_filename')->nullable();
            $table->string('storage_disk', 32)->default('local');
            $table->string('storage_path'); // relative to disk
            $table->string('sha256', 64)->nullable()->index();

            $table->json('meta')->nullable(); // mapping/version info, counts, etc.
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['payroll_period_id', 'export_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_exports');
    }
};

