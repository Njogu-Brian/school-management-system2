<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Guarded to be idempotent on fresh or partially migrated DBs
        Schema::table('streams', function (Blueprint $table) {
            if (! Schema::hasColumn('streams', 'classroom_id')) {
            $table->unsignedBigInteger('classroom_id')->nullable()->after('name');
            }

            // Add FK only if not already present
            if (Schema::hasColumn('streams', 'classroom_id')) {
                try {
                    $table->foreign('classroom_id')->references('id')->on('classrooms')->onDelete('cascade');
                } catch (\Throwable $e) {
                    // Ignore if already exists
                }
            }
        });
    }

    public function down()
    {
        Schema::table('streams', function (Blueprint $table) {
            if (Schema::hasColumn('streams', 'classroom_id')) {
                // Drop FK if it exists
                try {
            $table->dropForeign(['classroom_id']);
                } catch (\Throwable $e) {
                    // ignore if already dropped
                }
            $table->dropColumn('classroom_id');
            }
        });
    }
};
