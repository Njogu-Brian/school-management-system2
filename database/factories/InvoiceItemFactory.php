<?php

namespace Database\Factories;

use App\Models\InvoiceItem;
use App\Models\Invoice;
use App\Models\Votehead;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceItemFactory extends Factory
{
    protected $model = InvoiceItem::class;

    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'votehead_id' => Votehead::factory(),
            'amount' => fake()->randomFloat(2, 1000, 20000),
            'discount_amount' => 0,
            'status' => 'active',
            'effective_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'source' => fake()->randomElement(['structure', 'optional', 'manual']),
        ];
    }
}

