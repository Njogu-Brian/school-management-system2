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
        Schema::create('regional_settings', function (Blueprint $table) {
            $table->id();
            $table->string('timezone')->default('Africa/Nairobi');
            $table->string('currency')->default('KES');
            $table->string('currency_symbol')->default('KSh');
            $table->string('date_format')->default('d/m/Y');
            $table->timestamps();
        });        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regional_settings');
    }
};
