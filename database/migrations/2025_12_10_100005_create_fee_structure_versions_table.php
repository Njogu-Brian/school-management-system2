<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_structure_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_structure_id')->constrained('fee_structures')->onDelete('cascade');
            $table->integer('version_number');
            $table->json('structure_snapshot'); // Full snapshot of structure and charges
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('change_notes')->nullable();
            $table->timestamps();

            $table->unique(['fee_structure_id', 'version_number']);
            $table->index('fee_structure_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_structure_versions');
    }
};

