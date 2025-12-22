<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Fix the permissions table structure to match Spatie Permission package requirements
     */
    public function up(): void
    {
        if (!Schema::hasTable('permissions')) {
            // Create table if it doesn't exist
            Schema::create('permissions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
            });
            return;
        }

        // Check if table has old custom structure (module/feature columns)
        if (Schema::hasColumn('permissions', 'module')) {
            // Old custom structure - need to drop foreign keys first, then recreate
            // Drop foreign key constraints from permission_role table if it exists
            if (Schema::hasTable('permission_role')) {
                // Only drop if the FK exists
                $fkExists = DB::selectOne("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'permission_role' AND COLUMN_NAME = 'permission_id' AND REFERENCED_TABLE_NAME IS NOT NULL");
                if ($fkExists && isset($fkExists->CONSTRAINT_NAME)) {
                    Schema::table('permission_role', function (Blueprint $table) {
                        try {
                            $table->dropForeign(['permission_id']);
                        } catch (\Exception $e) {
                            // Foreign key might not exist or have different name
                        }
                    });
                }
            }
            // Also check role_has_permissions table
            if (Schema::hasTable('role_has_permissions')) {
                $fkExists = DB::selectOne("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'role_has_permissions' AND COLUMN_NAME = 'permission_id' AND REFERENCED_TABLE_NAME IS NOT NULL");
                if ($fkExists && isset($fkExists->CONSTRAINT_NAME)) {
                    Schema::table('role_has_permissions', function (Blueprint $table) {
                        try {
                            $table->dropForeign(['permission_id']);
                        } catch (\Exception $e) {
                            // Foreign key might not exist
                        }
                    });
                }
            }
            
            // Drop and recreate with Spatie structure
            Schema::dropIfExists('permissions');
            Schema::create('permissions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
            });
        } else {
            // Table exists but might be missing required columns
            if (!Schema::hasColumn('permissions', 'name')) {
                Schema::table('permissions', function (Blueprint $table) {
                    $table->string('name')->after('id');
                });
            }
            
            if (!Schema::hasColumn('permissions', 'guard_name')) {
                Schema::table('permissions', function (Blueprint $table) {
                    $table->string('guard_name')->default('web')->after('name');
                    // Update existing records to have guard_name = 'web'
                    DB::table('permissions')->update(['guard_name' => 'web']);
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't revert this fix
    }
};

