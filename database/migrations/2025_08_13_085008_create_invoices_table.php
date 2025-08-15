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
    Schema::create('invoices', function (Blueprint $table) {
        $table->id();
        $table->foreignId('student_id')->constrained()->onDelete('cascade');
        $table->year('year');
        $table->tinyInteger('term');
        $table->string('invoice_number')->unique();
        $table->decimal('total', 10, 2)->default(0);
        $table->enum('status', ['unpaid', 'partial', 'paid'])->default('unpaid');
        $table->timestamp('reversed_at')->nullable();
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
