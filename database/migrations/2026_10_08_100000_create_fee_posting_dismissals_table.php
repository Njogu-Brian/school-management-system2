<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_posting_dismissals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('votehead_id')->constrained('voteheads')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('term');
            $table->integer('new_amount_cents');
            $table->string('action', 20);
            $table->timestamps();

            $table->unique(
                ['student_id', 'votehead_id', 'year', 'term', 'new_amount_cents'],
                'fee_posting_dismissals_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_posting_dismissals');
    }
};
