<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Academics\CBCStrand;
use App\Models\Academics\CBCSubstrand;
use App\Models\Academics\LearningArea;
use Illuminate\Http\Request;

class ApiCbcController extends Controller
{
    public function learningAreas(Request $request)
    {
        $areas = LearningArea::query()
            ->active()
            ->ordered()
            ->withCount('strands')
            ->get()
            ->map(fn (LearningArea $a) => [
                'id' => $a->id,
                'code' => $a->code,
                'name' => $a->name,
                'description' => $a->description,
                'level_category' => $a->level_category,
                'levels' => $a->levels,
                'is_core' => (bool) $a->is_core,
                'strands_count' => $a->strands_count,
            ])
            ->values();

        return response()->json(['success' => true, 'data' => $areas]);
    }

    public function strands(Request $request)
    {
        $query = CBCStrand::query()
            ->where('is_active', true)
            ->with('learningArea')
            ->withCount('substrands')
            ->orderBy('display_order')
            ->orderBy('name');

        if ($request->filled('learning_area_id')) {
            $query->where('learning_area_id', (int) $request->input('learning_area_id'));
        }

        $strands = $query->get()->map(fn (CBCStrand $s) => [
            'id' => $s->id,
            'code' => $s->code,
            'name' => $s->name,
            'description' => $s->description,
            'level' => $s->level,
            'learning_area' => $s->learningArea?->name ?? $s->learning_area,
            'substrands_count' => $s->substrands_count,
        ])->values();

        return response()->json(['success' => true, 'data' => $strands]);
    }

    public function substrands(Request $request)
    {
        $query = CBCSubstrand::query()
            ->active()
            ->ordered()
            ->withCount('competencies');

        if ($request->filled('strand_id')) {
            $query->where('strand_id', (int) $request->input('strand_id'));
        }

        $substrands = $query->get()->map(fn (CBCSubstrand $s) => [
            'id' => $s->id,
            'code' => $s->code,
            'name' => $s->name,
            'description' => $s->description,
            'suggested_lessons' => $s->suggested_lessons,
            'competencies_count' => $s->competencies_count,
        ])->values();

        return response()->json(['success' => true, 'data' => $substrands]);
    }

    public function substrandShow(int $id)
    {
        $s = CBCSubstrand::with(['strand.learningArea', 'competencies' => fn ($q) => $q->where('is_active', true)])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $s->id,
                'code' => $s->code,
                'name' => $s->name,
                'description' => $s->description,
                'strand' => $s->strand?->name,
                'learning_area' => $s->strand?->learningArea?->name ?? $s->strand?->learning_area,
                'learning_outcomes' => $s->learning_outcomes ?? [],
                'key_inquiry_questions' => $s->key_inquiry_questions ?? [],
                'core_competencies' => $s->core_competencies ?? [],
                'values' => $s->values ?? [],
                'pclc' => $s->pclc ?? [],
                'suggested_lessons' => $s->suggested_lessons,
                'competencies' => $s->competencies->map(fn ($c) => [
                    'id' => $c->id,
                    'code' => $c->code,
                    'name' => $c->name,
                    'description' => $c->description,
                    'indicators' => $c->indicators ?? [],
                    'competency_level' => $c->competency_level,
                ])->values(),
            ],
        ]);
    }
}
