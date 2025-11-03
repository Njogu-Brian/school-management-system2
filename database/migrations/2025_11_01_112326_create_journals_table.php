<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('journals', function (Blueprint $t) {
            $t->id();
            $t->string('journal_number')->unique();
            $t->unsignedBigInteger('student_id')->index();
            $t->unsignedBigInteger('votehead_id')->index();
            $t->integer('year');
            $t->integer('term');
            $t->enum('type', ['credit','debit']); // credit reduces, debit increases
            $t->decimal('amount',10,2);
            $t->date('effective_date')->nullable();
            $t->string('reason');
            $t->unsignedBigInteger('invoice_id')->nullable()->index();
            $t->unsignedBigInteger('invoice_item_id')->nullable()->index();
            $t->json('meta')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('journals');
    }
};
