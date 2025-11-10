<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Status Management
            $table->enum('status', ['active', 'inactive', 'graduated', 'transferred', 'expelled', 'suspended'])->default('active')->after('archive');
            $table->date('admission_date')->nullable()->after('status');
            $table->date('graduation_date')->nullable()->after('admission_date');
            $table->date('transfer_date')->nullable()->after('graduation_date');
            $table->string('transfer_to_school')->nullable()->after('transfer_date');
            $table->text('status_change_reason')->nullable()->after('transfer_to_school');
            $table->unsignedBigInteger('status_changed_by')->nullable()->after('status_change_reason');
            $table->timestamp('status_changed_at')->nullable()->after('status_changed_by');
            
            // Re-admission tracking
            $table->boolean('is_readmission')->default(false)->after('status_changed_at');
            $table->unsignedBigInteger('previous_student_id')->nullable()->after('is_readmission');
            
            // Add foreign key for status_changed_by (references users table)
            $table->foreign('status_changed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['status_changed_by']);
            $table->dropColumn([
                'status', 'admission_date', 'graduation_date', 'transfer_date',
                'transfer_to_school', 'status_change_reason', 'status_changed_by',
                'status_changed_at', 'is_readmission', 'previous_student_id'
            ]);
        });
    }
};
