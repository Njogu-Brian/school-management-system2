<?php

namespace Tests\Unit\Services\Pdf;

use App\Services\Pdf\ChunkedDompdfExporter;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ChunkedDompdfExporterTest extends TestCase
{
    public function test_builds_merged_or_zipped_pdf_from_chunks(): void
    {
        $exporter = app(ChunkedDompdfExporter::class);

        $items = collect(range(1, 5));

        $built = $exporter->build($items, function (Collection $chunk) use ($exporter) {
            $lis = $chunk->map(fn ($n) => '<li>Item '.$n.'</li>')->implode('');

            return $exporter->renderHtml(
                '<html><body><h1>Chunk</h1><ul>'.$lis.'</ul></body></html>'
            );
        }, [
            'filename' => 'unit-chunked-export.pdf',
            'chunk_size' => 2,
            'allow_over_sync' => true,
        ]);

        $this->assertFileExists($built['path']);
        $this->assertGreaterThan(100, filesize($built['path']));
        $this->assertContains($built['extension'], ['pdf', 'zip']);
        $this->assertSame(5, $built['item_count']);

        @unlink($built['path']);
        if (!empty($built['cleanup_dir']) && is_dir($built['cleanup_dir'])) {
            foreach (scandir($built['cleanup_dir']) ?: [] as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                @unlink($built['cleanup_dir'].DIRECTORY_SEPARATOR.$item);
            }
            @rmdir($built['cleanup_dir']);
        }
    }

    public function test_rejects_empty_item_sets(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        app(ChunkedDompdfExporter::class)->build([], fn () => '', ['filename' => 'empty.pdf']);
    }
}
