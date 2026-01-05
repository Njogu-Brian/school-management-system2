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
     * This migration removes all route-related tables and columns from the transport module.
     */
    public function up(): void
    {
        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        try {
            // Helper function to drop foreign key safely
            $dropForeignKey = function($tableName, $columnName) {
                // Query for foreign keys using REFERENTIAL_CONSTRAINTS for more reliable results
                $foreignKeys = DB::select(
                    "SELECT rc.CONSTRAINT_NAME 
                     FROM information_schema.REFERENTIAL_CONSTRAINTS rc
                     JOIN information_schema.KEY_COLUMN_USAGE kcu 
                         ON rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME 
                         AND rc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA
                     WHERE rc.CONSTRAINT_SCHEMA = DATABASE() 
                     AND kcu.TABLE_NAME = ? 
                     AND kcu.COLUMN_NAME = ?
                     AND rc.REFERENCED_TABLE_NAME = 'routes'",
                    [$tableName, $columnName]
                );
                
                foreach ($foreignKeys as $fk) {
                    try {
                        DB::statement("ALTER TABLE `{$tableName}` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
                    } catch (\Exception $e) {
                        // Foreign key might not exist, continue
                    }
                }
            };

            // Remove route_id from trips table
            if (Schema::hasColumn('trips', 'route_id')) {
                $dropForeignKey('trips', 'route_id');
                Schema::table('trips', function (Blueprint $table) {
                    $table->dropColumn('route_id');
                });
            }

            // Remove route_id from students table
            if (Schema::hasColumn('students', 'route_id')) {
                $dropForeignKey('students', 'route_id');
                Schema::table('students', function (Blueprint $table) {
                    $table->dropColumn('route_id');
                });
            }

            // Remove route_id from drop_off_points table
            if (Schema::hasColumn('drop_off_points', 'route_id')) {
                $dropForeignKey('drop_off_points', 'route_id');
                Schema::table('drop_off_points', function (Blueprint $table) {
                    $table->dropColumn('route_id');
                });
            }

            // Remove route_id from online_admissions table
            if (Schema::hasColumn('online_admissions', 'route_id')) {
                $dropForeignKey('online_admissions', 'route_id');
                Schema::table('online_admissions', function (Blueprint $table) {
                    $table->dropColumn('route_id');
                });
            }

            // Drop route_vehicle pivot table (drops foreign keys automatically)
            if (Schema::hasTable('route_vehicle')) {
                Schema::dropIfExists('route_vehicle');
            }

            // Drop routes table (must be last as other tables may reference it)
            if (Schema::hasTable('routes')) {
                Schema::dropIfExists('routes');
            }

        } finally {
            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    /**
     * Reverse the migrations.
     * 
     * Note: This is a destructive migration. Reversing would require 
     * recreating the routes table structure and data, which may not be possible
     * without data loss. Consider this migration irreversible.
     */
    public function down(): void
    {
        // Recreate routes table
        if (!Schema::hasTable('routes')) {
            Schema::create('routes', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('area')->nullable();
                $table->timestamps();
            });
        }

        // Recreate route_vehicle pivot table
        if (!Schema::hasTable('route_vehicle')) {
            Schema::create('route_vehicle', function (Blueprint $table) {
                $table->id();
                $table->foreignId('route_id')->constrained('routes')->onDelete('cascade');
                $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
                $table->timestamps();
            });
        }

        // Add route_id columns back (nullable)
        Schema::table('trips', function (Blueprint $table) {
            if (!Schema::hasColumn('trips', 'route_id')) {
                $table->foreignId('route_id')->nullable()->constrained('routes')->onDelete('set null');
            }
        });

        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'route_id')) {
                $table->foreignId('route_id')->nullable()->constrained('routes')->onDelete('set null');
            }
        });

        Schema::table('drop_off_points', function (Blueprint $table) {
            if (!Schema::hasColumn('drop_off_points', 'route_id')) {
                $table->foreignId('route_id')->nullable()->constrained('routes')->onDelete('cascade');
            }
        });

        Schema::table('online_admissions', function (Blueprint $table) {
            if (!Schema::hasColumn('online_admissions', 'route_id')) {
                $table->foreignId('route_id')->nullable()->constrained('routes')->onDelete('set null');
            }
        });
    }
};
