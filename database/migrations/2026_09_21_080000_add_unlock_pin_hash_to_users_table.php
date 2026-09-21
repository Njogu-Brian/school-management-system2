<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'unlock_pin_hash')) {
                $table->string('unlock_pin_hash', 255)->nullable()->after('password');
            }
            if (! Schema::hasColumn('users', 'unlock_pin_set_at')) {
                $table->timestamp('unlock_pin_set_at')->nullable()->after('unlock_pin_hash');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'unlock_pin_set_at')) {
                $table->dropColumn('unlock_pin_set_at');
            }
            if (Schema::hasColumn('users', 'unlock_pin_hash')) {
                $table->dropColumn('unlock_pin_hash');
            }
        });
    }
};
