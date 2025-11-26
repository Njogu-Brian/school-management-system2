<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mess_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hostel_id')->constrained('hostels')->cascadeOnDelete();
            $table->enum('meal_type', ['breakfast', 'lunch', 'dinner', 'snack'])->default('lunch');
            $table->date('menu_date');
            $table->json('items'); // Array of menu items
            $table->foreignId('prepared_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['hostel_id', 'meal_type', 'menu_date']);
            $table->index('hostel_id');
            $table->index('menu_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mess_menus');
    }
};

