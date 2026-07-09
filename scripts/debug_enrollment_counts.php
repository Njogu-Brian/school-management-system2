<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$year = (int) (setting('current_year') ?? date('Y'));
$term = (int) (get_current_term_number() ?? 1);

$base = App\Models\Student::query()->where('archive', 0)->where('is_alumni', false);

echo 'current_year=' . $year . ' term=' . $term . PHP_EOL;
echo 'active_all=' . $base->count() . PHP_EOL;
echo 'active_for_term=' . (clone $base)->activeForCurrentTerm($year, $term)->count() . PHP_EOL;
echo 'future_term_only=' . (clone $base)->where('enrollment_year', $year)->where('enrollment_term', '>', $term)->count() . PHP_EOL;

