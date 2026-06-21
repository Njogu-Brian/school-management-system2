<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Website\Referral;
use App\Models\Website\PrayerRequest;
use App\Models\Website\AlumniStory;
use App\Models\Website\FamilyStory;
use App\Policies\Website\ManagesWebsiteCms;
use Illuminate\Http\Request;

class CommunityAdminController extends Controller
{
    use ManagesWebsiteCms;

    public function __construct()
    {
        $this->middleware(fn ($r, $n) => $this->canManageWebsite($r->user()) ? $n($r) : abort(403));
    }

    public function index()
    {
        return view('website.community.index', [
            'referrals' => Referral::latest()->limit(50)->get(),
            'prayers' => PrayerRequest::latest()->limit(50)->get(),
            'alumni' => AlumniStory::latest()->paginate(20),
            'families' => FamilyStory::latest()->paginate(20),
        ]);
    }

    public function approvePrayer(PrayerRequest $prayer)
    {
        $prayer->update(['status' => 'approved']);

        return back()->with('success', 'Prayer request approved.');
    }

    public function featurePrayer(Request $request, PrayerRequest $prayer)
    {
        $prayer->update(['featured' => $request->boolean('featured')]);

        return back()->with('success', 'Prayer featured status updated.');
    }

    public function markPrayerAnswered(Request $request, PrayerRequest $prayer)
    {
        $validated = $request->validate(['answered_testimony' => 'nullable|string']);
        $prayer->update([
            'answered' => true,
            'answered_testimony' => $validated['answered_testimony'] ?? $prayer->answered_testimony,
        ]);

        return back()->with('success', 'Prayer marked as answered.');
    }

    public function storeAlumni(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'graduation_year' => 'nullable|string|max:20',
            'headline' => 'required|string|max:255',
            'story' => 'required|string',
            'published' => 'nullable|boolean',
        ]);

        AlumniStory::create($data);

        return back()->with('success', 'Alumni story added.');
    }

    public function storeFamilyStory(Request $request)
    {
        $data = $request->validate([
            'family_name' => 'required|string|max:255',
            'story' => 'required|string',
            'cover_image' => 'nullable|string|max:500',
            'published' => 'nullable|boolean',
            'featured' => 'nullable|boolean',
        ]);

        FamilyStory::create([
            ...$data,
            'published' => $request->boolean('published', false),
            'featured' => $request->boolean('featured', false),
        ]);

        return back()->with('success', 'Family story added.');
    }
}
