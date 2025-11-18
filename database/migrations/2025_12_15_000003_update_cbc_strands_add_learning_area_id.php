<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cbc_strands')) {
            Schema::table('cbc_strands', function (Blueprint $table) {
                // Add learning_area_id foreign key if learning_areas table exists and column doesn't exist
                if (Schema::hasTable('learning_areas') && !Schema::hasColumn('cbc_strands', 'learning_area_id')) {
                    $table->foreignId('learning_area_id')->nullable()->after('id')->constrained('learning_areas')->nullOnDelete();
                }
                
                // Keep learning_area string field for backward compatibility, but make it nullable if it exists
                if (Schema::hasColumn('cbc_strands', 'learning_area')) {
                    $table->string('learning_area')->nullable()->change();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('cbc_strands', function (Blueprint $table) {
            if (Schema::hasColumn('cbc_strands', 'learning_area_id')) {
                $table->dropForeign(['learning_area_id']);
                $table->dropColumn('learning_area_id');
            }
            $table->string('learning_area')->nullable(false)->change();
        });
    }
};

