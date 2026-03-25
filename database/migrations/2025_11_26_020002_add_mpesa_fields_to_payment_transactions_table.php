<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Same as 2025_01_08_000003 when that ran before `payment_transactions` existed.
 * Runs immediately after `create_payment_transactions` + `payment_webhooks`.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payment_transactions')) {
            return;
        }
        if (! Schema::hasTable('payment_links')) {
            return;
        }

        Schema::table('payment_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('payment_transactions', 'mpesa_receipt_number')) {
                $table->string('mpesa_receipt_number')->nullable()->after('transaction_id');
            }
            if (! Schema::hasColumn('payment_transactions', 'phone_number')) {
                $table->string('phone_number')->nullable()->after('mpesa_receipt_number');
            }
            if (! Schema::hasColumn('payment_transactions', 'account_reference')) {
                $table->string('account_reference')->nullable()->after('phone_number');
            }
            if (! Schema::hasColumn('payment_transactions', 'mpesa_transaction_date')) {
                $table->timestamp('mpesa_transaction_date')->nullable()->after('paid_at');
            }
            if (! Schema::hasColumn('payment_transactions', 'initiated_by')) {
                $table->foreignId('initiated_by')->nullable()->after('student_id')
                    ->constrained('users')->onDelete('set null')
                    ->comment('Admin who initiated payment for admin-prompted STK push');
            }
            if (! Schema::hasColumn('payment_transactions', 'admin_notes')) {
                $table->text('admin_notes')->nullable()->after('failure_reason');
            }
            if (! Schema::hasColumn('payment_transactions', 'payment_link_id')) {
                $table->foreignId('payment_link_id')->nullable()->after('invoice_id')
                    ->constrained('payment_links')->onDelete('set null');
            }
        });

        foreach (['mpesa_receipt_number', 'phone_number', 'account_reference'] as $col) {
            if (! Schema::hasColumn('payment_transactions', $col)) {
                continue;
            }
            try {
                DB::statement('ALTER TABLE `payment_transactions` ADD INDEX `payment_transactions_'.$col.'_index` (`'.$col.'`)');
            } catch (\Throwable) {
                //
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('payment_transactions')) {
            return;
        }

        Schema::table('payment_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('payment_transactions', 'payment_link_id')) {
                $table->dropForeign(['payment_link_id']);
            }
            if (Schema::hasColumn('payment_transactions', 'initiated_by')) {
                $table->dropForeign(['initiated_by']);
            }
        });

        Schema::table('payment_transactions', function (Blueprint $table) {
            $cols = [];
            foreach (
                [
                    'mpesa_receipt_number',
                    'phone_number',
                    'account_reference',
                    'mpesa_transaction_date',
                    'initiated_by',
                    'admin_notes',
                    'payment_link_id',
                ] as $c
            ) {
                if (Schema::hasColumn('payment_transactions', $c)) {
                    $cols[] = $c;
                }
            }
            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });
    }
};
