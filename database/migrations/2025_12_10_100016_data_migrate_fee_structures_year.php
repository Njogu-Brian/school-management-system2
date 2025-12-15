<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Data migration to convert integer year to academic_year_id in fee_structures
     */
    public function up(): void
    {
        DB::statement("
            UPDATE fee_structures fs
            INNER JOIN academic_years ay ON ay.year = fs.year
            SET fs.academic_year_id = ay.id
            WHERE fs.academic_year_id IS NULL
        ");
    }

    public function down(): void
    {
        // Cannot safely reverse
    }
};

