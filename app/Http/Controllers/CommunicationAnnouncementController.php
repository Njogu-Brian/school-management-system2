<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;

class CommunicationAnnouncementController extends Controller
{
    
    public function index()
    {
        $announcements = Announcement::query()
            ->where('active', 1)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now());
            })
            // Order by expires_at if set, otherwise created_at
            ->orderByRaw('COALESCE(expires_at, created_at) DESC')
            ->take(5)
            ->get();

        return view('communication.announcements.index', compact('announcements'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'      => 'required|string|max:255',
            'content'    => 'required|string',
            'active'     => 'required|boolean',
            'expires_at' => 'nullable|date',
        ]);

        Announcement::create($request->only(['title','content','active','expires_at']));
        return redirect()->route('announcements.index')->with('success', 'Announcement created successfully.');
    }

    public function updateAnnouncement(Request $request, Announcement $announcement)
    {
        $request->validate([
            'title'      => 'required|string|max:255',
            'content'    => 'required|string',
            'active'     => 'required|boolean',
            'expires_at' => 'nullable|date',
        ]);

        $announcement->update($request->only(['title','content','active','expires_at']));
        return redirect()->route('announcements.index')->with('success', 'Announcement updated successfully.');
    }

    public function create()
    {
        return view('communication.announcements.create');
    }

    public function editAnnouncement(Announcement $announcement)
    {
        return view('communication.announcements.edit', compact('announcement'));
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();
        return redirect()->route('announcements.index')->with('success', 'Announcement deleted.');
    }
}
