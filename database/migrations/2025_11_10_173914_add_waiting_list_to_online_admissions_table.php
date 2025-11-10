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
        Schema::table('online_admissions', function (Blueprint $table) {
            $table->enum('application_status', ['pending', 'under_review', 'accepted', 'rejected', 'waitlisted'])->default('pending')->after('enrolled');
            $table->integer('waitlist_position')->nullable()->after('application_status');
            $table->unsignedBigInteger('reviewed_by')->nullable()->after('waitlist_position');
            $table->text('review_notes')->nullable()->after('reviewed_by');
            $table->date('application_date')->default(now())->after('review_notes');
            $table->date('review_date')->nullable()->after('application_date');
            $table->unsignedBigInteger('classroom_id')->nullable()->after('review_date');
            $table->unsignedBigInteger('stream_id')->nullable()->after('classroom_id');
            $table->string('application_source')->nullable()->after('stream_id'); // online, walk-in, referral
            $table->text('application_notes')->nullable()->after('application_source');
            
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('classroom_id')->references('id')->on('classrooms')->onDelete('set null');
            $table->foreign('stream_id')->references('id')->on('streams')->onDelete('set null');
            $table->index('application_status', 'idx_admission_status');
            $table->index('waitlist_position', 'idx_waitlist_position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('online_admissions', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropForeign(['classroom_id']);
            $table->dropForeign(['stream_id']);
            $table->dropIndex('idx_admission_status');
            $table->dropIndex('idx_waitlist_position');
            $table->dropColumn([
                'application_status', 'waitlist_position', 'reviewed_by', 'review_notes',
                'application_date', 'review_date', 'classroom_id', 'stream_id',
                'application_source', 'application_notes'
            ]);
        });
    }
};
