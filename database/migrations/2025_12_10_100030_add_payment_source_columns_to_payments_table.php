<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the same columns as 2025_01_08_000002 when that migration ran too early
 * (before `payments` / `payment_transactions` existed). Runs after `enhance_payments`.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }
        if (! Schema::hasColumn('payments', 'payment_method_id')) {
            return;
        }
        if (! Schema::hasTable('payment_links')) {
            return;
        }
        if (! Schema::hasTable('payment_transactions')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'payment_channel')) {
                $table->string('payment_channel')->nullable()->after('payment_method_id')
                    ->comment('Source: stk_push, payment_link, paybill_manual, admin_entry, mobile_app, online_portal');
            }
            if (! Schema::hasColumn('payments', 'mpesa_receipt_number')) {
                $table->string('mpesa_receipt_number')->nullable()->after('payment_channel');
            }
            if (! Schema::hasColumn('payments', 'mpesa_phone_number')) {
                $table->string('mpesa_phone_number')->nullable()->after('mpesa_receipt_number');
            }
            if (! Schema::hasColumn('payments', 'payment_link_id')) {
                $table->foreignId('payment_link_id')->nullable()->after('invoice_id')
                    ->constrained('payment_links')->onDelete('set null');
            }
            if (! Schema::hasColumn('payments', 'payment_transaction_id')) {
                $table->foreignId('payment_transaction_id')->nullable()->after('payment_link_id')
                    ->constrained('payment_transactions')->onDelete('set null');
            }
        });

        foreach (['payment_channel', 'mpesa_receipt_number'] as $col) {
            if (! Schema::hasColumn('payments', $col)) {
                continue;
            }
            try {
                DB::statement('ALTER TABLE `payments` ADD INDEX `payments_'.$col.'_index` (`'.$col.'`)');
            } catch (\Throwable) {
                //
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'payment_transaction_id')) {
                $table->dropForeign(['payment_transaction_id']);
            }
            if (Schema::hasColumn('payments', 'payment_link_id')) {
                $table->dropForeign(['payment_link_id']);
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            $cols = [];
            foreach (['payment_channel', 'mpesa_receipt_number', 'mpesa_phone_number', 'payment_link_id', 'payment_transaction_id'] as $c) {
                if (Schema::hasColumn('payments', $c)) {
                    $cols[] = $c;
                }
            }
            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });
    }
};
