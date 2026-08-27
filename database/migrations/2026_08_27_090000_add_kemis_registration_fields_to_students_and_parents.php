<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'kcpe_kjsea_year')) {
                $table->unsignedSmallInteger('kcpe_kjsea_year')->nullable()->after('knec_assessment_number');
            }
            if (! Schema::hasColumn('students', 'nationality')) {
                $table->string('nationality', 100)->nullable()->after('gender');
            }
            if (! Schema::hasColumn('students', 'county_of_birth')) {
                $table->string('county_of_birth', 100)->nullable()->after('nationality');
            }
            if (! Schema::hasColumn('students', 'sub_county_of_birth')) {
                $table->string('sub_county_of_birth', 120)->nullable()->after('county_of_birth');
            }
            if (! Schema::hasColumn('students', 'location_of_birth')) {
                $table->string('location_of_birth', 150)->nullable()->after('sub_county_of_birth');
            }
            if (! Schema::hasColumn('students', 'birth_certificate_entry_no')) {
                $table->string('birth_certificate_entry_no', 80)->nullable()->after('location_of_birth');
            }
            if (! Schema::hasColumn('students', 'medical_condition')) {
                $table->string('medical_condition', 255)->nullable()->after('chronic_conditions');
            }
            if (! Schema::hasColumn('students', 'learner_interests')) {
                $table->json('learner_interests')->nullable()->after('religion');
            }
            if (! Schema::hasColumn('students', 'orphan_status')) {
                $table->string('orphan_status', 20)->nullable()->after('gender');
            }
            if (! Schema::hasColumn('students', 'disability_type')) {
                $table->string('disability_type', 100)->nullable()->after('has_special_needs');
            }
        });

        Schema::table('parent_info', function (Blueprint $table) {
            foreach (['father', 'mother', 'guardian'] as $slot) {
                if (! Schema::hasColumn('parent_info', "{$slot}_first_name")) {
                    $table->string("{$slot}_first_name")->nullable()->after("{$slot}_name");
                }
                if (! Schema::hasColumn('parent_info', "{$slot}_middle_name")) {
                    $table->string("{$slot}_middle_name")->nullable()->after("{$slot}_first_name");
                }
                if (! Schema::hasColumn('parent_info', "{$slot}_last_name")) {
                    $table->string("{$slot}_last_name")->nullable()->after("{$slot}_middle_name");
                }
                if (! Schema::hasColumn('parent_info', "{$slot}_id_type")) {
                    $after = $slot === 'guardian' ? 'guardian_relationship' : "{$slot}_id_number";
                    if (Schema::hasColumn('parent_info', $after)) {
                        $table->string("{$slot}_id_type", 50)->nullable()->after($after);
                    } else {
                        $table->string("{$slot}_id_type", 50)->nullable();
                    }
                }
                if (! Schema::hasColumn('parent_info', "{$slot}_country_of_residence")) {
                    $table->string("{$slot}_country_of_residence", 100)->nullable()->after("{$slot}_id_type");
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $cols = [
                'kcpe_kjsea_year',
                'nationality',
                'county_of_birth',
                'sub_county_of_birth',
                'location_of_birth',
                'birth_certificate_entry_no',
                'medical_condition',
                'learner_interests',
                'orphan_status',
                'disability_type',
            ];
            $existing = array_values(array_filter($cols, fn ($c) => Schema::hasColumn('students', $c)));
            if ($existing) {
                $table->dropColumn($existing);
            }
        });

        Schema::table('parent_info', function (Blueprint $table) {
            $cols = [];
            foreach (['father', 'mother', 'guardian'] as $slot) {
                $cols[] = "{$slot}_first_name";
                $cols[] = "{$slot}_middle_name";
                $cols[] = "{$slot}_last_name";
                $cols[] = "{$slot}_id_type";
                $cols[] = "{$slot}_country_of_residence";
            }
            $existing = array_values(array_filter($cols, fn ($c) => Schema::hasColumn('parent_info', $c)));
            if ($existing) {
                $table->dropColumn($existing);
            }
        });
    }
};
