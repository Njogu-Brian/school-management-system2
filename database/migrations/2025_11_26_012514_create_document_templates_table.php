<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('document_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('type', [
                'certificate',
                'transcript',
                'id_card',
                'transfer_certificate',
                'character_certificate',
                'diploma',
                'merit_certificate',
                'participation_certificate',
                'custom'
            ])->default('custom');
            $table->text('template_html');
            $table->json('placeholders')->nullable(); // Available placeholders documentation
            $table->json('settings')->nullable(); // Page size, orientation, margins, etc.
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_templates');
    }
};

