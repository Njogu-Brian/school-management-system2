<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('communication_templates', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('type', ['email', 'sms']);
            $table->string('subject')->nullable(); // Only for email
            $table->text('content');
            $table->timestamps();
        });

        Schema::create('communication_logs', function (Blueprint $table) {
            $table->id();
            $table->string('recipient_type'); // parent, teacher, student, etc.
            $table->unsignedBigInteger('recipient_id')->nullable();
            $table->string('contact'); // email or phone
            $table->enum('channel', ['email', 'sms']);
            $table->text('message');
            $table->string('status'); // sent / failed
            $table->text('response')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_logs');
        Schema::dropIfExists('communication_templates');
    }
};
