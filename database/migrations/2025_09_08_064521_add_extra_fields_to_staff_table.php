<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->string('kra_pin')->nullable()->after('emergency_contact_phone');
            $table->string('nssf')->nullable()->after('kra_pin');
            $table->string('nhif')->nullable()->after('nssf');
            $table->string('bank_name')->nullable()->after('nhif');
            $table->string('bank_branch')->nullable()->after('bank_name');
            $table->string('bank_account')->nullable()->after('bank_branch');
            $table->string('department')->nullable()->after('bank_account');
            $table->string('job_title')->nullable()->after('department');
            $table->unsignedBigInteger('supervisor_id')->nullable()->after('job_title');
            $table->string('photo')->nullable()->after('supervisor_id');

            $table->foreign('supervisor_id')->references('id')->on('staff')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->dropForeign(['supervisor_id']);
            $table->dropColumn([
                'kra_pin','nssf','nhif','bank_name','bank_branch','bank_account',
                'department','job_title','supervisor_id','photo'
            ]);
        });
    }
};
