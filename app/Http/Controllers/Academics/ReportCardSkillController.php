<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\ReportCard;
use App\Models\Academics\ReportCardSkill;
use Illuminate\Http\Request;

class ReportCardSkillController extends Controller
{
    public function index(ReportCard $reportCard)
    {
        $reportCard->load('student');
        $skills = $reportCard->skills()
            ->orderBy('skill_name')   // field used by your blades
            ->paginate(30);

        return view('academics.report_cards.skills.index', compact('skills', 'reportCard'));
    }

    public function create(ReportCard $reportCard)
    {
        return view('academics.report_cards.skills.create', compact('reportCard'));
    }

    public function store(Request $request, ReportCard $reportCard)
    {
        $data = $request->validate([
            'skill_name' => 'required|string|max:255',
            'rating'     => 'nullable|in:EE,ME,AE,BE',
        ]);

        $reportCard->skills()->create($data);

        return redirect()
            ->route('academics.report_cards.skills.index', $reportCard)
            ->with('success', 'Skill added to report card.');
    }

    public function edit(ReportCard $reportCard, ReportCardSkill $skill)
    {
        return view('academics.report_cards.skills.edit', compact('reportCard','skill'));
    }

    public function update(Request $request, ReportCard $reportCard, ReportCardSkill $skill)
    {
        $data = $request->validate([
            'skill_name' => 'required|string|max:255',
            'rating'     => 'nullable|in:EE,ME,AE,BE',
        ]);

        $skill->update($data);

        return redirect()
            ->route('academics.report_cards.skills.index', $reportCard)
            ->with('success', 'Skill updated successfully.');
    }

    public function destroy(ReportCard $reportCard, ReportCardSkill $skill)
    {
        $skill->delete();

        return redirect()
            ->route('academics.report_cards.skills.index', $reportCard)
            ->with('success', 'Skill removed.');
    }
}
