<?php


// database/migrations/2025_10_31_000000_add_dlr_fields_to_communication_logs.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('communication_logs', function (Blueprint $table) {
            $table->string('provider_id')->nullable()->index();
            $table->string('provider_status')->nullable()->index(); // e.g. queued, sent, delivered, failed, blacklisted, pending
            $table->timestamp('delivered_at')->nullable();
            $table->string('error_code')->nullable();
        });
    }
    public function down() {
        Schema::table('communication_logs', function (Blueprint $table) {
            $table->dropColumn(['provider_id','provider_status','delivered_at','error_code']);
        });
    }
};
