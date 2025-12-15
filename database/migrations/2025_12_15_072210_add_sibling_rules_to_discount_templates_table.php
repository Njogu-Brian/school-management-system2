<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('discount_templates', function (Blueprint $table) {
            // Sibling discount rules (JSON): defines discount for each child position
            // Example: {"2": 5, "3": 10, "4": 15} means 2nd child gets 5%, 3rd gets 10%, 4th gets 15%
            $table->json('sibling_rules')->nullable()->after('value');
            
            // Votehead IDs for votehead-specific discounts (JSON array)
            $table->json('votehead_ids')->nullable()->after('scope');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('discount_templates', function (Blueprint $table) {
            $table->dropColumn(['sibling_rules', 'votehead_ids']);
        });
    }
};
