<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateParentInfoTable extends Migration
{
    public function up()
    {
        Schema::table('parent_info', function (Blueprint $table) {
            $table->string('father_name')->nullable();
            $table->string('father_phone')->nullable();
            $table->string('father_whatsapp')->nullable();
            $table->string('father_email')->nullable();
            $table->string('father_id_number')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('mother_phone')->nullable();
            $table->string('mother_whatsapp')->nullable();
            $table->string('mother_email')->nullable();
            $table->string('mother_id_number')->nullable();
            $table->string('guardian_name')->nullable();
            $table->string('guardian_phone')->nullable();
            $table->string('guardian_email')->nullable();
            $table->string('guardian_relationship')->nullable();
        });
    }

    public function down()
    {
        Schema::table('parent_info', function (Blueprint $table) {
            $table->dropColumn([
                'father_name', 'father_phone', 'father_whatsapp', 'father_email', 'father_id_number',
                'mother_name', 'mother_phone', 'mother_whatsapp', 'mother_email', 'mother_id_number',
                'guardian_name', 'guardian_phone', 'guardian_email', 'guardian_relationship'
            ]);
        });
    }
}
