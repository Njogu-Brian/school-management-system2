<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pos_products')) {
            return;
        }

        Schema::table('pos_products', function (Blueprint $table) {
            // Add stock alert fields
            if (!Schema::hasColumn('pos_products', 'allow_overselling')) {
                $table->boolean('allow_overselling')
                    ->default(false)
                    ->after('allow_backorders')
                    ->comment('Allow purchase when out of stock');
            }

            if (!Schema::hasColumn('pos_products', 'oversell_count')) {
                $table->integer('oversell_count')
                    ->default(0)
                    ->after('allow_overselling')
                    ->comment('Number of times oversold');
            }

            if (!Schema::hasColumn('pos_products', 'last_oversell_alert_at')) {
                $table->timestamp('last_oversell_alert_at')
                    ->nullable()
                    ->after('oversell_count')
                    ->comment('Last time admin was alerted about overselling');
            }

            if (!Schema::hasColumn('pos_products', 'is_publicly_visible')) {
                $table->boolean('is_publicly_visible')
                    ->default(false)
                    ->after('is_featured')
                    ->comment('Visible in public online storefront');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('pos_products')) {
            Schema::table('pos_products', function (Blueprint $table) {
                $columns = [
                    'is_publicly_visible',
                    'last_oversell_alert_at',
                    'oversell_count',
                    'allow_overselling'
                ];

                foreach ($columns as $column) {
                    if (Schema::hasColumn('pos_products', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};

