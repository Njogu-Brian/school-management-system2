<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        /**
         * Ensure a default category exists and backfill students.
         */
        $defaultCategoryId = DB::table('student_categories')->where('name', 'General')->value('id');
        if (!$defaultCategoryId) {
            $defaultCategoryId = DB::table('student_categories')->insertGetId([
                'name' => 'General',
                'description' => 'Default category for students without a specified category',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Students table updates
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'drop_off_point_id')) {
                $table->foreignId('drop_off_point_id')->nullable()->after('route_id')->constrained('drop_off_points')->onDelete('set null');
            }
            if (!Schema::hasColumn('students', 'drop_off_point_other')) {
                $table->string('drop_off_point_other')->nullable()->after('drop_off_point_id');
            }
            if (!Schema::hasColumn('students', 'trip_id')) {
                $table->foreignId('trip_id')->nullable()->after('route_id')->constrained('trips')->onDelete('set null');
            }
        });

        // Backfill student categories to default where missing
        DB::table('students')->whereNull('category_id')->update(['category_id' => $defaultCategoryId]);

        // Online admissions enhancements
        Schema::table('online_admissions', function (Blueprint $table) {
            if (!Schema::hasColumn('online_admissions', 'preferred_classroom_id')) {
                $table->foreignId('preferred_classroom_id')->nullable()->after('gender')->constrained('classrooms')->onDelete('set null');
            }
            if (!Schema::hasColumn('online_admissions', 'transport_needed')) {
                $table->boolean('transport_needed')->default(false)->after('application_source');
            }
            if (!Schema::hasColumn('online_admissions', 'drop_off_point_id')) {
                $table->foreignId('drop_off_point_id')->nullable()->after('transport_needed')->constrained('drop_off_points')->onDelete('set null');
            }
            if (!Schema::hasColumn('online_admissions', 'drop_off_point_other')) {
                $table->string('drop_off_point_other')->nullable()->after('drop_off_point_id');
            }
            if (!Schema::hasColumn('online_admissions', 'route_id')) {
                $table->foreignId('route_id')->nullable()->after('drop_off_point_other')->constrained('routes')->onDelete('set null');
            }
            if (!Schema::hasColumn('online_admissions', 'trip_id')) {
                $table->foreignId('trip_id')->nullable()->after('route_id')->constrained('trips')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'trip_id')) {
                $table->dropForeign(['trip_id']);
                $table->dropColumn('trip_id');
            }
            if (Schema::hasColumn('students', 'drop_off_point_id')) {
                $table->dropForeign(['drop_off_point_id']);
                $table->dropColumn('drop_off_point_id');
            }
            if (Schema::hasColumn('students', 'drop_off_point_other')) {
                $table->dropColumn('drop_off_point_other');
            }
        });

        Schema::table('online_admissions', function (Blueprint $table) {
            if (Schema::hasColumn('online_admissions', 'preferred_classroom_id')) {
                $table->dropForeign(['preferred_classroom_id']);
                $table->dropColumn('preferred_classroom_id');
            }
            if (Schema::hasColumn('online_admissions', 'transport_needed')) {
                $table->dropColumn('transport_needed');
            }
            if (Schema::hasColumn('online_admissions', 'drop_off_point_id')) {
                $table->dropForeign(['drop_off_point_id']);
                $table->dropColumn('drop_off_point_id');
            }
            if (Schema::hasColumn('online_admissions', 'drop_off_point_other')) {
                $table->dropColumn('drop_off_point_other');
            }
            if (Schema::hasColumn('online_admissions', 'route_id')) {
                $table->dropForeign(['route_id']);
                $table->dropColumn('route_id');
            }
            if (Schema::hasColumn('online_admissions', 'trip_id')) {
                $table->dropForeign(['trip_id']);
                $table->dropColumn('trip_id');
            }
        });
    }
};

