<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fee_posting_runs', function (Blueprint $table) {
            $table->boolean('is_active')->default(false)->after('status');
            $table->index('is_active');
        });

        // Set the latest completed, non-reversed run as active
        $latestActive = DB::table('fee_posting_runs')
            ->where('status', 'completed')
            ->where('run_type', 'commit')
            ->whereNull('reversed_at')
            ->orderBy('posted_at', 'desc')
            ->first();

        if ($latestActive) {
            DB::table('fee_posting_runs')
                ->where('id', $latestActive->id)
                ->update(['is_active' => true]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fee_posting_runs', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropColumn('is_active');
        });
    }
};
