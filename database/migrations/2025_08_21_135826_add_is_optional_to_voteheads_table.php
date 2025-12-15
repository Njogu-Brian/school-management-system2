<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('voteheads', function (Blueprint $table) {
            if (!Schema::hasColumn('voteheads', 'is_optional')) {
                $table->boolean('is_optional')->default(false)->after('is_mandatory');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('voteheads', function (Blueprint $table) {
            if (Schema::hasColumn('voteheads', 'is_optional')) {
                $table->dropColumn('is_optional');
            }
        });
    }
};
