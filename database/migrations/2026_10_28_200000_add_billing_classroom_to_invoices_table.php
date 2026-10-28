<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'classroom_id')) {
                $table->foreignId('classroom_id')
                    ->nullable()
                    ->after('student_id')
                    ->constrained('classrooms')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('invoices', 'stream_id')) {
                $table->foreignId('stream_id')
                    ->nullable()
                    ->after('classroom_id')
                    ->constrained('streams')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'stream_id')) {
                $table->dropConstrainedForeignId('stream_id');
            }
            if (Schema::hasColumn('invoices', 'classroom_id')) {
                $table->dropConstrainedForeignId('classroom_id');
            }
        });
    }
};
