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
        Schema::table('parent_info', function (Blueprint $table) {
            if (!Schema::hasColumn('parent_info', 'father_whatsapp_country_code')) {
                $table->string('father_whatsapp_country_code', 10)->nullable()->after('father_phone_country_code');
            }
            if (!Schema::hasColumn('parent_info', 'mother_whatsapp_country_code')) {
                $table->string('mother_whatsapp_country_code', 10)->nullable()->after('mother_phone_country_code');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parent_info', function (Blueprint $table) {
            if (Schema::hasColumn('parent_info', 'father_whatsapp_country_code')) {
                $table->dropColumn('father_whatsapp_country_code');
            }
            if (Schema::hasColumn('parent_info', 'mother_whatsapp_country_code')) {
                $table->dropColumn('mother_whatsapp_country_code');
            }
        });
    }
};
