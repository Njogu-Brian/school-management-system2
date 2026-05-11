<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voteheads', function (Blueprint $table) {
            if (!Schema::hasColumn('voteheads', 'is_activity_fee')) {
                $table->boolean('is_activity_fee')->default(false)->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('voteheads', function (Blueprint $table) {
            if (Schema::hasColumn('voteheads', 'is_activity_fee')) {
                $table->dropColumn('is_activity_fee');
            }
        });
    }
};
