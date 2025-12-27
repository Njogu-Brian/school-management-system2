<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_fee_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transport_fee_id')->constrained('transport_fees')->cascadeOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source')->nullable();
            $table->decimal('old_amount', 12, 2)->nullable();
            $table->decimal('new_amount', 12, 2);
            $table->foreignId('old_drop_off_point_id')->nullable()->constrained('drop_off_points')->nullOnDelete();
            $table->foreignId('new_drop_off_point_id')->nullable()->constrained('drop_off_points')->nullOnDelete();
            $table->string('old_drop_off_point_name')->nullable();
            $table->string('new_drop_off_point_name')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_fee_revisions');
    }
};

