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
        Schema::table('cbc_strands', function (Blueprint $table) {
            $table->foreignId('curriculum_design_id')->nullable()->after('id')->constrained('curriculum_designs')->nullOnDelete();
            $table->index('curriculum_design_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cbc_strands', function (Blueprint $table) {
            $table->dropForeign(['curriculum_design_id']);
            $table->dropIndex(['curriculum_design_id']);
            $table->dropColumn('curriculum_design_id');
        });
    }
};
