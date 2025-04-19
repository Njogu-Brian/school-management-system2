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
    Schema::table('communication_templates', function (Blueprint $table) {
        $table->string('code')->unique()->after('id');
    });
}

public function down()
{
    Schema::table('communication_templates', function (Blueprint $table) {
        $table->dropColumn('code');
    });
}

};
