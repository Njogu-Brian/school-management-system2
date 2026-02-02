<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phone_number_normalization_logs', function (Blueprint $table) {
            $table->id();
            $table->string('model_type', 120);
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('field', 80);
            $table->string('old_value', 64)->nullable();
            $table->string('new_value', 64)->nullable();
            $table->string('country_code', 16)->nullable();
            $table->string('source', 64)->default('system');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->index(['model_type', 'model_id']);
            $table->index(['field', 'source']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_number_normalization_logs');
    }
};
