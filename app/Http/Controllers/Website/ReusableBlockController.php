<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Website\ReusableBlock;
use App\Policies\Website\ManagesWebsiteCms;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReusableBlockController extends Controller
{
    use ManagesWebsiteCms;

    public function __construct()
    {
        $this->middleware(fn ($r, $n) => $this->canManageWebsite($r->user()) ? $n($r) : abort(403));
    }

    public function index(): View
    {
        $blocks = ReusableBlock::query()->latest()->paginate(20);

        return view('website.blocks.index', compact('blocks'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'block_type' => 'required|string|max:100',
            'content' => 'nullable|string',
        ]);

        ReusableBlock::create($validated);

        return back()->with('success', 'Block created.');
    }
}
