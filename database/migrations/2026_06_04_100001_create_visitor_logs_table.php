<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitor_logs', function (Blueprint $table) {
            $table->id();
            $table->string('visitor_name');
            $table->string('phone')->nullable();
            $table->string('id_number')->nullable();
            $table->string('organization')->nullable();
            $table->string('purpose')->nullable();
            $table->string('host_name')->nullable();
            $table->foreignId('host_staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('badge_number')->nullable();
            $table->timestamp('checked_in_at');
            $table->timestamp('checked_out_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('checked_in_at');
            $table->index('checked_out_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitor_logs');
    }
};
