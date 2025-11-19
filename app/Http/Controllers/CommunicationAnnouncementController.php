<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;

class CommunicationAnnouncementController extends Controller
{
    
    public function index()
    {
        $query = Announcement::query()
            ->where('active', 1)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now());
            })
            // Order by expires_at if set, otherwise created_at
            ->orderByRaw('COALESCE(expires_at, created_at) DESC');
        
        // Teachers see all active announcements, admins see all
        if (auth()->user()->hasRole('Teacher') || auth()->user()->hasRole('teacher')) {
            $announcements = $query->get();
        } else {
            $announcements = $query->paginate(20);
        }

        return view('communication.announcements.index', compact('announcements'));
    }

    public function store(Request $request)
    {
        // Only admins can create announcements
        if (auth()->user()->hasRole('Teacher') || auth()->user()->hasRole('teacher')) {
            abort(403, 'You do not have permission to create announcements.');
        }

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
        // Only admins can update announcements
        if (auth()->user()->hasRole('Teacher') || auth()->user()->hasRole('teacher')) {
            abort(403, 'You do not have permission to update announcements.');
        }

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
        // Only admins can create announcements
        if (auth()->user()->hasRole('Teacher') || auth()->user()->hasRole('teacher')) {
            abort(403, 'You do not have permission to create announcements.');
        }

        return view('communication.announcements.create');
    }

    public function editAnnouncement(Announcement $announcement)
    {
        // Only admins can edit announcements
        if (auth()->user()->hasRole('Teacher') || auth()->user()->hasRole('teacher')) {
            abort(403, 'You do not have permission to edit announcements.');
        }

        return view('communication.announcements.edit', compact('announcement'));
    }

    public function destroy(Announcement $announcement)
    {
        // Only admins can delete announcements
        if (auth()->user()->hasRole('Teacher') || auth()->user()->hasRole('teacher')) {
            abort(403, 'You do not have permission to delete announcements.');
        }

        $announcement->delete();
        return redirect()->route('announcements.index')->with('success', 'Announcement deleted.');
    }
}
