<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Website\NewsletterSubscriber;
use App\Policies\Website\ManagesWebsiteCms;
use Illuminate\View\View;

class NewsletterController extends Controller
{
    use ManagesWebsiteCms;

    public function __construct()
    {
        $this->middleware(fn ($r, $n) => $this->canManageWebsite($r->user()) ? $n($r) : abort(403));
    }

    public function index(): View
    {
        $subscribers = NewsletterSubscriber::query()->latest()->paginate(50);

        return view('website.newsletter.index', compact('subscribers'));
    }
}
