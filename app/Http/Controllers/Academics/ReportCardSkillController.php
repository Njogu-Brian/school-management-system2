<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\ReportCardSkill;
use App\Models\Academics\Classroom;
use Illuminate\Http\Request;

class ReportCardSkillController extends Controller
{
    public function index()
    {
        $skills = ReportCardSkill::with('classroom')->orderBy('name')->paginate(30);
        return view('academics.report_card_skills.index', compact('skills'));
    }

    public function create()
    {
        return view('academics.report_card_skills.create', [
            'classrooms' => Classroom::orderBy('name')->get()
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'description'  => 'nullable|string|max:500',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'is_active'    => 'nullable|boolean',
        ]);

        $data['is_active'] = (bool)($data['is_active'] ?? true);

        ReportCardSkill::create($data);

        return redirect()->route('academics.report-card-skills.index')
            ->with('success', 'Skill created.');
    }

    public function edit(ReportCardSkill $report_card_skill)
    {
        return view('academics.report_card_skills.edit', [
            'skill'      => $report_card_skill,
            'classrooms' => Classroom::orderBy('name')->get()
        ]);
    }

    public function update(Request $request, ReportCardSkill $report_card_skill)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'description'  => 'nullable|string|max:500',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'is_active'    => 'nullable|boolean',
        ]);

        $data['is_active'] = (bool)($data['is_active'] ?? true);

        $report_card_skill->update($data);

        return redirect()->route('academics.report-card-skills.index')
            ->with('success', 'Skill updated.');
    }

    public function destroy(ReportCardSkill $report_card_skill)
    {
        $report_card_skill->delete();

        return back()->with('success', 'Skill deleted.');
    }
}
