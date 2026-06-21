<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Website\Enquiry;
use App\Policies\Website\ManagesWebsiteCms;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EnquiryController extends Controller
{
    use ManagesWebsiteCms;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless($this->canManageWebsite($request->user()), 403);

            return $next($request);
        });
    }

    public function index(Request $request): View
    {
        $query = Enquiry::query()->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $enquiries = $query->paginate(25);

        return view('website.enquiries.index', compact('enquiries'));
    }

    public function show(Enquiry $enquiry): View
    {
        return view('website.enquiries.show', compact('enquiry'));
    }

    public function updateStatus(Request $request, Enquiry $enquiry): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:'.implode(',', Enquiry::statuses()),
        ]);

        $enquiry->update($validated);

        return back()->with('success', 'Enquiry status updated.');
    }
}
