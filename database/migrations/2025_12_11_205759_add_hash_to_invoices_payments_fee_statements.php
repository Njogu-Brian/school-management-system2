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
        // Add hash to invoices
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('hash', 64)->unique()->nullable()->after('id');
            $table->index('hash');
        });

        // Add hash to payments
        Schema::table('payments', function (Blueprint $table) {
            $table->string('hash', 64)->unique()->nullable()->after('id');
            $table->index('hash');
        });

        // Add hash to fee_statements if table exists
        if (Schema::hasTable('fee_statements')) {
            Schema::table('fee_statements', function (Blueprint $table) {
                $table->string('hash', 64)->unique()->nullable()->after('id');
                $table->index('hash');
            });
        }

        // Generate hashes for existing records
        $this->generateHashesForExistingRecords();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['hash']);
            $table->dropColumn('hash');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['hash']);
            $table->dropColumn('hash');
        });

        if (Schema::hasTable('fee_statements')) {
            Schema::table('fee_statements', function (Blueprint $table) {
                $table->dropIndex(['hash']);
                $table->dropColumn('hash');
            });
        }
    }

    /**
     * Generate hashes for existing records
     */
    private function generateHashesForExistingRecords(): void
    {
        // Generate hashes for existing invoices
        $invoices = DB::table('invoices')->whereNull('hash')->get();
        foreach ($invoices as $invoice) {
            $hash = $this->generateHash($invoice->id, $invoice->invoice_number ?? 'INV');
            DB::table('invoices')->where('id', $invoice->id)->update(['hash' => $hash]);
        }

        // Generate hashes for existing payments
        $payments = DB::table('payments')->whereNull('hash')->get();
        foreach ($payments as $payment) {
            $hash = $this->generateHash($payment->id, $payment->receipt_number ?? $payment->transaction_code ?? 'PAY');
            DB::table('payments')->where('id', $payment->id)->update(['hash' => $hash]);
        }

        // Generate hashes for existing fee_statements
        if (Schema::hasTable('fee_statements')) {
            $statements = DB::table('fee_statements')->whereNull('hash')->get();
            foreach ($statements as $statement) {
                $hash = $this->generateHash($statement->id, 'STMT');
                DB::table('fee_statements')->where('id', $statement->id)->update(['hash' => $hash]);
            }
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
