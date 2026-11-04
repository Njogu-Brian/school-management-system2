<?php

namespace App\Services\Academics\ExamReports;

use App\Models\Academics\Classroom;
use App\Models\Academics\Stream;
use Illuminate\Support\Collection;

/**
 * Resolve parallel classes at the same grade/level for cross-class ranking.
 * Streamed classes are treated as separate sections (e.g. Grade 7 East + West = 2 classes).
 */
final class ParallelClassroomResolver
{
    /**
     * Peer classrooms including the given class (same level / grade band).
     *
     * @return Collection<int, Classroom>
     */
    public function peers(Classroom $classroom): Collection
    {
        $classroom->refresh();

        if (filled($classroom->level)) {
            $peers = Classroom::query()
                ->where('level', $classroom->level)
                ->orderBy('name')
                ->get();

            if ($peers->count() > 1) {
                return $peers;
            }
        }

        $key = $this->gradeKey($classroom);
        if ($key === null) {
            return collect([$classroom]);
        }

        return Classroom::query()
            ->orderBy('name')
            ->get()
            ->filter(fn (Classroom $peer) => $this->gradeKey($peer) === $key)
            ->values();
    }

    /**
     * Peer sections for ranking. A classroom with 2+ streams becomes one section per stream.
     *
     * @return Collection<int, array{classroom: Classroom, stream_id: ?int, label: string, key: string}>
     */
    public function peerSections(Classroom $classroom): Collection
    {
        $sections = collect();

        foreach ($this->peers($classroom) as $peer) {
            $streams = $this->streamsForClassroom($peer);
            if ($streams->count() >= 2) {
                foreach ($streams as $stream) {
                    $sections->push([
                        'classroom' => $peer,
                        'stream_id' => (int) $stream->id,
                        'label' => trim($peer->name.' — '.$stream->name),
                        'key' => $peer->id.':'.$stream->id,
                    ]);
                }
            } else {
                $sections->push([
                    'classroom' => $peer,
                    'stream_id' => null,
                    'label' => (string) $peer->name,
                    'key' => $peer->id.':0',
                ]);
            }
        }

        return $sections->values();
    }

    /**
     * @return Collection<int, Stream>
     */
    public function streamsForClassroom(Classroom $classroom): Collection
    {
        $primary = Stream::query()
            ->where('classroom_id', $classroom->id)
            ->orderBy('name')
            ->get(['id', 'name', 'classroom_id']);

        $pivot = Stream::query()
            ->whereHas('classrooms', fn ($q) => $q->where('classrooms.id', $classroom->id))
            ->orderBy('name')
            ->get(['id', 'name', 'classroom_id']);

        return $primary->concat($pivot)->unique('id')->sortBy('name')->values();
    }

    /**
     * Stable grade key used to group parallel sections (e.g. "grade 7").
     */
    public function gradeKey(Classroom $classroom): ?string
    {
        if (filled($classroom->level)) {
            return strtolower(trim(preg_replace('/\s+/', ' ', (string) $classroom->level)));
        }

        return $this->gradeKeyFromName($classroom->name);
    }

    public function gradeKeyFromName(?string $name): ?string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return null;
        }

        // Match "Grade 4" / "Grade 4A" / "Grade 7 East" — keep the grade, drop section suffix.
        if (preg_match('/\b((?:pp|pre[\s\-]?primary)\s*[12]|creche|baby(?:\s*class)?|nursery|grade\s*\d+|form\s*\d+|class\s*\d+)/i', $name, $m)) {
            $key = strtolower(preg_replace('/\s+/', ' ', trim($m[1])));

            return preg_replace('/^(pp|grade|form|class)(\d+)/', '$1 $2', $key) ?: $key;
        }

        // "7 East" / "7A" style — keep leading grade number.
        if (preg_match('/^(\d{1,2})\s*([a-z]|east|west|north|south|blue|red|green|yellow)?\b/i', $name, $m)) {
            return 'grade '.((int) $m[1]);
        }

        $stripped = preg_replace(
            '/[\s\-_]+(east|west|north|south|blue|red|green|yellow|stream\s*\w+|[a-z])$/i',
            '',
            $name
        );
        $stripped = trim((string) $stripped);

        return $stripped !== '' ? strtolower(preg_replace('/\s+/', ' ', $stripped)) : null;
    }

    public function gradeLabel(Classroom $classroom): string
    {
        if (filled($classroom->level)) {
            return (string) $classroom->level;
        }

        $key = $this->gradeKey($classroom);
        if ($key === null) {
            return (string) $classroom->name;
        }

        return ucwords($key);
    }
}
