<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operations_facilities', function (Blueprint $table) {
            $table->id();
            $table->date('week_ending');
            $table->enum('campus', ['lower', 'upper'])->nullable();
            $table->string('area');
            $table->enum('status', ['Good', 'Fair', 'Poor'])->nullable();
            $table->text('issue_noted')->nullable();
            $table->text('action_needed')->nullable();
            $table->string('responsible_person')->nullable();
            $table->boolean('resolved')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['week_ending', 'campus']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operations_facilities');
    }
};
