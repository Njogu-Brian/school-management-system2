<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::create('terms', function (Blueprint $table) {
        $table->id();
        $table->string('name'); // Term 1, 2, 3
        $table->foreignId('academic_year_id')->constrained()->onDelete('cascade');
        $table->boolean('is_current')->default(false);
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('terms');
    }
};
