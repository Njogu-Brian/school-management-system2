<?php

namespace App\Services\Website;

use App\Models\Announcement;
use App\Models\Event;
use App\Models\Student;
use App\Models\StudentExtracurricularActivity;
use App\Models\Academics\Classroom;
use Illuminate\Support\Facades\Cache;

class WebsiteErpIntegrationService
{
    public function liveStats(): array
    {
        return Cache::remember('website.erp.live_stats', 300, function () {
            $activeStudents = Student::query()->count();

            $classrooms = Classroom::query()
                ->where('is_alumni', false)
                ->orderBy('level')
                ->orderBy('name')
                ->get(['id', 'name', 'level', 'academic_group', 'campus']);

            $classStructure = $classrooms->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'level' => $c->level,
                'academic_group' => $c->academic_group,
                'campus' => $c->campus,
                'learners' => Student::query()
                    ->where('classroom_id', $c->id)
                    ->count(),
            ])->values();

            return [
                'total_learners' => $activeStudents,
                'class_structure' => $classStructure,
            ];
        });
    }

    public function announcements(int $limit = 10): array
    {
        return Cache::remember("website.erp.announcements.{$limit}", 120, function () use ($limit) {
            return Announcement::query()
                ->where('active', true)
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->latest()
                ->limit($limit)
                ->get(['id', 'title', 'content', 'expires_at', 'created_at'])
                ->map(fn ($a) => [
                    'id' => $a->id,
                    'title' => $a->title,
                    'content' => $a->content,
                    'expires_at' => $a->expires_at?->toIso8601String(),
                    'published_at' => $a->created_at?->toIso8601String(),
                ])
                ->all();
        });
    }

    public function achievements(int $limit = 12): array
    {
        return Cache::remember("website.erp.achievements.{$limit}", 600, function () use ($limit) {
            return StudentExtracurricularActivity::query()
                ->whereNotNull('award_achievement')
                ->with(['student:id,first_name,last_name,classroom_id', 'student.classroom:id,name'])
                ->latest('achievement_date')
                ->limit($limit)
                ->get()
                ->map(fn ($item) => [
                    'student' => trim(($item->student->first_name ?? '').' '.($item->student->last_name ?? '')),
                    'classroom' => $item->student?->classroom?->name,
                    'award' => $item->award_achievement,
                    'description' => $item->achievement_description,
                    'date' => $item->achievement_date?->toDateString(),
                ])
                ->all();
        });
    }

    public function upcomingErpEvents(int $limit = 6): array
    {
        return Cache::remember("website.erp.events.{$limit}", 300, function () use ($limit) {
            return Event::query()
                ->where('is_active', true)
                ->where('start_date', '>=', now()->toDateString())
                ->orderBy('start_date')
                ->limit($limit)
                ->get(['id', 'title', 'description', 'start_date', 'end_date', 'venue', 'type'])
                ->map(fn ($e) => [
                    'id' => $e->id,
                    'title' => $e->title,
                    'description' => $e->description,
                    'start_date' => $e->start_date?->toDateString(),
                    'end_date' => $e->end_date?->toDateString(),
                    'location' => $e->venue,
                    'type' => $e->type,
                    'source' => 'erp',
                ])
                ->all();
        });
    }
}
