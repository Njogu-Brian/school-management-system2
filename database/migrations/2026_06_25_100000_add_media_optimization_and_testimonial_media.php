<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_library', function (Blueprint $table) {
            if (! Schema::hasColumn('media_library', 'variants')) {
                $table->json('variants')->nullable()->after('optimized_path');
            }
            if (! Schema::hasColumn('media_library', 'optimization_status')) {
                $table->string('optimization_status', 20)->default('pending')->after('variants');
            }
            if (! Schema::hasColumn('media_library', 'width')) {
                $table->unsignedSmallInteger('width')->nullable()->after('optimization_status');
            }
            if (! Schema::hasColumn('media_library', 'height')) {
                $table->unsignedSmallInteger('height')->nullable()->after('width');
            }
        });

        Schema::table('testimonials', function (Blueprint $table) {
            if (! Schema::hasColumn('testimonials', 'media_id')) {
                $table->foreignId('media_id')->nullable()->after('photo')->constrained('media_library')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            if (Schema::hasColumn('testimonials', 'media_id')) {
                $table->dropConstrainedForeignId('media_id');
            }
        });

        Schema::table('media_library', function (Blueprint $table) {
            foreach (['height', 'width', 'optimization_status', 'variants'] as $col) {
                if (Schema::hasColumn('media_library', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
