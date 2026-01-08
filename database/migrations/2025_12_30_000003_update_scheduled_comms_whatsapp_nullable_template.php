<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scheduled_communications', function (Blueprint $table) {
            if (Schema::hasColumn('scheduled_communications', 'type')) {
                $table->string('type', 30)->default('email')->change(); // allow whatsapp
            }
            if (Schema::hasColumn('scheduled_communications', 'template_id')) {
                $table->unsignedBigInteger('template_id')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        // No strict down to avoid data loss
    }
};














