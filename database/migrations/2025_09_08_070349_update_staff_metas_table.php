<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_meta', function (Blueprint $table) {
            if (!Schema::hasColumn('staff_meta', 'staff_id')) {
                $table->unsignedBigInteger('staff_id')->after('id');
                $table->foreign('staff_id')->references('id')->on('staff')->onDelete('cascade');
            }
            if (!Schema::hasColumn('staff_meta', 'field_key')) {
                $table->string('field_key', 100)->after('staff_id');
            }
            if (!Schema::hasColumn('staff_meta', 'field_value')) {
                $table->text('field_value')->nullable()->after('field_key');
            }
        });
    }

    public function down(): void
    {
        Schema::table('staff_meta', function (Blueprint $table) {
            $table->dropForeign(['staff_id']);
            $table->dropColumn(['staff_id','field_key','field_value']);
        });
    }
};
