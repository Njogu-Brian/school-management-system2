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
    Schema::create('communication_placeholders', function (Blueprint $table) {
        $table->id();
        $table->string('key')->unique(); // e.g. principal_name  (used as {principal_name})
        $table->text('value')->nullable();
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('communication_placeholders');
    }
};
