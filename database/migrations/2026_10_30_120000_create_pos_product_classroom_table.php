<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pos_product_classroom')) {
            return;
        }

        Schema::create('pos_product_classroom', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('classroom_id');
            $table->timestamps();

            $table->unique(['product_id', 'classroom_id']);
            $table->index('classroom_id');
        });

        if (Schema::hasTable('pos_products')) {
            Schema::table('pos_product_classroom', function (Blueprint $table) {
                $table->foreign('product_id')->references('id')->on('pos_products')->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('classrooms')) {
            Schema::table('pos_product_classroom', function (Blueprint $table) {
                $table->foreign('classroom_id')->references('id')->on('classrooms')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_product_classroom');
    }
};
