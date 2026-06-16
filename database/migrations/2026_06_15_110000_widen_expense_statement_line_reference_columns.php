<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('expense_statement_lines', function (Blueprint $table) {
            $table->string('account_reference', 255)->nullable()->change();
            $table->string('merchant_reference', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('expense_statement_lines', function (Blueprint $table) {
            $table->string('account_reference', 64)->nullable()->change();
            $table->string('merchant_reference', 64)->nullable()->change();
        });
    }
};
