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
        $table->string('scope')->nullable(); // group, class, section, individual
        $table->unsignedBigInteger('classroom_id')->nullable();
        $table->unsignedBigInteger('stream_id')->nullable();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
