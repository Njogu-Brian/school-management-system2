<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DocumentCounter;

class DocumentCounterSeeder extends Seeder
{
    public function run(): void
    {
        $counters = [
            [
                'type' => 'invoice',
                'prefix' => 'INV',
                'suffix' => '',
                'padding_length' => 5,
                'next_number' => 1,
                'reset_period' => 'yearly',
            ],
            [
                'type' => 'receipt',
                'prefix' => 'RCPT',
                'suffix' => '',
                'padding_length' => 6,
                'next_number' => 1,
                'reset_period' => 'yearly',
            ],
            [
                'type' => 'credit_note',
                'prefix' => 'CN',
                'suffix' => '',
                'padding_length' => 5,
                'next_number' => 1,
                'reset_period' => 'never',
            ],
            [
                'type' => 'debit_note',
                'prefix' => 'DN',
                'suffix' => '',
                'padding_length' => 5,
                'next_number' => 1,
                'reset_period' => 'never',
            ],
        ];

        foreach ($counters as $counter) {
            DocumentCounter::updateOrCreate(
                ['type' => $counter['type']],
                $counter
            );
        }
    }
}
