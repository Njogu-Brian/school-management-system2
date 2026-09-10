<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('assessments')) {
            return;
        }

        Schema::table('assessments', function (Blueprint $table) {
            if (! Schema::hasColumn('assessments', 'question_count')) {
                $table->unsignedSmallInteger('question_count')->nullable()->after('out_of');
            }
            if (! Schema::hasColumn('assessments', 'batch_key')) {
                $table->string('batch_key', 64)->nullable()->index()->after('question_count');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('assessments')) {
            return;
        }

        Schema::table('assessments', function (Blueprint $table) {
            if (Schema::hasColumn('assessments', 'batch_key')) {
                $table->dropColumn('batch_key');
            }
            if (Schema::hasColumn('assessments', 'question_count')) {
                $table->dropColumn('question_count');
            }
        });
    }
};
