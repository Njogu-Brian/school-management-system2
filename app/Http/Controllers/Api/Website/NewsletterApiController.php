<?php

namespace App\Http\Controllers\Api\Website;

use App\Http\Controllers\Controller;
use App\Models\Website\NewsletterSubscriber;
use App\Services\Website\NewsletterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewsletterApiController extends Controller
{
    public function __construct(private NewsletterService $newsletter)
    {
    }

    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'source' => 'nullable|string|max:100',
        ]);

        $subscriber = $this->newsletter->subscribe($validated['email'], $validated['source'] ?? 'website');

        return response()->json([
            'message' => 'Thank you for subscribing!',
            'data' => ['email' => $subscriber->email],
        ], 201);
    }
}
