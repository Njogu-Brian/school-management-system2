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
    Schema::table('students', function (Blueprint $table) {
        $table->string('drop_off_point')->nullable()->after('class');
    });
}

public function down()
{
    Schema::table('students', function (Blueprint $table) {
        $table->dropColumn('drop_off_point');
    });
}
};
