<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::create('classroom_teacher', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('classroom_id');
        $table->unsignedBigInteger('teacher_id'); // this actually references users.id
        $table->timestamps();

        $table->foreign('classroom_id')->references('id')->on('classrooms')->onDelete('cascade');
        $table->foreign('teacher_id')->references('id')->on('users')->onDelete('cascade'); // fix is here
    });
}


    public function down()
    {
        Schema::dropIfExists('classroom_teacher');
    }
};
