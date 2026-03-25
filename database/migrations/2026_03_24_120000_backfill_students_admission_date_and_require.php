<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement("
                UPDATE students
                SET admission_date = date(created_at)
                WHERE admission_date IS NULL
            ");
        } else {
            DB::table('students')
                ->whereNull('admission_date')
                ->update(['admission_date' => DB::raw('DATE(created_at)')]);
        }

        Schema::table('students', function (Blueprint $table) {
            $table->date('admission_date')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->date('admission_date')->nullable()->change();
        });
    }
};
