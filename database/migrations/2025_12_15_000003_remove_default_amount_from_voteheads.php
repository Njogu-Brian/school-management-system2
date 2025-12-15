<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voteheads', function (Blueprint $table) {
            if (Schema::hasColumn('voteheads', 'default_amount')) {
                $table->dropColumn('default_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('voteheads', function (Blueprint $table) {
            if (!Schema::hasColumn('voteheads', 'default_amount')) {
                $table->decimal('default_amount', 10, 2)->nullable()->after('charge_type');
            }
        });
    }
};

