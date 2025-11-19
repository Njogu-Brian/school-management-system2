<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop dependent tables first
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('diary_read_receipts');
        Schema::dropIfExists('diary_messages');
        Schema::dropIfExists('diaries');

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // No-op. Legacy tables intentionally removed.
    }
};

