<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_registrations', function (Blueprint $table) {
            $table->string('photo')->nullable()->after('personal_email');
            $table->string('residential_address')->nullable()->after('marital_status');
            $table->string('emergency_contact_name')->nullable()->after('phone_number');
            $table->string('emergency_contact_relationship', 100)->nullable()->after('emergency_contact_name');
            $table->string('payment_method', 20)->nullable()->after('bank_account');
            $table->unsignedInteger('max_lessons_per_week')->nullable()->after('payment_method');
            $table->foreignId('department_id')->nullable()->after('max_lessons_per_week')->constrained('departments')->nullOnDelete();
            $table->foreignId('job_title_id')->nullable()->after('department_id')->constrained('job_titles')->nullOnDelete();
            $table->foreignId('staff_category_id')->nullable()->after('job_title_id')->constrained('staff_categories')->nullOnDelete();
            $table->date('hire_date')->nullable()->after('staff_category_id');
            $table->string('employment_type', 32)->nullable()->after('hire_date');
            $table->date('contract_start_date')->nullable()->after('employment_type');
            $table->date('contract_end_date')->nullable()->after('contract_start_date');
        });
    }

    public function down(): void
    {
        Schema::table('staff_registrations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropConstrainedForeignId('job_title_id');
            $table->dropConstrainedForeignId('staff_category_id');
            $table->dropColumn([
                'photo',
                'residential_address',
                'emergency_contact_name',
                'emergency_contact_relationship',
                'hire_date',
                'employment_type',
                'contract_start_date',
                'contract_end_date',
                'payment_method',
                'max_lessons_per_week',
            ]);
        });
    }
};
