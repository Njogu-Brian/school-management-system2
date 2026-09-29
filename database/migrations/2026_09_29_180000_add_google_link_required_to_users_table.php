<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'google_link_required')) {
                $table->boolean('google_link_required')->default(false)->after('google_email');
            }
        });

        if (Schema::hasTable('settings') && Setting::get('google_link_prompt_mode') === null) {
            Setting::set('google_link_prompt_mode', 'all');
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'google_link_required')) {
                $table->dropColumn('google_link_required');
            }
        });
    }
};
