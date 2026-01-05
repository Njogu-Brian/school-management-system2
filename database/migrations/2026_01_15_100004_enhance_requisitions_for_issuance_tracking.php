<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('requisitions')) {
            return;
        }

        Schema::table('requisitions', function (Blueprint $table) {
            // Add issuance tracking fields
            if (!Schema::hasColumn('requisitions', 'issued_by')) {
                $table->foreignId('issued_by')
                    ->nullable()
                    ->constrained('users')
                    ->onDelete('set null')
                    ->after('approved_by');
            }

            if (!Schema::hasColumn('requisitions', 'issued_at')) {
                $table->timestamp('issued_at')
                    ->nullable()
                    ->after('fulfilled_at');
            }
        });

        if (!Schema::hasTable('requisition_items')) {
            return;
        }

        Schema::table('requisition_items', function (Blueprint $table) {
            // Add issuance tracking to items
            if (!Schema::hasColumn('requisition_items', 'issued_by')) {
                $table->foreignId('issued_by')
                    ->nullable()
                    ->constrained('users')
                    ->onDelete('set null')
                    ->after('quantity_issued');
            }

            if (!Schema::hasColumn('requisition_items', 'issued_at')) {
                $table->timestamp('issued_at')
                    ->nullable()
                    ->after('issued_by');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('requisition_items')) {
            Schema::table('requisition_items', function (Blueprint $table) {
                if (Schema::hasColumn('requisition_items', 'issued_at')) {
                    $table->dropColumn('issued_at');
                }
                if (Schema::hasColumn('requisition_items', 'issued_by')) {
                    $table->dropForeign(['issued_by']);
                    $table->dropColumn('issued_by');
                }
            });
        }

        if (Schema::hasTable('requisitions')) {
            Schema::table('requisitions', function (Blueprint $table) {
                if (Schema::hasColumn('requisitions', 'issued_at')) {
                    $table->dropColumn('issued_at');
                }
                if (Schema::hasColumn('requisitions', 'issued_by')) {
                    $table->dropForeign(['issued_by']);
                    $table->dropColumn('issued_by');
                }
            });
        }
    }
};

