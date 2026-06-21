<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Website\StudentSpotlight;
use App\Models\Website\WebsiteCompetition;
use App\Policies\Website\ManagesWebsiteCms;
use Illuminate\Http\Request;

class StudentSpotlightController extends Controller
{
    use ManagesWebsiteCms;

    public function __construct()
    {
        $this->middleware(fn ($r, $n) => $this->canManageWebsite($r->user()) ? $n($r) : abort(403));
    }

    public function index()
    {
        $spotlights = StudentSpotlight::with('student')->latest()->paginate(20);
        $competitions = WebsiteCompetition::latest()->paginate(20);

        return view('website.showcase.index', compact('spotlights', 'competitions'));
    }

    public function storeSpotlight(Request $request)
    {
        $data = $request->validate([
            'student_id' => 'nullable|exists:students,id',
            'title' => 'required|string|max:255',
            'story' => 'nullable|string',
            'achievement' => 'nullable|string|max:255',
            'featured' => 'nullable|boolean',
            'published' => 'nullable|boolean',
        ]);

        StudentSpotlight::create($data);

        return back()->with('success', 'Spotlight created.');
    }

    public function storeCompetition(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date' => 'nullable|date',
            'location' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:100',
            'result' => 'nullable|string|max:255',
        ]);

        WebsiteCompetition::create($data);

        return back()->with('success', 'Competition recorded.');
    }
}
