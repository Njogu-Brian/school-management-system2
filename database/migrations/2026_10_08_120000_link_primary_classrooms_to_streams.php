<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Grade 1 owns Love and Peace as the primary classroom, but those rows
     * were missing from classroom_stream, so the class list showed no stream.
     * This only adds the missing links. Students, teachers, and stream ids stay put.
     */
    public function up(): void
    {
        DB::statement('
            INSERT IGNORE INTO classroom_stream (classroom_id, stream_id, created_at, updated_at)
            SELECT classroom_id, id, NOW(), NOW()
            FROM streams
            WHERE classroom_id IS NOT NULL
        ');
    }

    public function down(): void
    {
        // The links may have existed before this migration. Leave them in place.
    }
};
