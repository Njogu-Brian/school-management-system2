<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('email_templates')) {
            Schema::dropIfExists('email_templates');
        }
    }

    public function down(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('title');
            $table->text('message');
            $table->string('attachment')->nullable();
            $table->timestamps();
        });
    }
};
