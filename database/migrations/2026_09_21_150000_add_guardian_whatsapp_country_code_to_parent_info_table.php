<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('parent_info')) {
            return;
        }

        Schema::table('parent_info', function (Blueprint $table) {
            if (! Schema::hasColumn('parent_info', 'guardian_whatsapp_country_code')) {
                $after = Schema::hasColumn('parent_info', 'guardian_phone_country_code')
                    ? 'guardian_phone_country_code'
                    : (Schema::hasColumn('parent_info', 'guardian_whatsapp') ? 'guardian_whatsapp' : null);
                $col = $table->string('guardian_whatsapp_country_code', 10)->nullable();
                if ($after) {
                    $col->after($after);
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('parent_info')) {
            return;
        }

        Schema::table('parent_info', function (Blueprint $table) {
            if (Schema::hasColumn('parent_info', 'guardian_whatsapp_country_code')) {
                $table->dropColumn('guardian_whatsapp_country_code');
            }
        });
    }
};
