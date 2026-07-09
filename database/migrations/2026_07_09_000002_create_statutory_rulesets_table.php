<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('statutory_rulesets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->boolean('is_default')->default(false)->index();

            /**
             * Generic params to avoid hardcoding volatile statutory changes.
             *
             * Example keys:
             * - personal_relief_monthly
             * - paye_bands: [{min,max,rate}]
             * - nssf: {tier1_max,tier2_max,rate}
             * - shif: {rate,min}
             * - housing_levy: {rate}
             * - taxable_income: {subtract_nssf:true, subtract_shif:true, subtract_nhif:false}
             */
            $table->json('params');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statutory_rulesets');
    }
};

