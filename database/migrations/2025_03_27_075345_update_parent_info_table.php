<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateParentInfoTable extends Migration
{
    public function up()
    {
        Schema::table('parent_info', function (Blueprint $table) {
            // Check if the column does not exist before adding it
            if (!Schema::hasColumn('parent_info', 'guardian_relationship')) {
                $table->string('guardian_relationship')->nullable()->after('guardian_email');
            }
        });
    }

    public function down()
    {
        Schema::table('parent_info', function (Blueprint $table) {
            if (Schema::hasColumn('parent_info', 'guardian_relationship')) {
                $table->dropColumn('guardian_relationship');
            }
        });
    }
}
