<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'shared_receipt_number')) {
                $table->string('shared_receipt_number')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'shared_receipt_number')) {
                $table->dropIndex(['shared_receipt_number']);
                $table->dropColumn('shared_receipt_number');
            }
        });
    }
};
