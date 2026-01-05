<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('student_requirements')) {
            return;
        }

        Schema::table('student_requirements', function (Blueprint $table) {
            // Add fields for better partial receipt tracking
            if (!Schema::hasColumn('student_requirements', 'expected_quantity')) {
                $table->decimal('expected_quantity', 10, 2)
                    ->nullable()
                    ->after('quantity_required')
                    ->comment('Expected quantity (may differ from required if updated)');
            }

            if (!Schema::hasColumn('student_requirements', 'balance_pending')) {
                $table->decimal('balance_pending', 10, 2)
                    ->default(0)
                    ->after('quantity_missing')
                    ->comment('Outstanding balance after partial receipt');
            }

            if (!Schema::hasColumn('student_requirements', 'last_received_at')) {
                $table->timestamp('last_received_at')
                    ->nullable()
                    ->after('collected_at')
                    ->comment('Last time items were received (for partial updates)');
            }

            if (!Schema::hasColumn('student_requirements', 'can_update_receipt')) {
                $table->boolean('can_update_receipt')
                    ->default(true)
                    ->after('status')
                    ->comment('Whether receipt can be updated later');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('student_requirements')) {
            Schema::table('student_requirements', function (Blueprint $table) {
                $columns = [
                    'expected_quantity',
                    'balance_pending',
                    'last_received_at',
                    'can_update_receipt'
                ];

                foreach ($columns as $column) {
                    if (Schema::hasColumn('student_requirements', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};

