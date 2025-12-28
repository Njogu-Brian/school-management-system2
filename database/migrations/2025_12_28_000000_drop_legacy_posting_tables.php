<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('legacy_ledger_postings');
        Schema::dropIfExists('legacy_votehead_mappings');
    }

    public function down(): void
    {
        // No-op: tables were removed intentionally.
    }
};

