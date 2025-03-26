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
        Schema::table('trip', function (Blueprint $table) {
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles');
            $table->foreignId('route_id')->constrained('routes');
            $table->string('drop_off_point')->nullable();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
