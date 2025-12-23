<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('staff_metas')) {
            return;
        }

        Schema::create('staff_metas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id');
            $table->string('field_key');
            $table->text('field_value')->nullable();
            $table->timestamps();

            $table->foreign('staff_id')->references('id')->on('staff')->onDelete('cascade');
            $table->index(['staff_id', 'field_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_metas');
    }
};

