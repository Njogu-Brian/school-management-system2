<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$current = App\Models\Term::where('is_current', true)->orderByDesc('id')->first();
echo 'current_term_id=' . ($current?->id ?? 'null') . PHP_EOL;
echo 'current_term_name=' . (($current?->name ?? '') === '' ? '(empty)' : $current?->name) . PHP_EOL;

$sample = App\Models\Term::query()->orderByDesc('academic_year_id')->orderBy('opening_date')->orderBy('id')->limit(6)->get();
foreach ($sample as $t) {
  $n = trim((string) ($t->name ?? ''));
  echo 'term_id=' . $t->id
    . ' ay=' . ($t->academic_year_id ?? '')
    . ' is_current=' . ((int) ($t->is_current ?? 0))
    . ' name=' . ($n === '' ? '(empty)' : $n)
    . PHP_EOL;
}

