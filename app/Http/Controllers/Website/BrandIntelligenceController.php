<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Policies\Website\ManagesWebsiteCms;
use App\Services\Website\BrandIntelligenceService;
use Illuminate\View\View;

class BrandIntelligenceController extends Controller
{
    use ManagesWebsiteCms;

    public function __construct()
    {
        $this->middleware(fn ($r, $n) => $this->canManageWebsite($r->user()) ? $n($r) : abort(403));
    }

    public function index(BrandIntelligenceService $intel): View
    {
        return view('website.brand.index', [
            'dashboard' => $intel->dashboard(),
            'recommendations' => $intel->recommendations(),
        ]);
    }
}
