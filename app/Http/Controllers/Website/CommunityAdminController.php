<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Website\Referral;
use App\Models\Website\PrayerRequest;
use App\Models\Website\AlumniStory;
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
        ]);
    }

    public function approvePrayer(PrayerRequest $prayer)
    {
        $prayer->update(['status' => 'approved']);

        return back()->with('success', 'Prayer request approved.');
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
}
