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
        if (!Schema::hasTable('transport_special_assignments')) {
            Schema::create('transport_special_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->nullable()->constrained('students')->onDelete('cascade');
                $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->onDelete('cascade');
                $table->foreignId('trip_id')->nullable()->constrained('trips')->onDelete('set null');
                $table->foreignId('drop_off_point_id')->nullable()->constrained('drop_off_points')->onDelete('set null');
                $table->enum('assignment_type', ['student_specific', 'vehicle_wide'])->default('student_specific');
                $table->enum('transport_mode', ['vehicle', 'trip', 'own_means'])->default('vehicle');
                $table->date('start_date');
                $table->date('end_date')->nullable(); // NULL = indefinite
                $table->text('reason')->nullable();
                $table->enum('status', ['pending', 'active', 'expired', 'cancelled'])->default('pending');
                $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
            });
        }

        // Add indexes if they don't exist (table already exists from previous partial migration)
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();
        
        Schema::table('transport_special_assignments', function (Blueprint $table) use ($connection, $databaseName) {
            // Check if index exists before creating
            $indexes = $connection->select(
                "SELECT index_name FROM information_schema.statistics 
                 WHERE table_schema = ? AND table_name = 'transport_special_assignments'",
                [$databaseName]
            );
            $existingIndexes = array_column($indexes, 'index_name');
            
            if (!in_array('tsa_student_dates_idx', $existingIndexes)) {
                $table->index(['student_id', 'start_date', 'end_date'], 'tsa_student_dates_idx');
            }
            if (!in_array('tsa_vehicle_dates_idx', $existingIndexes)) {
                $table->index(['vehicle_id', 'start_date', 'end_date'], 'tsa_vehicle_dates_idx');
            }
            if (!in_array('tsa_status_idx', $existingIndexes)) {
                $table->index('status', 'tsa_status_idx');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transport_special_assignments');
    }
};
