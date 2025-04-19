<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('communication_logs', function (Blueprint $table) {
            // Only add columns that don't already exist
            if (!Schema::hasColumn('communication_logs', 'target')) {
                $table->string('target')->nullable();
            }
            if (!Schema::hasColumn('communication_logs', 'recipient')) {
                $table->string('recipient')->nullable();
            }
            if (!Schema::hasColumn('communication_logs', 'type')) {
                $table->enum('type', ['email', 'sms'])->default('email');
            }
            if (!Schema::hasColumn('communication_logs', 'sent_at')) {
                $table->timestamp('sent_at')->nullable();
            }
        });
    }
    



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('communication_logs', function (Blueprint $table) {
            //
        });
    }
};
