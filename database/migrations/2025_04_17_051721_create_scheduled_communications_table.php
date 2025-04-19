<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('scheduled_communications', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['email', 'sms']);
            $table->unsignedBigInteger('template_id');
            $table->string('target'); // students, teachers, staff, parents
            $table->unsignedBigInteger('classroom_id')->nullable();
            $table->timestamp('send_at');
            $table->enum('status', ['pending', 'sent'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_communications');
    }
};
