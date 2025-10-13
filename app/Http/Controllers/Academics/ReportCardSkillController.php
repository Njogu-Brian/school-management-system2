<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\ReportCard;
use App\Models\Academics\ReportCardSkill;
use App\Models\Academics\Classroom;
use Illuminate\Http\Request;

class ReportCardSkillController extends Controller
{
    public function index(ReportCard $reportCard)
    {
        $skills = $reportCard->skills()->with('classroom')->orderBy('skill_name')->paginate(30);
        return view('academics.report_cards.skills.index', compact('skills', 'reportCard'));
    }

    public function create(ReportCard $reportCard)
    {
        return view('academics.report_cards.skills.create', [
            'reportCard' => $reportCard,
            'classrooms' => Classroom::orderBy('skill_name')->get()
        ]);
    }

    public function store(Request $request, ReportCard $reportCard)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'description'  => 'nullable|string|max:500',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'is_active'    => 'nullable|boolean',
            'rating'       => 'nullable|in:EE,ME,AE,BE',
        ]);

        $data['is_active'] = (bool)($data['is_active'] ?? true);

        $reportCard->skills()->create($data);

        return redirect()
            ->route('academics.report-cards.skills.index', $reportCard)
            ->with('success', 'Skill added to report card.');
    }

    public function edit(ReportCard $reportCard, ReportCardSkill $skill)
    {
        return view('academics.report_cards.skills.edit', [
            'reportCard' => $reportCard,
            'skill' => $skill,
            'classrooms' => Classroom::orderBy('skill_name')->get()
        ]);
    }

    public function update(Request $request, ReportCard $reportCard, ReportCardSkill $skill)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'description'  => 'nullable|string|max:500',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'is_active'    => 'nullable|boolean',
            'rating'       => 'nullable|in:EE,ME,AE,BE',
        ]);

        $skill->update($data);

        return redirect()
            ->route('academics.report-cards.skills.index', $reportCard)
            ->with('success', 'Skill updated successfully.');
    }

    public function destroy(ReportCard $reportCard, ReportCardSkill $skill)
    {
        $skill->delete();

        return redirect()
            ->route('academics.report-cards.skills.index', $reportCard)
            ->with('success', 'Skill removed.');
    }
}
