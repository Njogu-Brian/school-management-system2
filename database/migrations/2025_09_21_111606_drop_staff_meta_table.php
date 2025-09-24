<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::dropIfExists('staff_meta'); // keep staff_metas
    }

    public function down()
    {
        Schema::create('staff_meta', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id');
            $table->string('field_key');
            $table->text('field_value')->nullable();
            $table->timestamps();
        });
    }

};
