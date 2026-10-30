<?php

namespace App\Console\Commands;

use App\Models\Pos\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FetchRequirementShopImages extends Command
{
    protected $signature = 'pos:fetch-requirement-images
        {--limit=0 : Max products to process (0 = all)}
        {--force : Re-download even if images already set}';

    protected $description = 'Download/attach product images for requirement-linked POS products (placeholder when no source URL).';

    public function handle(): int
    {
        $query = Product::query()
            ->whereNotNull('requirement_type_id')
            ->where('is_active', true)
            ->orderBy('id');

        if (! $this->option('force')) {
            $query->where(function ($q) {
                $q->whereNull('images')->orWhere('images', '[]')->orWhere('images', '');
            });
        }

        $limit = (int) $this->option('limit');
        if ($limit > 0) {
            $query->limit($limit);
        }

        $products = $query->get();
        $this->info('Processing '.$products->count().' products…');

        $dir = 'pos/products';
        Storage::disk('public')->makeDirectory($dir);

        $placeholder = $this->ensurePlaceholder($dir);

        $updated = 0;
        foreach ($products as $product) {
            $sourceUrl = data_get($product->specifications, 'image_source_url');
            $path = $placeholder;

            if (is_string($sourceUrl) && str_starts_with($sourceUrl, 'http')) {
                try {
                    $response = Http::timeout(20)->get($sourceUrl);
                    if ($response->successful()) {
                        $ext = $this->guessExtension($response->header('Content-Type'), $sourceUrl);
                        $filename = Str::slug($product->sku ?: ('product-'.$product->id)).'.'.$ext;
                        Storage::disk('public')->put($dir.'/'.$filename, $response->body());
                        $path = $dir.'/'.$filename;
                    }
                } catch (\Throwable $e) {
                    $this->warn("Failed {$product->name}: {$e->getMessage()}");
                }
            }

            $product->images = [$path];
            $product->save();
            $updated++;
            $this->line("  ✓ {$product->name}");
        }

        $this->info("Updated images on {$updated} products.");

        return self::SUCCESS;
    }

    private function ensurePlaceholder(string $dir): string
    {
        $path = $dir.'/placeholder.png';
        if (Storage::disk('public')->exists($path)) {
            return $path;
        }

        // Minimal 1x1 PNG
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO5XBZsAAAAASUVORK5CYII='
        );
        Storage::disk('public')->put($path, $png);

        return $path;
    }

    private function guessExtension(?string $contentType, string $url): string
    {
        $contentType = strtolower((string) $contentType);
        if (str_contains($contentType, 'jpeg') || str_contains($contentType, 'jpg')) {
            return 'jpg';
        }
        if (str_contains($contentType, 'webp')) {
            return 'webp';
        }
        if (str_contains($contentType, 'gif')) {
            return 'gif';
        }
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true) ? ($ext === 'jpeg' ? 'jpg' : $ext) : 'png';
    }
}
