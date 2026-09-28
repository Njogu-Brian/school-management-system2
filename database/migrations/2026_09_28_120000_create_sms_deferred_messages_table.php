<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_deferred_messages', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 64)->index();
            $table->string('contact', 32)->index();
            $table->text('message');
            $table->string('sender_id')->nullable();
            $table->string('title')->nullable();
            $table->string('scope', 64)->nullable()->index();
            $table->nullableMorphs('recipient');
            $table->json('meta')->nullable();
            $table->string('status', 32)->default('pending')->index(); // pending|sent|expired|cancelled
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['status', 'expires_at']);
            $table->index(['status', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_deferred_messages');
    }
};
