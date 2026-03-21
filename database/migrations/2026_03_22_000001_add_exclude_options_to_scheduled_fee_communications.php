<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scheduled_fee_communications', function (Blueprint $table) {
            $table->boolean('exclude_staff')->default(true)->after('classroom_ids');
            $table->json('exclude_student_ids')->nullable()->after('exclude_staff');
        });
    }

    public function down(): void
    {
        Schema::table('scheduled_fee_communications', function (Blueprint $table) {
            $table->dropColumn(['exclude_staff', 'exclude_student_ids']);
        });
    }
};
