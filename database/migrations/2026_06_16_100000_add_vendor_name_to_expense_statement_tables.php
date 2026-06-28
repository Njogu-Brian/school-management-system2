<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expense_statement_lines', function (Blueprint $table) {
            if (! Schema::hasColumn('expense_statement_lines', 'vendor_name')) {
                $table->string('vendor_name')->nullable()->after('recipient_name');
            }
        });

        Schema::table('expense_statement_recipient_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('expense_statement_recipient_profiles', 'default_vendor_name')) {
                $table->string('default_vendor_name')->nullable()->after('display_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('expense_statement_lines', function (Blueprint $table) {
            if (Schema::hasColumn('expense_statement_lines', 'vendor_name')) {
                $table->dropColumn('vendor_name');
            }
        });

        Schema::table('expense_statement_recipient_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('expense_statement_recipient_profiles', 'default_vendor_name')) {
                $table->dropColumn('default_vendor_name');
            }
        });
    }
};
