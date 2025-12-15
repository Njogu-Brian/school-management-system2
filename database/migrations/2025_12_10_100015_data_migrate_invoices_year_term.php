<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Data migration to convert integer year/term to FK relationships
     * Run this AFTER enhance_invoices_table migration
     */
    public function up(): void
    {
        // Migrate invoices.year to academic_year_id
        DB::statement("
            UPDATE invoices i
            INNER JOIN academic_years ay ON ay.year = i.year
            SET i.academic_year_id = ay.id
            WHERE i.academic_year_id IS NULL
        ");
        
        // Migrate invoices.term to term_id
        DB::statement("
            UPDATE invoices i
            INNER JOIN terms t ON t.academic_year_id = i.academic_year_id AND CAST(SUBSTRING(t.name, -1) AS UNSIGNED) = i.term
            SET i.term_id = t.id
            WHERE i.term_id IS NULL AND i.academic_year_id IS NOT NULL
        ");
        
        // Alternative: If term name doesn't match, try to match by term number in term table
        // This assumes terms table has a term_number field or similar
    }

    public function down(): void
    {
        // Cannot safely reverse data migration
        // The year/term integers would need to be reconstructed from FKs
    }
};

