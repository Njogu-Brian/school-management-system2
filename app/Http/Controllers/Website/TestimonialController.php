<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Website\Testimonial;
use App\Policies\Website\ManagesWebsiteCms;
use App\Services\Website\WebsiteMediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TestimonialController extends Controller
{
    use ManagesWebsiteCms;

    public function __construct(private WebsiteMediaService $media)
    {
        $this->middleware(function ($request, $next) {
            abort_unless($this->canManageWebsite($request->user()), 403);

            return $next($request);
        });
    }

    public function index(): View
    {
        $testimonials = Testimonial::query()->latest()->paginate(20);

        return view('website.testimonials.index', compact('testimonials'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'relationship' => 'nullable|string|max:255',
            'message' => 'required|string|max:5000',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'video_url' => 'nullable|url|max:500',
            'featured' => 'nullable|boolean',
            'approved' => 'nullable|boolean',
        ]);

        $photo = null;
        if ($request->hasFile('photo')) {
            $photo = $this->media->store($request->file('photo'), 'testimonials');
        }

        Testimonial::create([
            ...$validated,
            'photo' => $photo,
            'featured' => $request->boolean('featured'),
            'approved' => $request->boolean('approved'),
        ]);

        return back()->with('success', 'Testimonial added.');
    }

    public function update(Request $request, Testimonial $testimonial): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'relationship' => 'nullable|string|max:255',
            'message' => 'required|string|max:5000',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'video_url' => 'nullable|url|max:500',
            'featured' => 'nullable|boolean',
            'approved' => 'nullable|boolean',
        ]);

        $data = $validated;
        unset($data['photo']);

        if ($request->hasFile('photo')) {
            $this->media->delete($testimonial->photo);
            $data['photo'] = $this->media->store($request->file('photo'), 'testimonials');
        }

        $data['featured'] = $request->boolean('featured');
        $data['approved'] = $request->boolean('approved');

        $testimonial->update($data);

        return back()->with('success', 'Testimonial updated.');
    }

    public function destroy(Testimonial $testimonial): RedirectResponse
    {
        $this->media->delete($testimonial->photo);
        $testimonial->delete();

        return back()->with('success', 'Testimonial deleted.');
    }
}
