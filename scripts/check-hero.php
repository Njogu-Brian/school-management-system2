<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$rows = App\Models\Website\PageSection::query()
    ->where('section_key', 'hero_main')
    ->get(['id', 'is_active', 'title', 'subtitle', 'content', 'updated_at']);

foreach ($rows as $row) {
    echo $row->id.' active='.(int) $row->is_active.' '.$row->title.' '.$row->updated_at.PHP_EOL;
    echo substr((string) $row->content, 0, 180).PHP_EOL;
}
