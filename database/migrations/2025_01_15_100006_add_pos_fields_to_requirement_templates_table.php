<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Skip if table doesn't exist yet (will be created later)
        if (!Schema::hasTable('requirement_templates')) {
            return;
        }
        Schema::table('requirement_templates', function (Blueprint $table) {
            // Add column without foreign key constraint (will be added in separate migration)
            if (!Schema::hasColumn('requirement_templates', 'pos_product_id')) {
                $table->unsignedBigInteger('pos_product_id')->nullable()->after('requirement_type_id');
            }
            if (!Schema::hasColumn('requirement_templates', 'is_available_in_shop')) {
                $table->boolean('is_available_in_shop')->default(false)->after('is_active');
            }
        });
        
        // Note: Foreign key constraint to pos_products will be added in separate migration
    }

    public function down(): void
    {
        if (Schema::hasTable('requirement_templates')) {
            Schema::table('requirement_templates', function (Blueprint $table) {
                // Drop foreign key if it exists
                if (Schema::hasColumn('requirement_templates', 'pos_product_id')) {
                    try {
                        $table->dropForeign(['pos_product_id']);
                    } catch (\Exception $e) {
                        // Ignore if doesn't exist
                    }
                }
                $table->dropColumnIfExists(['pos_product_id', 'is_available_in_shop']);
            });
        }
    }
};



