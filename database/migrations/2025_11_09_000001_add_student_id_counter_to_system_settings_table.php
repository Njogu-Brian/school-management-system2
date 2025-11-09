<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('system_settings', 'student_id_counter')) {
                $table->unsignedBigInteger('student_id_counter')->default(0)->after('student_id_start');
            }
        });

        $maxAdmission = DB::table('students')
            ->pluck('admission_number')
            ->map(fn ($value) => (int) preg_replace('/\D/', '', (string) $value))
            ->filter()
            ->max();

        $nextCounter = $maxAdmission ? $maxAdmission + 1 : 0;

        if ($nextCounter > 0) {
            if ($row = DB::table('system_settings')->first()) {
                DB::table('system_settings')->where('id', $row->id)->update(['student_id_counter' => $nextCounter]);
            } else {
                DB::table('system_settings')->insert([
                    'student_id_counter' => $nextCounter,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            if (Schema::hasColumn('system_settings', 'student_id_counter')) {
                $table->dropColumn('student_id_counter');
            }
        });
    }
};
