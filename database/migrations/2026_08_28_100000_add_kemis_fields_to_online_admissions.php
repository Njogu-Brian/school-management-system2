<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('online_admissions', function (Blueprint $table) {
            $stringCols = [
                'nationality' => 100,
                'county_of_birth' => 100,
                'sub_county_of_birth' => 120,
                'location_of_birth' => 150,
                'birth_certificate_entry_no' => 80,
                'medical_condition' => 255,
                'religion' => 255,
                'orphan_status' => 50,
                'disability_type' => 100,
                'father_first_name' => 255,
                'father_middle_name' => 255,
                'father_last_name' => 255,
                'father_id_type' => 50,
                'father_country_of_residence' => 100,
                'mother_first_name' => 255,
                'mother_middle_name' => 255,
                'mother_last_name' => 255,
                'mother_id_type' => 50,
                'mother_country_of_residence' => 100,
                'guardian_first_name' => 255,
                'guardian_middle_name' => 255,
                'guardian_last_name' => 255,
                'guardian_id_type' => 50,
                'guardian_country_of_residence' => 100,
                'father_whatsapp' => 50,
                'mother_whatsapp' => 50,
                'guardian_whatsapp' => 50,
            ];

            foreach ($stringCols as $column => $length) {
                if (! Schema::hasColumn('online_admissions', $column)) {
                    $table->string($column, $length)->nullable();
                }
            }

            if (! Schema::hasColumn('online_admissions', 'learner_interests')) {
                $table->json('learner_interests')->nullable();
            }

            if (! Schema::hasColumn('online_admissions', 'has_special_needs')) {
                $table->boolean('has_special_needs')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('online_admissions', function (Blueprint $table) {
            $columns = [
                'nationality', 'county_of_birth', 'sub_county_of_birth', 'location_of_birth',
                'birth_certificate_entry_no', 'medical_condition', 'religion', 'learner_interests',
                'orphan_status', 'has_special_needs', 'disability_type',
                'father_first_name', 'father_middle_name', 'father_last_name',
                'father_id_type', 'father_country_of_residence',
                'mother_first_name', 'mother_middle_name', 'mother_last_name',
                'mother_id_type', 'mother_country_of_residence',
                'guardian_first_name', 'guardian_middle_name', 'guardian_last_name',
                'guardian_id_type', 'guardian_country_of_residence',
                'father_whatsapp', 'mother_whatsapp', 'guardian_whatsapp',
            ];
            foreach ($columns as $column) {
                if (Schema::hasColumn('online_admissions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
