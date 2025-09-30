<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('report_card_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_card_id')->constrained('report_cards')->cascadeOnDelete();
            $table->string('skill_name');
            $table->enum('rating', ['EE','ME','AE','BE'])->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('report_card_skills');
    }
};
