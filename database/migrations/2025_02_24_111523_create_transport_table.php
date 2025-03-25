<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('transport', function (Blueprint $table) {
            $table->id();
            $table->string('driver_name');
            $table->string('vehicle_number');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('transport');
    }
};