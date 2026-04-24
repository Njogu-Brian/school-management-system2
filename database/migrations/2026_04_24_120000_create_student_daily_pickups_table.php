<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_daily_pickups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            // No DB FK to users: local DBs often have users.id that is not BIGINT UNSIGNED (imports / older
            // schemas), which makes MySQL errno 150; prod Laravel defaults match foreignId() but dev may not.
            $table->unsignedBigInteger('recorded_by_user_id')->index();
            $table->string('picked_up_by')->nullable();
            $table->enum('direction', ['morning', 'evening', 'both'])->default('evening');
            $table->boolean('skip_evening_trip')->default(true);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('transport_special_assignment_id')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'date']);
            $table->unique(['student_id', 'date', 'direction']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_daily_pickups');
    }
};
