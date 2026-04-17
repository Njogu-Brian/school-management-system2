<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parent_info', function (Blueprint $table) {
            if (! Schema::hasColumn('parent_info', 'school_notifications_muted_parent')) {
                $table->string('school_notifications_muted_parent', 16)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('parent_info', function (Blueprint $table) {
            if (Schema::hasColumn('parent_info', 'school_notifications_muted_parent')) {
                $table->dropColumn('school_notifications_muted_parent');
            }
        });
    }
};
