<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Website\WebsiteEvent;
use App\Policies\Website\ManagesWebsiteCms;
use App\Services\Website\WebsiteMediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WebsiteEventController extends Controller
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
        $events = WebsiteEvent::query()->orderByDesc('start_date')->paginate(20);

        return view('website.events.index', compact('events'));
    }

    public function create(): View
    {
        return view('website.events.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:website_events,slug',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'cover_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'location' => 'nullable|string|max:255',
            'registration_enabled' => 'nullable|boolean',
        ]);

        $data = $validated;
        unset($data['cover_image']);

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $this->media->store($request->file('cover_image'), 'events');
        }

        $data['registration_enabled'] = $request->boolean('registration_enabled');

        WebsiteEvent::create($data);

        return redirect()->route('website.events.index')->with('success', 'Event created.');
    }

    public function edit(WebsiteEvent $websiteEvent): View
    {
        return view('website.events.edit', ['event' => $websiteEvent]);
    }

    public function update(Request $request, WebsiteEvent $websiteEvent): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:website_events,slug,'.$websiteEvent->id,
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'cover_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'location' => 'nullable|string|max:255',
            'registration_enabled' => 'nullable|boolean',
        ]);

        $data = $validated;
        unset($data['cover_image']);

        if ($request->hasFile('cover_image')) {
            $this->media->delete($websiteEvent->cover_image);
            $data['cover_image'] = $this->media->store($request->file('cover_image'), 'events');
        }

        $data['registration_enabled'] = $request->boolean('registration_enabled');
        $websiteEvent->update($data);

        return redirect()->route('website.events.index')->with('success', 'Event updated.');
    }

    public function destroy(WebsiteEvent $websiteEvent): RedirectResponse
    {
        $this->media->delete($websiteEvent->cover_image);
        $websiteEvent->delete();

        return redirect()->route('website.events.index')->with('success', 'Event deleted.');
    }
}
