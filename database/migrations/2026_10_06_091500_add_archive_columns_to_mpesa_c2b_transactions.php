<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mpesa_c2b_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('mpesa_c2b_transactions', 'is_archived')) {
                $table->boolean('is_archived')->default(false)->after('is_duplicate');
            }
            if (! Schema::hasColumn('mpesa_c2b_transactions', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('is_archived');
            }
            if (! Schema::hasColumn('mpesa_c2b_transactions', 'archived_by')) {
                $table->unsignedBigInteger('archived_by')->nullable()->after('archived_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mpesa_c2b_transactions', function (Blueprint $table) {
            foreach (['archived_by', 'archived_at', 'is_archived'] as $col) {
                if (Schema::hasColumn('mpesa_c2b_transactions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
