<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Check if document_counters table exists
        if (!Schema::hasTable('document_counters')) {
            Schema::create('document_counters', function (Blueprint $table) {
                $table->id();
                $table->string('type')->unique(); // invoice, receipt, credit_note, debit_note
                $table->string('prefix')->default('');
                $table->string('suffix')->default('');
                $table->integer('padding_length')->default(4);
                $table->bigInteger('next_number')->default(1);
                $table->enum('reset_period', ['never', 'yearly', 'monthly'])->default('never');
                $table->integer('last_reset_year')->nullable();
                $table->integer('last_reset_month')->nullable();
                $table->timestamps();
                
                $table->index('type');
            });
        } else {
            Schema::table('document_counters', function (Blueprint $table) {
                // Add missing columns if they don't exist
                if (!Schema::hasColumn('document_counters', 'prefix')) {
                    $table->string('prefix')->default('')->after('type');
                }
                if (!Schema::hasColumn('document_counters', 'suffix')) {
                    $table->string('suffix')->default('')->after('prefix');
                }
                if (!Schema::hasColumn('document_counters', 'padding_length')) {
                    $table->integer('padding_length')->default(4)->after('suffix');
                }
                if (!Schema::hasColumn('document_counters', 'reset_period')) {
                    $table->enum('reset_period', ['never', 'yearly', 'monthly'])->default('never')->after('next_number');
                }
                if (!Schema::hasColumn('document_counters', 'last_reset_year')) {
                    $table->integer('last_reset_year')->nullable()->after('reset_period');
                }
                if (!Schema::hasColumn('document_counters', 'last_reset_month')) {
                    $table->integer('last_reset_month')->nullable()->after('last_reset_year');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('document_counters')) {
            Schema::table('document_counters', function (Blueprint $table) {
                $columns = ['prefix', 'suffix', 'padding_length', 'reset_period', 'last_reset_year', 'last_reset_month'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('document_counters', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};

