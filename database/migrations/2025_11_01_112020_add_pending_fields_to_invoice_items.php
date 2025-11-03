<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('invoice_items', function (Blueprint $t) {
            if (!Schema::hasColumn('invoice_items', 'status')) {
                $t->enum('status', ['pending','active'])->default('active')->after('amount');
            }
            if (!Schema::hasColumn('invoice_items', 'effective_date')) {
                $t->date('effective_date')->nullable()->after('status');
            }
            if (!Schema::hasColumn('invoice_items', 'votehead_id')) {
                $t->unsignedBigInteger('votehead_id')->after('invoice_id')->index();
            }
            if (!Schema::hasColumn('invoice_items', 'source')) {
                $t->string('source')->nullable()->after('effective_date'); // structure|optional|transport|manual|journal
            }
        });
    }
    public function down(): void {
        Schema::table('invoice_items', function (Blueprint $t) {
            if (Schema::hasColumn('invoice_items', 'source')) $t->dropColumn('source');
            if (Schema::hasColumn('invoice_items', 'effective_date')) $t->dropColumn('effective_date');
            if (Schema::hasColumn('invoice_items', 'status')) $t->dropColumn('status');
            // don't drop votehead_id (core FK)
        });
    }
};
