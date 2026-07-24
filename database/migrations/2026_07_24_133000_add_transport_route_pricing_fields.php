<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drop_off_points', function (Blueprint $table) {
            if (!Schema::hasColumn('drop_off_points', 'two_way_amount')) {
                $table->decimal('two_way_amount', 12, 2)->nullable()->after('name');
            }
            if (!Schema::hasColumn('drop_off_points', 'one_way_amount')) {
                $table->decimal('one_way_amount', 12, 2)->nullable()->after('two_way_amount');
            }
        });

        Schema::table('transport_fees', function (Blueprint $table) {
            if (!Schema::hasColumn('transport_fees', 'pricing_mode')) {
                $table->string('pricing_mode', 32)->default('calculated')->after('amount');
            }
            if (!Schema::hasColumn('transport_fees', 'pricing_breakdown')) {
                $table->json('pricing_breakdown')->nullable()->after('pricing_mode');
            }
        });

        $exists = DB::table('drop_off_points')
            ->whereRaw('UPPER(name) = ?', ['OWN MEANS'])
            ->exists();

        if (!$exists) {
            DB::table('drop_off_points')->insert([
                'name' => 'OWN MEANS',
                'two_way_amount' => 0,
                'one_way_amount' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('drop_off_points')
                ->whereRaw('UPPER(name) = ?', ['OWN MEANS'])
                ->update([
                    'two_way_amount' => 0,
                    'one_way_amount' => 0,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('transport_fees', function (Blueprint $table) {
            if (Schema::hasColumn('transport_fees', 'pricing_breakdown')) {
                $table->dropColumn('pricing_breakdown');
            }
            if (Schema::hasColumn('transport_fees', 'pricing_mode')) {
                $table->dropColumn('pricing_mode');
            }
        });

        Schema::table('drop_off_points', function (Blueprint $table) {
            if (Schema::hasColumn('drop_off_points', 'one_way_amount')) {
                $table->dropColumn('one_way_amount');
            }
            if (Schema::hasColumn('drop_off_points', 'two_way_amount')) {
                $table->dropColumn('two_way_amount');
            }
        });
    }
};
