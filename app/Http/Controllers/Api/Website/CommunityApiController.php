<?php

namespace App\Http\Controllers\Api\Website;

use App\Http\Controllers\Controller;
use App\Models\Website\AlumniStory;
use App\Models\Website\FamilyStory;
use App\Models\Website\PrayerRequest;
use App\Models\Website\Referral;
use App\Models\Website\Testimonial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommunityApiController extends Controller
{
    public function index(): JsonResponse
    {
        $videoTestimonials = Testimonial::query()
            ->where('approved', true)
            ->whereNotNull('video_url')
            ->latest()
            ->limit(12)
            ->get(['id', 'name', 'relationship', 'message', 'video_url', 'photo']);

        $alumni = AlumniStory::query()
            ->where('published', true)
            ->latest()
            ->limit(12)
            ->get();

        $prayers = PrayerRequest::query()
            ->where('is_public', true)
            ->where('status', 'approved')
            ->orderByDesc('featured')
            ->latest()
            ->limit(20)
            ->get(['id', 'name', 'request', 'is_anonymous', 'featured', 'answered', 'answered_testimony', 'created_at']);

        $families = FamilyStory::query()
            ->where('published', true)
            ->orderByDesc('featured')
            ->latest()
            ->limit(12)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'video_testimonials' => $videoTestimonials,
                'alumni_stories' => $alumni,
                'prayer_wall' => $prayers,
                'family_stories' => $families,
            ],
        ]);
    }

    public function submitReferral(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'referrer_name' => 'required|string|max:255',
            'referrer_phone' => 'required|string|max:30',
            'referrer_email' => 'nullable|email',
            'referred_name' => 'required|string|max:255',
            'referred_phone' => 'nullable|string|max:30',
            'referred_email' => 'nullable|email',
        ]);

        $referral = Referral::create($validated);

        return response()->json(['success' => true, 'data' => $referral], 201);
    }

    public function submitPrayer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'request' => 'required|string|max:2000',
            'is_anonymous' => 'nullable|boolean',
            'is_public' => 'nullable|boolean',
        ]);

        $prayer = PrayerRequest::create([
            ...$validated,
            'status' => 'pending',
            'is_public' => $validated['is_public'] ?? false,
        ]);

        return response()->json(['success' => true, 'data' => ['id' => $prayer->id]], 201);
    }
}
