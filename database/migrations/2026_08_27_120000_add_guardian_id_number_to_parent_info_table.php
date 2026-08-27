<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parent_info', function (Blueprint $table) {
            if (! Schema::hasColumn('parent_info', 'guardian_id_number')) {
                $after = Schema::hasColumn('parent_info', 'guardian_id_type')
                    ? 'guardian_id_type'
                    : (Schema::hasColumn('parent_info', 'guardian_email') ? 'guardian_email' : null);
                if ($after) {
                    $table->string('guardian_id_number')->nullable()->after($after);
                } else {
                    $table->string('guardian_id_number')->nullable();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('parent_info', function (Blueprint $table) {
            if (Schema::hasColumn('parent_info', 'guardian_id_number')) {
                $table->dropColumn('guardian_id_number');
            }
        });
    }
};
