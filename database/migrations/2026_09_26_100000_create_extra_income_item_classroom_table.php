<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extra_income_item_classroom', function (Blueprint $table) {
            $table->id();
            $table->foreignId('extra_income_item_id')->constrained('extra_income_items')->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['extra_income_item_id', 'classroom_id'], 'extra_income_classroom_unique');
        });

        if (Schema::hasTable('extra_income_items')) {
            $rows = DB::table('extra_income_items')
                ->whereNotNull('classroom_id')
                ->get(['id', 'classroom_id', 'created_at', 'updated_at']);

            foreach ($rows as $row) {
                DB::table('extra_income_item_classroom')->insertOrIgnore([
                    'extra_income_item_id' => $row->id,
                    'classroom_id' => $row->classroom_id,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('extra_income_item_classroom');
    }
};
