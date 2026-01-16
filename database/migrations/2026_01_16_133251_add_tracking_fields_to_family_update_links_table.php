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
        Schema::table('family_update_links', function (Blueprint $table) {
            $table->integer('click_count')->default(0)->after('last_sent_at');
            $table->timestamp('first_clicked_at')->nullable()->after('click_count');
            $table->timestamp('last_clicked_at')->nullable()->after('first_clicked_at');
            $table->integer('update_count')->default(0)->after('last_clicked_at');
            $table->timestamp('last_updated_at')->nullable()->after('update_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('family_update_links', function (Blueprint $table) {
            $table->dropColumn([
                'click_count',
                'first_clicked_at',
                'last_clicked_at',
                'update_count',
                'last_updated_at',
            ]);
        });
    }
};
