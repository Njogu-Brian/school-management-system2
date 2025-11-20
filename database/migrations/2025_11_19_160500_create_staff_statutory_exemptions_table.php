<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('staff_statutory_exemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->string('deduction_code'); // e.g. nssf, nhif, paye
            $table->timestamps();

            $table->unique(['staff_id', 'deduction_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_statutory_exemptions');
    }
};

