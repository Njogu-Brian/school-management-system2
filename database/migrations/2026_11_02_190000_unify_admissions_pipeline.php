<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE online_admissions MODIFY COLUMN application_status ENUM('pending','contacted','under_review','interviewed','ready_to_enroll','waitlisted','enrolled','rejected','accepted') DEFAULT 'pending'");
            DB::statement('ALTER TABLE online_admissions MODIFY COLUMN dob DATE NULL');
            DB::statement("ALTER TABLE online_admissions MODIFY COLUMN gender ENUM('Male','Female') NULL");
        }

        Schema::table('online_admissions', function (Blueprint $table) {
            if (! Schema::hasColumn('online_admissions', 'admission_application_id')) {
                $table->unsignedBigInteger('admission_application_id')->nullable()->after('id');
                $table->index('admission_application_id');
            }
            if (! Schema::hasColumn('online_admissions', 'student_id')) {
                $table->unsignedBigInteger('student_id')->nullable()->after('admission_application_id');
                $table->index('student_id');
            }
            if (! Schema::hasColumn('online_admissions', 'pipeline_stage')) {
                $table->string('pipeline_stage', 40)->nullable()->after('application_status');
            }
            if (! Schema::hasColumn('online_admissions', 'interview_required')) {
                $table->string('interview_required', 20)->nullable()->after('pipeline_stage'); // required|optional|not_applicable
            }
            if (! Schema::hasColumn('online_admissions', 'interview_date')) {
                $table->date('interview_date')->nullable();
            }
            if (! Schema::hasColumn('online_admissions', 'interview_notes')) {
                $table->text('interview_notes')->nullable();
            }
            if (! Schema::hasColumn('online_admissions', 'interview_outcome')) {
                $table->string('interview_outcome', 40)->nullable(); // passed|failed|deferred|skipped
            }
            if (! Schema::hasColumn('online_admissions', 'interviewed_by')) {
                $table->unsignedBigInteger('interviewed_by')->nullable();
            }
            if (! Schema::hasColumn('online_admissions', 'interview_fee_votehead_id')) {
                $table->unsignedBigInteger('interview_fee_votehead_id')->nullable();
            }
            if (! Schema::hasColumn('online_admissions', 'interview_fee_amount')) {
                $table->decimal('interview_fee_amount', 12, 2)->nullable();
            }
            if (! Schema::hasColumn('online_admissions', 'interview_fee_paid')) {
                $table->boolean('interview_fee_paid')->default(false);
            }
            if (! Schema::hasColumn('online_admissions', 'interview_fee_paid_at')) {
                $table->timestamp('interview_fee_paid_at')->nullable();
            }
            if (! Schema::hasColumn('online_admissions', 'interview_mpesa_code')) {
                $table->string('interview_mpesa_code', 64)->nullable();
            }
            if (! Schema::hasColumn('online_admissions', 'interview_receipt_number')) {
                $table->string('interview_receipt_number', 64)->nullable();
            }
            if (! Schema::hasColumn('online_admissions', 'interview_receipt_path')) {
                $table->string('interview_receipt_path')->nullable();
            }
            if (! Schema::hasColumn('online_admissions', 'interview_payment_id')) {
                $table->unsignedBigInteger('interview_payment_id')->nullable();
            }
            if (! Schema::hasColumn('online_admissions', 'signed_admission_form')) {
                $table->string('signed_admission_form')->nullable();
            }
            if (! Schema::hasColumn('online_admissions', 'admission_form_filled_at')) {
                $table->timestamp('admission_form_filled_at')->nullable();
            }
            if (! Schema::hasColumn('online_admissions', 'prospectus_parent_name')) {
                $table->string('prospectus_parent_name')->nullable();
            }
            if (! Schema::hasColumn('online_admissions', 'prospectus_phone')) {
                $table->string('prospectus_phone', 40)->nullable();
            }
            if (! Schema::hasColumn('online_admissions', 'prospectus_email')) {
                $table->string('prospectus_email')->nullable();
            }
            if (! Schema::hasColumn('online_admissions', 'application_no')) {
                $table->string('application_no', 40)->nullable()->index();
            }
        });

        Schema::table('admission_applications', function (Blueprint $table) {
            if (! Schema::hasColumn('admission_applications', 'online_admission_id')) {
                $table->unsignedBigInteger('online_admission_id')->nullable()->after('id');
                $table->index('online_admission_id');
            }
        });

        if (! Schema::hasTable('admission_interview_scores')) {
            Schema::create('admission_interview_scores', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('online_admission_id');
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->string('subject_name');
                $table->string('subject_code', 40)->nullable();
                $table->decimal('score', 8, 2)->nullable();
                $table->decimal('out_of', 8, 2)->default(100);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index('online_admission_id');
                $table->foreign('online_admission_id')
                    ->references('id')
                    ->on('online_admissions')
                    ->onDelete('cascade');
            });
        }

        if (Schema::hasTable('admission_documents') && ! Schema::hasColumn('admission_documents', 'online_admission_id')) {
            Schema::table('admission_documents', function (Blueprint $table) {
                $table->unsignedBigInteger('online_admission_id')->nullable()->after('application_id');
                $table->index('online_admission_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_interview_scores');

        Schema::table('admission_applications', function (Blueprint $table) {
            if (Schema::hasColumn('admission_applications', 'online_admission_id')) {
                $table->dropColumn('online_admission_id');
            }
        });

        if (Schema::hasTable('admission_documents') && Schema::hasColumn('admission_documents', 'online_admission_id')) {
            Schema::table('admission_documents', function (Blueprint $table) {
                $table->dropColumn('online_admission_id');
            });
        }

        $columns = [
            'admission_application_id', 'student_id', 'pipeline_stage', 'interview_required',
            'interview_date', 'interview_notes', 'interview_outcome', 'interviewed_by',
            'interview_fee_votehead_id', 'interview_fee_amount', 'interview_fee_paid',
            'interview_fee_paid_at', 'interview_mpesa_code', 'interview_receipt_number',
            'interview_receipt_path', 'interview_payment_id', 'signed_admission_form',
            'admission_form_filled_at', 'prospectus_parent_name', 'prospectus_phone',
            'prospectus_email', 'application_no',
        ];

        Schema::table('online_admissions', function (Blueprint $table) use ($columns) {
            foreach ($columns as $col) {
                if (Schema::hasColumn('online_admissions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE online_admissions MODIFY COLUMN application_status ENUM('pending', 'under_review', 'accepted', 'rejected', 'waitlisted', 'enrolled') DEFAULT 'pending'");
        }
    }
};
