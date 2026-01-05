<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Create inventory types lookup table
        if (!Schema::hasTable('inventory_types')) {
            Schema::create('inventory_types', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique(); // student_stationery, school_stationery, uniforms, textbooks, food, other
                $table->string('display_name');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            // Insert default inventory types
            DB::table('inventory_types')->insert([
                ['name' => 'student_stationery', 'display_name' => 'Student-Collected Stationery', 'description' => 'Items collected from students', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'school_stationery', 'display_name' => 'School-Purchased Stationery', 'description' => 'Items purchased by school', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'uniforms', 'display_name' => 'Uniforms', 'description' => 'School uniforms', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'textbooks', 'display_name' => 'Textbooks', 'description' => 'Textbooks and learning materials', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'food', 'display_name' => 'Food Items', 'description' => 'Food and consumables', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'other', 'display_name' => 'Other', 'description' => 'Other inventory items', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        // Add inventory_type_id to inventory_items if table exists
        if (Schema::hasTable('inventory_items')) {
            Schema::table('inventory_items', function (Blueprint $table) {
                if (!Schema::hasColumn('inventory_items', 'inventory_type_id')) {
                    $table->foreignId('inventory_type_id')
                        ->nullable()
                        ->constrained('inventory_types')
                        ->onDelete('set null')
                        ->after('category');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('inventory_items')) {
            Schema::table('inventory_items', function (Blueprint $table) {
                if (Schema::hasColumn('inventory_items', 'inventory_type_id')) {
                    $table->dropForeign(['inventory_type_id']);
                    $table->dropColumn('inventory_type_id');
                }
            });
        }

        if (Schema::hasTable('inventory_types')) {
            Schema::dropIfExists('inventory_types');
        }
    }
};

