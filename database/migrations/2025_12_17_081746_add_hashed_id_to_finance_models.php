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
        // Add hashed_id to payments (also update public_token length)
        Schema::table('payments', function (Blueprint $table) {
            $table->string('hashed_id', 10)->unique()->nullable()->after('public_token');
        });

        // Add hashed_id to invoices
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('hashed_id', 10)->unique()->nullable()->after('invoice_number');
        });

        // Add hashed_id to credit_notes
        if (Schema::hasTable('credit_notes')) {
            Schema::table('credit_notes', function (Blueprint $table) {
                $table->string('hashed_id', 10)->unique()->nullable()->after('credit_note_number');
            });
        }

        // Add hashed_id to debit_notes
        if (Schema::hasTable('debit_notes')) {
            Schema::table('debit_notes', function (Blueprint $table) {
                $table->string('hashed_id', 10)->unique()->nullable()->after('debit_note_number');
            });
        }

        // Add hashed_id to fee_reminders (if table exists)
        if (Schema::hasTable('fee_reminders')) {
            Schema::table('fee_reminders', function (Blueprint $table) {
                $table->string('hashed_id', 10)->unique()->nullable()->after('id');
            });
        }

        // Add hashed_id to fee_payment_plans (if table exists)
        if (Schema::hasTable('fee_payment_plans')) {
            Schema::table('fee_payment_plans', function (Blueprint $table) {
                $table->string('hashed_id', 10)->unique()->nullable()->after('id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('hashed_id');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('hashed_id');
        });

        if (Schema::hasTable('credit_notes')) {
            Schema::table('credit_notes', function (Blueprint $table) {
                $table->dropColumn('hashed_id');
            });
        }

        if (Schema::hasTable('debit_notes')) {
            Schema::table('debit_notes', function (Blueprint $table) {
                $table->dropColumn('hashed_id');
            });
        }

        if (Schema::hasTable('fee_reminders')) {
            Schema::table('fee_reminders', function (Blueprint $table) {
                $table->dropColumn('hashed_id');
            });
        }

        if (Schema::hasTable('fee_payment_plans')) {
            Schema::table('fee_payment_plans', function (Blueprint $table) {
                $table->dropColumn('hashed_id');
            });
        }
    }
};
