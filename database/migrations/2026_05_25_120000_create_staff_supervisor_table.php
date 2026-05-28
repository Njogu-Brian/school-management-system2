<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('staff_supervisor')) {
            return;
        }

        Schema::create('staff_supervisor', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id');
            $table->unsignedBigInteger('supervisor_staff_id');
            $table->timestamps();

            $table->foreign('staff_id')->references('id')->on('staff')->cascadeOnDelete();
            $table->foreign('supervisor_staff_id')->references('id')->on('staff')->cascadeOnDelete();
            $table->unique(['staff_id', 'supervisor_staff_id']);
        });

        // Backfill pivot from legacy single supervisor_id
        if (Schema::hasColumn('staff', 'supervisor_id')) {
            $rows = \DB::table('staff')
                ->whereNotNull('supervisor_id')
                ->select('id as staff_id', 'supervisor_id as supervisor_staff_id')
                ->get();

            foreach ($rows as $row) {
                if ((int) $row->staff_id === (int) $row->supervisor_staff_id) {
                    continue;
                }
                \DB::table('staff_supervisor')->insertOrIgnore([
                    'staff_id' => $row->staff_id,
                    'supervisor_staff_id' => $row->supervisor_staff_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_supervisor');
    }
};
