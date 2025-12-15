<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voteheads', function (Blueprint $table) {
            // Add preferred_term for once_annually fees
            // This indicates which term the fee should be charged (e.g., textbook fee in term 1)
            if (!Schema::hasColumn('voteheads', 'preferred_term')) {
                $table->integer('preferred_term')
                    ->nullable()
                    ->after('charge_type')
                    ->comment('Preferred term for once_annually fees (1, 2, or 3)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('voteheads', function (Blueprint $table) {
            if (Schema::hasColumn('voteheads', 'preferred_term')) {
                $table->dropColumn('preferred_term');
            }
        });
    }
};

