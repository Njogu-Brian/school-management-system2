<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drop_off_points', function (Blueprint $table) {
            // Rename point_name to name for consistency
            $table->renameColumn('point_name', 'name');

            // Ensure route_id supports multiple routes by making it nullable
            $table->unsignedBigInteger('route_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('drop_off_points', function (Blueprint $table) {
            // Reverse the changes
            $table->renameColumn('name', 'point_name');
            $table->unsignedBigInteger('route_id')->nullable(false)->change();
        });
    }
};
