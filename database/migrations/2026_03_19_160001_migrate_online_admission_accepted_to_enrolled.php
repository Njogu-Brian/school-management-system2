<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Merge "accepted" status into "enrolled" for consistency.
     */
    public function up(): void
    {
        DB::table('online_admissions')
            ->where('application_status', 'accepted')
            ->update(['application_status' => 'enrolled']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('online_admissions')
            ->where('application_status', 'enrolled')
            ->where('enrolled', true)
            ->update(['application_status' => 'accepted']);
    }
};
