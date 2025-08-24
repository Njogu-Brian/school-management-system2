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
    Schema::table('voteheads', function (Blueprint $table) {
        $table->decimal('default_amount', 10, 2)->nullable()->after('is_mandatory');
    });
}

public function down()
{
    Schema::table('voteheads', function (Blueprint $table) {
        $table->dropColumn('default_amount');
    });
}

};
