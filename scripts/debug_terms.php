<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$termsTotal = App\Models\Term::count();
$currentTerms = App\Models\Term::where('is_current', true)->count();
$latest = App\Models\Term::orderByDesc('id')->first();

echo 'terms_total=' . $termsTotal . PHP_EOL;
echo 'current_terms=' . $currentTerms . PHP_EOL;
if ($latest) {
  echo 'latest_id=' . $latest->id . ' name=' . ($latest->name ?? '') . ' is_current=' . ((int) $latest->is_current) . PHP_EOL;
}

$ctx = App\Support\AcademicContext::forView(null, null, true);
echo 'ctx_years=' . (($ctx['years'] ?? collect())->count()) . PHP_EOL;
echo 'ctx_terms=' . (($ctx['terms'] ?? collect())->count()) . PHP_EOL;
echo 'ctx_selectedYearId=' . (($ctx['selectedYearId'] ?? null) ?? 'null') . PHP_EOL;
echo 'ctx_selectedTermId=' . (($ctx['selectedTermId'] ?? null) ?? 'null') . PHP_EOL;

