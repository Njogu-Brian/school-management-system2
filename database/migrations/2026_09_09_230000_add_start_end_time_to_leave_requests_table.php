<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('leave_requests', 'start_time')) {
                $table->time('start_time')->nullable()->after('end_date');
            }
            if (! Schema::hasColumn('leave_requests', 'end_time')) {
                $table->time('end_time')->nullable()->after('start_time');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            if (Schema::hasColumn('leave_requests', 'end_time')) {
                $table->dropColumn('end_time');
            }
            if (Schema::hasColumn('leave_requests', 'start_time')) {
                $table->dropColumn('start_time');
            }
        });
    }
};
