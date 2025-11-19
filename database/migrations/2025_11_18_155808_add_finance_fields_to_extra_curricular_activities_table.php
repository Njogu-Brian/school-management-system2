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
        Schema::table('extra_curricular_activities', function (Blueprint $table) {
            $table->decimal('fee_amount', 10, 2)->nullable()->after('repeat_weekly');
            $table->foreignId('votehead_id')->nullable()->after('fee_amount')->constrained('voteheads')->onDelete('set null');
            $table->boolean('auto_invoice')->default(false)->after('votehead_id');
            $table->json('student_ids')->nullable()->after('auto_invoice');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('extra_curricular_activities', function (Blueprint $table) {
            $table->dropForeign(['votehead_id']);
            $table->dropColumn(['fee_amount', 'votehead_id', 'auto_invoice', 'student_ids']);
        });
    }
};
