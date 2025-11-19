<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\AcademicYear;
use Illuminate\Http\Request;

class EventCalendarController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->filled('year') 
            ? AcademicYear::find($request->year) 
            : AcademicYear::where('is_active', true)->first();

        $events = Event::where('is_active', true)
            ->when($year, function($q) use ($year) {
                $q->where('academic_year_id', $year->id);
            })
            ->orderBy('start_date')
            ->get();

        $years = AcademicYear::orderByDesc('year')->get();

        return view('events.calendar', compact('events', 'years', 'year'));
    }

    public function create()
    {
        // Only admins can create events
        if (auth()->user()->hasRole('Teacher') || auth()->user()->hasRole('teacher')) {
            abort(403, 'You do not have permission to create events.');
        }

        $years = AcademicYear::orderByDesc('year')->get();
        $classrooms = \App\Models\Academics\Classroom::orderBy('name')->get();
        return view('events.create', compact('years', 'classrooms'));
    }

    public function store(Request $request)
    {
        // Only admins can create events
        if (auth()->user()->hasRole('Teacher') || auth()->user()->hasRole('teacher')) {
            abort(403, 'You do not have permission to create events.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'venue' => 'nullable|string|max:255',
            'type' => 'required|in:academic,sports,cultural,holiday,meeting,other',
            'visibility' => 'required|in:public,staff,students,parents',
            'target_audience' => 'nullable|array',
            'is_all_day' => 'boolean',
            'academic_year_id' => 'nullable|exists:academic_years,id',
        ]);

        $validated['is_all_day'] = $request->filled('is_all_day');
        $validated['is_active'] = true;
        $validated['created_by'] = auth()->id();

        Event::create($validated);

        return redirect()->route('events.index')
            ->with('success', 'Event created successfully.');
    }

    public function show(Event $event)
    {
        $event->load(['academicYear', 'creator']);
        return view('events.show', compact('event'));
    }

    public function edit(Event $event)
    {
        // Only admins can edit events
        if (auth()->user()->hasRole('Teacher') || auth()->user()->hasRole('teacher')) {
            abort(403, 'You do not have permission to edit events.');
        }

        $years = AcademicYear::orderByDesc('year')->get();
        $classrooms = \App\Models\Academics\Classroom::orderBy('name')->get();
        return view('events.edit', compact('event', 'years', 'classrooms'));
    }

    public function update(Request $request, Event $event)
    {
        // Only admins can update events
        if (auth()->user()->hasRole('Teacher') || auth()->user()->hasRole('teacher')) {
            abort(403, 'You do not have permission to update events.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'venue' => 'nullable|string|max:255',
            'type' => 'required|in:academic,sports,cultural,holiday,meeting,other',
            'visibility' => 'required|in:public,staff,students,parents',
            'target_audience' => 'nullable|array',
            'is_all_day' => 'boolean',
            'academic_year_id' => 'nullable|exists:academic_years,id',
        ]);

        $validated['is_all_day'] = $request->filled('is_all_day');

        $event->update($validated);

        return redirect()->route('events.show', $event)
            ->with('success', 'Event updated successfully.');
    }

    public function destroy(Event $event)
    {
        // Only admins can delete events
        if (auth()->user()->hasRole('Teacher') || auth()->user()->hasRole('teacher')) {
            abort(403, 'You do not have permission to delete events.');
        }

        $event->delete();
        return redirect()->route('events.index')
            ->with('success', 'Event deleted successfully.');
    }

    public function api(Request $request)
    {
        $start = $request->get('start');
        $end = $request->get('end');

        $events = Event::where('is_active', true)
            ->whereBetween('start_date', [$start, $end])
            ->get()
            ->map(function($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'start' => $event->is_all_day 
                        ? $event->start_date->format('Y-m-d')
                        : $event->start_date->format('Y-m-d') . 'T' . ($event->start_time ? date('H:i:s', strtotime($event->start_time)) : '00:00:00'),
                    'end' => $event->end_date 
                        ? ($event->is_all_day 
                            ? $event->end_date->format('Y-m-d')
                            : $event->end_date->format('Y-m-d') . 'T' . ($event->end_time ? date('H:i:s', strtotime($event->end_time)) : '23:59:59'))
                        : null,
                    'allDay' => $event->is_all_day,
                    'color' => $this->getEventColor($event->type),
                ];
            });

        return response()->json($events);
    }

    protected function getEventColor($type)
    {
        return match($type) {
            'academic' => '#007bff',
            'sports' => '#28a745',
            'cultural' => '#ffc107',
            'holiday' => '#dc3545',
            'meeting' => '#6c757d',
            default => '#17a2b8',
        };
    }
}
