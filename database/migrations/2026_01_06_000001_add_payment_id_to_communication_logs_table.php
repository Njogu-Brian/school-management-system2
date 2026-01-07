<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('communication_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('communication_logs', 'payment_id')) {
                $table->foreignId('payment_id')->nullable()->after('recipient_id')->constrained('payments')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('communication_logs', function (Blueprint $table) {
            if (Schema::hasColumn('communication_logs', 'payment_id')) {
                $table->dropForeign(['payment_id']);
                $table->dropColumn('payment_id');
            }
        });
    }
};

