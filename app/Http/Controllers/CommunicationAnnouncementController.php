<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;

class CommunicationAnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::latest()->get();
        return view('communication.announcements.index', compact('announcements'));
    }

    public function create()
    {
        return view('communication.announcements.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'active' => 'required|boolean',
            'expires_at' => 'nullable|date',
        ]);

        Announcement::create($request->all());
        return redirect()->route('announcements.index')->with('success', 'Announcement created successfully.');
    }

    public function editAnnouncement(Announcement $announcement)
    {
        return view('communication.announcements.edit', compact('announcement'));
    }

    public function updateAnnouncement(Request $request, Announcement $announcement)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'active' => 'required|boolean',
            'expires_at' => 'nullable|date',
        ]);

        $announcement->update($request->all());
        return redirect()->route('announcements.index')->with('success', 'Announcement updated successfully.');
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();
        return redirect()->route('announcements.index')->with('success', 'Announcement deleted.');
    }
}
