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
            $table->string('hash', 64)->unique()->nullable()->after('id');
            $table->index('hash');
        });

        // Generate hashes for existing records
        $this->generateHashesForExistingRecords();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fee_posting_runs', function (Blueprint $table) {
            $table->dropIndex(['hash']);
            $table->dropColumn('hash');
        });
    }

    /**
     * Generate hashes for existing records
     */
    private function generateHashesForExistingRecords(): void
    {
        $runs = DB::table('fee_posting_runs')->whereNull('hash')->get();
        foreach ($runs as $run) {
            $hash = $this->generateHash($run->id, 'RUN');
            DB::table('fee_posting_runs')->where('id', $run->id)->update(['hash' => $hash]);
        }
    }

    /**
     * Generate a unique hash
     */
    private function generateHash(int $id, string $prefix): string
    {
        $secret = config('app.key');
        $data = $id . $prefix . $secret . time();
        return hash('sha256', $data);
    }
};
