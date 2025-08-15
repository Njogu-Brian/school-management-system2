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
    Schema::create('payments', function (Blueprint $table) {
        $table->id();
        $table->foreignId('student_id')->constrained()->onDelete('cascade');
        $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('set null');
        $table->decimal('amount', 10, 2);
        $table->string('payment_method')->nullable();
        $table->string('reference')->nullable();
        $table->timestamp('payment_date')->useCurrent();
        $table->boolean('reversed')->default(false);
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
