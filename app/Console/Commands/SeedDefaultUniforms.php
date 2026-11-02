<?php

namespace App\Console\Commands;

use App\Models\Academics\Classroom;
use App\Models\Pos\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeedDefaultUniforms extends Command
{
    protected $signature = 'pos:seed-default-uniforms
        {--force : Skip confirmation}
        {--dry-run : Preview only}';

    protected $description = 'Seed school uniform selling prices (primary+junior sized list, junior-high flat list). Stock starts at 0.';

    public function handle(): int
    {
        if (! Schema::hasTable('pos_products')) {
            $this->error('pos_products table missing.');

            return self::FAILURE;
        }

        $classrooms = Classroom::query()
            ->withoutGlobalScope('offered')
            ->orderBy('name')
            ->get(['id', 'name']);

        $primary = $this->matchGroups($classrooms, [
            'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6',
        ]);
        $junior = $this->matchGroups($classrooms, ['Grade 7', 'Grade 8', 'Grade 9']);
        $primaryAndJunior = $primary->merge($junior)->unique('id')->values();

        $this->line('Primary (G1–6): '.$primary->pluck('name')->implode(', ') ?: '(none)');
        $this->line('Junior High (G7–9): '.$junior->pluck('name')->implode(', ') ?: '(none)');

        if ($primaryAndJunior->isEmpty() && $junior->isEmpty()) {
            $this->error('No matching classrooms found.');

            return self::FAILURE;
        }

        // Two-column sized list → Primary + Junior High
        // One-column flat list → Junior High only
        $catalog = array_merge(
            $this->primaryAndJuniorCatalog($primaryAndJunior),
            $this->juniorHighCatalog($junior),
        );

        foreach ($catalog as $item) {
            $sizeLabel = empty($item['sizes'])
                ? '—'
                : collect($item['sizes'])->map(fn ($s) => $s['value'].'@'.$s['price'])->implode(',');
            $this->line(sprintf(
                '  %-36s classes=%d sell=%s sizes=%s',
                $item['name'],
                $item['classes']->count(),
                $item['selling'],
                $sizeLabel
            ));
        }

        if ($this->option('dry-run')) {
            $this->warn('Dry run — nothing written.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Seed/update these uniforms (stock 0)?', true)) {
            $this->warn('Cancelled.');

            return self::SUCCESS;
        }

        $created = 0;
        $updated = 0;
        $deactivated = 0;
        $keepNames = collect($catalog)->pluck('name')->all();

        DB::transaction(function () use ($catalog, $keepNames, &$created, &$updated, &$deactivated) {
            // Retire previous placeholder default uniforms not on the printed price lists.
            $old = Product::query()
                ->where('type', 'uniform')
                ->where('is_active', true)
                ->get();

            foreach ($old as $product) {
                $seeded = data_get($product->specifications, 'seeded');
                if (in_array($seeded, ['default_uniforms', 'uniform_price_list'], true)
                    && ! in_array($product->name, $keepNames, true)
                ) {
                    $product->update(['is_active' => false, 'is_publicly_visible' => false]);
                    $deactivated++;
                }
            }

            foreach ($catalog as $item) {
                if ($item['classes']->isEmpty()) {
                    continue;
                }

                $product = Product::query()
                    ->where('type', 'uniform')
                    ->where('name', $item['name'])
                    ->first();

                $payload = [
                    'name' => $item['name'],
                    'type' => 'uniform',
                    'category' => 'Uniform',
                    'brand' => $item['color'] ?? null,
                    'base_price' => $item['selling'],
                    'cost_price' => null,
                    'stock_quantity' => 0,
                    'min_stock_level' => 0,
                    'track_stock' => true,
                    'allow_backorders' => true,
                    'is_active' => true,
                    'is_publicly_visible' => true,
                    'specifications' => array_filter([
                        'color' => $item['color'] ?? null,
                        'scope' => $item['scope'] ?? null,
                        'seeded' => 'uniform_price_list',
                    ]),
                ];

                if ($product) {
                    $product->fill($payload)->save();
                    $updated++;
                } else {
                    $product = Product::create($payload);
                    $created++;
                }

                $product->classrooms()->sync($item['classes']->pluck('id')->all());
                $product->variants()->delete();

                if (! empty($item['color'])) {
                    $product->variants()->create([
                        'variant_type' => 'color',
                        'name' => 'Colour',
                        'value' => $item['color'],
                        'stock_quantity' => 0,
                        'price_adjustment' => 0,
                        'is_active' => true,
                    ]);
                }

                foreach ($item['sizes'] as $size) {
                    $product->variants()->create([
                        'variant_type' => 'size',
                        'name' => 'Size',
                        'value' => (string) $size['value'],
                        'stock_quantity' => 0,
                        'price_adjustment' => ((float) $size['price']) - ((float) $item['selling']),
                        'is_active' => true,
                    ]);
                }
            }
        });

        $this->info("Uniforms created: {$created}, updated: {$updated}, deactivated old: {$deactivated}");
        $this->comment('All stock set to 0. Buying price left empty.');

        return self::SUCCESS;
    }

    /**
     * Sized price list (two-column sheet) — Primary + Junior High.
     */
    private function primaryAndJuniorCatalog($classes): array
    {
        return [
            $this->item('Shirt', $classes, 'Primary & Junior', null, [
                ['value' => 'XS', 'price' => 600],
                ['value' => 'S', 'price' => 600],
                ['value' => 'M', 'price' => 700],
                ['value' => 'L', 'price' => 700],
                ['value' => 'XL', 'price' => 800],
            ]),
            $this->item('Tracksuit with T-shirt', $classes, 'Primary & Junior', null, [
                ['value' => 'XXS', 'price' => 1800],
                ['value' => 'XS', 'price' => 1800],
                ['value' => 'S', 'price' => 2000],
                ['value' => 'M', 'price' => 2000],
                ['value' => 'L', 'price' => 2200],
                ['value' => 'XL', 'price' => 2200],
            ]),
            $this->item('Dress', $classes, 'Primary & Junior', null, [
                ['value' => '22', 'price' => 800],
                ['value' => '24', 'price' => 800],
                ['value' => '26', 'price' => 900],
                ['value' => '28', 'price' => 900],
                ['value' => '30', 'price' => 1000],
                ['value' => '32', 'price' => 1000],
                ['value' => '34', 'price' => 1200],
                ['value' => '36', 'price' => 1200],
            ]),
            $this->item('Socks', $classes, 'Primary & Junior', null, [], 200),
            $this->item('Bow Tie', $classes, 'Primary & Junior', null, [], 200),
            $this->item('Tie', $classes, 'Primary & Junior', null, [], 200),
            $this->item('Marvin', $classes, 'Primary & Junior', null, [], 300),
            $this->item('Trouser', $classes, 'Primary & Junior', null, [
                ['value' => '22', 'price' => 600],
                ['value' => '24', 'price' => 600],
                ['value' => '26', 'price' => 650],
                ['value' => '28', 'price' => 650],
                ['value' => '30', 'price' => 700],
                ['value' => '32', 'price' => 700],
                ['value' => '34', 'price' => 750],
                ['value' => '36', 'price' => 750],
            ]),
            $this->item('Sweater', $classes, 'Primary & Junior', null, [
                ['value' => '22', 'price' => 800],
                ['value' => '24', 'price' => 800],
                ['value' => '26', 'price' => 850],
                ['value' => '28', 'price' => 850],
                ['value' => '30', 'price' => 900],
                ['value' => '32', 'price' => 900],
                ['value' => '34', 'price' => 1000],
                ['value' => '36', 'price' => 1000],
            ]),
            $this->item('Fleece Jacket', $classes, 'Primary & Junior', null, [
                ['value' => '22', 'price' => 2000],
                ['value' => '24', 'price' => 2000],
                ['value' => '26', 'price' => 2100],
                ['value' => '28', 'price' => 2100],
                ['value' => '30', 'price' => 2200],
                ['value' => '32', 'price' => 2200],
                ['value' => '34', 'price' => 2400],
                ['value' => '36', 'price' => 2400],
            ]),
        ];
    }

    /**
     * Flat one-column price list — Junior High only.
     * Trouser uses handwritten correction 1000 (printed 750 crossed out).
     */
    private function juniorHighCatalog($classes): array
    {
        return [
            $this->item('Shirt', $classes, 'Junior High', null, [], 800),
            $this->item('Trouser', $classes, 'Junior High', null, [], 1000),
            $this->item('Skirt', $classes, 'Junior High', null, [], 900),
            $this->item('Sweater', $classes, 'Junior High', null, [], 1000),
            $this->item('Half Sweater', $classes, 'Junior High', null, [], 900),
            $this->item('Blazer', $classes, 'Junior High', null, [], 3500),
            $this->item('Tracksuit', $classes, 'Junior High', null, [], 1600),
            $this->item('Tracksuit T-shirt', $classes, 'Junior High', null, [], 600),
            $this->item('Fleece Jacket', $classes, 'Junior High', null, [], 2400),
            $this->item('Bow Tie & Tie', $classes, 'Junior High', null, [], 250),
            $this->item('Socks', $classes, 'Junior High', null, [], 250),
        ];
    }

    private function item(string $name, $classes, string $scope, ?string $color, array $sizes, ?float $flatPrice = null): array
    {
        $displayName = $name.' — '.$scope;
        $selling = $flatPrice ?? (empty($sizes) ? 0 : min(array_column($sizes, 'price')));

        return [
            'name' => $displayName,
            'scope' => $scope,
            'color' => $color,
            'classes' => $classes,
            'selling' => $selling,
            'sizes' => $sizes,
        ];
    }

    private function matchGroups($classrooms, array $labels)
    {
        $matched = collect();
        foreach ($labels as $label) {
            $targets = [$this->normalize($label)];
            foreach ($this->aliases($label) as $alias) {
                $targets[] = $this->normalize($alias);
            }
            $targets = array_values(array_unique($targets));

            $hits = $classrooms->filter(function ($c) use ($targets) {
                $norm = $this->normalize((string) $c->name);
                foreach ($targets as $target) {
                    if ($norm === $target) {
                        return true;
                    }
                    if (str_starts_with($norm, $target)) {
                        $next = substr($norm, strlen($target), 1);

                        return $next === '' || ! ctype_digit($next);
                    }
                }

                return false;
            });

            $matched = $matched->merge($hits);
        }

        return $matched->unique('id')->values();
    }

    private function aliases(string $label): array
    {
        return match (strtoupper(trim($label))) {
            'PP1' => ['PP1', 'PP 1', 'Pre Primary 1', 'Pre-Primary 1'],
            'PP2' => ['PP2', 'PP 2', 'Pre Primary 2', 'Pre-Primary 2'],
            'FOUNDATION' => ['Foundation', 'Foundation Class'],
            default => [$label],
        };
    }

    private function normalize(string $name): string
    {
        return strtoupper((string) preg_replace('/[^A-Za-z0-9]+/', '', $name));
    }
}
