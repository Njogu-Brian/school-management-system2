<?php

namespace App\Http\Controllers\Api\Website;

use App\Http\Controllers\Controller;
use App\Models\Website\WebsiteEvent;
use App\Models\Website\WebsiteEventRegistration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventRegistrationApiController extends Controller
{
    public function register(Request $request, string $slug): JsonResponse
    {
        $event = WebsiteEvent::where('slug', $slug)->firstOrFail();

        if (! $event->registration_enabled) {
            return response()->json(['success' => false, 'message' => 'Registration is closed for this event.'], 422);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:30',
            'attendees' => 'nullable|integer|min:1|max:20',
        ]);

        $registration = WebsiteEventRegistration::create([
            'website_event_id' => $event->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'attendees' => $validated['attendees'] ?? 1,
            'status' => 'confirmed',
        ]);

        app(\App\Services\Website\WebsiteAnalyticsService::class)->trackConversion(
            'event_registration',
            '/events/'.$slug,
            ['event_id' => $event->id, 'registration_id' => $registration->id]
        );

        return response()->json(['success' => true, 'data' => ['id' => $registration->id]], 201);
    }
}
