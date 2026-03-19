<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scheduled_communications', function (Blueprint $table) {
            $table->string('classroom_ids')->nullable()->after('classroom_id');
        });
    }

    public function down(): void
    {
        Schema::table('scheduled_communications', function (Blueprint $table) {
            $table->dropColumn('classroom_ids');
        });
    }
};
