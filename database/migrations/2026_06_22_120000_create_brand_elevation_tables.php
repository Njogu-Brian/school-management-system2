<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_brand_items', function (Blueprint $table) {
            $table->id();
            $table->string('block_type', 64)->index();
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->text('body')->nullable();
            $table->string('image_url')->nullable();
            $table->string('link_url')->nullable();
            $table->string('video_url')->nullable();
            $table->json('settings')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('media_quality_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')->constrained('media_library')->cascadeOnDelete();
            $table->boolean('approved')->default(false);
            $table->boolean('hero_ready')->default(false);
            $table->boolean('homepage_ready')->default(false);
            $table->unsignedTinyInteger('priority')->default(0);
            $table->timestamps();

            $table->unique('media_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_quality_flags');
        Schema::dropIfExists('website_brand_items');
    }
};
