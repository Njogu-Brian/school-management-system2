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

    protected $description = 'Seed default school uniforms (preschool/primary/junior) with sizes and class links.';

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
        $preschoolPrimary = $this->matchGroups($classrooms, [
            'PP1', 'PP2', 'Foundation', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6',
        ]);
        $primary = $this->matchGroups($classrooms, [
            'PP1', 'PP2', 'Foundation', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6',
        ]);
        // Primary-only tracksuit (exclude preschool naming overlap already in list above as requested "primary")
        $primaryOnly = $this->matchGroups($classrooms, [
            'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6',
        ]);
        $junior = $this->matchGroups($classrooms, ['Grade 7', 'Grade 8', 'Grade 9']);
        $primaryAndJunior = $primary->merge($junior)->unique('id')->values();

        $this->line('Preschool & primary: '.$preschoolPrimary->pluck('name')->implode(', ') ?: '(none)');
        $this->line('Primary (G1–6): '.$primaryOnly->pluck('name')->implode(', ') ?: '(none)');
        $this->line('Junior: '.$junior->pluck('name')->implode(', ') ?: '(none)');

        if ($preschoolPrimary->isEmpty() && $junior->isEmpty()) {
            $this->error('No matching classrooms found.');

            return self::FAILURE;
        }

        $catalog = [
            [
                'name' => 'Grey Sweater',
                'color' => 'Grey',
                'sizes' => $this->rangeEven(20, 38),
                'classes' => $preschoolPrimary,
                'selling' => 1500,
            ],
            [
                'name' => 'Black Sweater',
                'color' => 'Black',
                'sizes' => $this->rangeEven(30, 38),
                'classes' => $junior,
                'selling' => 1800,
            ],
            [
                'name' => 'Black Trouser',
                'color' => 'Black',
                'sizes' => $this->rangeEven(30, 38),
                'classes' => $junior,
                'selling' => 1500,
            ],
            [
                'name' => 'Grey Trouser',
                'color' => 'Grey',
                'sizes' => $this->rangeEven(20, 38),
                'classes' => $preschoolPrimary,
                'selling' => 1200,
            ],
            [
                'name' => 'White Shirt',
                'color' => 'White',
                'sizes' => ['XS', 'S', 'M', 'L', 'XL'],
                'classes' => $primaryAndJunior,
                'selling' => 800,
            ],
            [
                'name' => 'Tracksuit',
                'color' => null,
                'sizes' => ['XXS', 'XS', 'S', 'M', 'L', 'XL'],
                'classes' => $primaryOnly->isNotEmpty() ? $primaryOnly : $preschoolPrimary,
                'selling' => 2500,
                'scope' => 'Primary',
            ],
            [
                'name' => 'Tracksuit T-shirt',
                'color' => null,
                'sizes' => ['XXS', 'XS', 'S', 'M', 'L', 'XL'],
                'classes' => $primaryOnly->isNotEmpty() ? $primaryOnly : $preschoolPrimary,
                'selling' => 800,
                'scope' => 'Primary',
            ],
            [
                'name' => 'Tracksuit',
                'color' => null,
                'sizes' => ['S', 'M', 'L', 'XL'],
                'classes' => $junior,
                'selling' => 2800,
                'scope' => 'Junior',
            ],
            [
                'name' => 'Tracksuit T-shirt',
                'color' => null,
                'sizes' => ['S', 'M', 'L', 'XL'],
                'classes' => $junior,
                'selling' => 900,
                'scope' => 'Junior',
            ],
            [
                'name' => 'Socks White',
                'color' => 'White',
                'sizes' => [],
                'classes' => $primaryAndJunior,
                'selling' => 300,
            ],
            [
                'name' => 'Socks Grey',
                'color' => 'Grey',
                'sizes' => [],
                'classes' => $preschoolPrimary,
                'selling' => 300,
            ],
            [
                'name' => 'Socks Black',
                'color' => 'Black',
                'sizes' => [],
                'classes' => $junior,
                'selling' => 300,
            ],
        ];

        foreach ($catalog as $item) {
            $label = $item['name'].(! empty($item['scope']) ? ' ('.$item['scope'].')' : '');
            $this->line(sprintf(
                '  %-28s classes=%d sizes=%s sell=%s',
                $label,
                $item['classes']->count(),
                $item['sizes'] ? implode(',', $item['sizes']) : '—',
                $item['selling']
            ));
        }

        if ($this->option('dry-run')) {
            $this->warn('Dry run — nothing written.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Seed/update these default uniforms?', true)) {
            $this->warn('Cancelled.');

            return self::SUCCESS;
        }

        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($catalog, &$created, &$updated) {
            foreach ($catalog as $item) {
                if ($item['classes']->isEmpty()) {
                    continue;
                }

                $displayName = $item['name'];
                if (! empty($item['scope'])) {
                    $displayName .= ' — '.$item['scope'];
                }

                $product = Product::query()
                    ->where('type', 'uniform')
                    ->where('name', $displayName)
                    ->first();

                $payload = [
                    'name' => $displayName,
                    'type' => 'uniform',
                    'category' => 'Uniform',
                    'brand' => $item['color'],
                    'base_price' => $item['selling'],
                    'cost_price' => null,
                    'stock_quantity' => 0,
                    'min_stock_level' => 0,
                    'track_stock' => true,
                    'allow_backorders' => true,
                    'is_active' => true,
                    'is_publicly_visible' => true,
                    'specifications' => array_filter([
                        'color' => $item['color'],
                        'scope' => $item['scope'] ?? null,
                        'seeded' => 'default_uniforms',
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

                // Rebuild size/color variants for a clean seed
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
                        'value' => (string) $size,
                        'stock_quantity' => 0,
                        'price_adjustment' => 0,
                        'is_active' => true,
                    ]);
                }
            }
        });

        $this->info("Uniforms created: {$created}, updated: {$updated}");
        $this->comment('Selling prices are placeholders — adjust in POS Uniforms as needed. Buying price left empty.');

        return self::SUCCESS;
    }

    private function rangeEven(int $from, int $to): array
    {
        $out = [];
        for ($n = $from; $n <= $to; $n += 2) {
            $out[] = (string) $n;
        }

        return $out;
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
