<?php

use App\Models\Website\WebsiteBrandItem;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Cache;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$arts = 'https://royalkingsschools.sc.ke/assets/images/441539411-1116959156184234-4545625582975216690-n-1020x765.jpg';

foreach (WebsiteBrandItem::query()->where('block_type', 'cocurricular')->where('title', 'Ballet')->get() as $item) {
    $item->image_url = $arts;
    $item->save();
    echo "Ballet image updated\n";
}

foreach (WebsiteBrandItem::query()->where('block_type', 'cocurricular')->whereIn('title', ['Robotics', 'Music'])->get() as $item) {
    if (str_contains((string) $item->image_url, 'career-day')) {
        $item->image_url = null;
        $item->save();
        echo "Cleared career-day image from {$item->title}\n";
    }
}

Cache::flush();
echo "Done\n";
