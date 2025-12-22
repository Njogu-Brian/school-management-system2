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
        Schema::table('fee_posting_runs', function (Blueprint $table) {
            $table->decimal('total_amount_posted', 10, 2)->default(0)->after('items_posted_count');
            $table->integer('total_students_affected')->default(0)->after('total_amount_posted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fee_posting_runs', function (Blueprint $table) {
            $table->dropColumn(['total_amount_posted', 'total_students_affected']);
        });
    }
};
