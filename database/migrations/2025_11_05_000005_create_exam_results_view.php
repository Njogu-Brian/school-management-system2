<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('CREATE OR REPLACE VIEW exam_results AS SELECT * FROM exam_marks');
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS exam_results');
    }
};
