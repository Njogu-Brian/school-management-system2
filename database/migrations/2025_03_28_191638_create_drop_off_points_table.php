<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drop_off_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained()->onDelete('cascade');
            $table->string('point_name'); // e.g., Gate A, Block B
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drop_off_points');
    }
};
