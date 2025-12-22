<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration adds foreign key constraints to pos_products table
     * after inventory_items and requirement_types tables have been created.
     */
    public function up(): void
    {
        // Only add foreign keys if both pos_products table exists and target tables exist
        if (!Schema::hasTable('pos_products')) {
            return;
        }

        Schema::table('pos_products', function (Blueprint $table) {
            // Add foreign key to inventory_items if table exists
            if (Schema::hasTable('inventory_items') && Schema::hasColumn('pos_products', 'inventory_item_id')) {
                try {
                    // Try to add foreign key - will fail gracefully if it already exists
                    $table->foreign('inventory_item_id')
                        ->references('id')
                        ->on('inventory_items')
                        ->onDelete('set null');
                } catch (\Exception $e) {
                    // Foreign key may already exist, ignore
                }
            }

            // Add foreign key to requirement_types if table exists
            if (Schema::hasTable('requirement_types') && Schema::hasColumn('pos_products', 'requirement_type_id')) {
                try {
                    // Try to add foreign key - will fail gracefully if it already exists
                    $table->foreign('requirement_type_id')
                        ->references('id')
                        ->on('requirement_types')
                        ->onDelete('set null');
                } catch (\Exception $e) {
                    // Foreign key may already exist, ignore
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('pos_products')) {
            Schema::table('pos_products', function (Blueprint $table) {
                // Drop foreign keys if they exist
                $foreignKeys = $this->getTableForeignKeys('pos_products');
                foreach ($foreignKeys as $fk) {
                    $columns = $fk->getLocalColumns();
                    if (in_array('inventory_item_id', $columns) || in_array('requirement_type_id', $columns)) {
                        $table->dropForeign([$columns[0]]);
                    }
                }
            });
        }
    }

    /**
     * Get foreign keys for a table
     */
    private function getTableForeignKeys(string $table): array
    {
        try {
            $connection = Schema::getConnection();
            if (method_exists($connection, 'getDoctrineSchemaManager')) {
                return $connection->getDoctrineSchemaManager()->listTableForeignKeys($table);
            }
            // Fallback: check if columns exist and assume no FK if method doesn't exist
            return [];
        } catch (\Exception $e) {
            // If getDoctrineSchemaManager doesn't exist or fails, return empty array
            return [];
        }
    }
};

