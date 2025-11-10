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
        Schema::table('student_extracurricular_activities', function (Blueprint $table) {
            $table->unsignedBigInteger('votehead_id')->nullable()->after('supervisor_id');
            $table->decimal('fee_amount', 10, 2)->nullable()->after('votehead_id');
            $table->boolean('auto_bill')->default(true)->after('fee_amount');
            $table->integer('billing_term')->nullable()->after('auto_bill');
            $table->integer('billing_year')->nullable()->after('billing_term');
            
            $table->foreign('votehead_id')->references('id')->on('voteheads')->onDelete('set null');
            $table->index('votehead_id', 'idx_activity_votehead');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_extracurricular_activities', function (Blueprint $table) {
            $table->dropForeign(['votehead_id']);
            $table->dropIndex('idx_activity_votehead');
            $table->dropColumn(['votehead_id', 'fee_amount', 'auto_bill', 'billing_term', 'billing_year']);
        });
    }
};
