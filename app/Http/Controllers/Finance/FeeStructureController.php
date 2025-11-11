<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FeeStructure;
use App\Models\FeeCharge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeeStructureController extends Controller
{
    public function index()
    {
        $structures = FeeStructure::with('classroom', 'charges.votehead')->get();
        return view('finance.fee_structures.index', compact('structures'));
    }
    public function show(FeeStructure $feeStructure)
    {
        $feeStructure->load('classroom', 'charges.votehead');

        return view('finance.fee_structures.show', compact('feeStructure'));
    }
    public function manage(Request $request)
    {
        $classrooms = \App\Models\Academics\Classroom::all();
        $voteheads = \App\Models\Votehead::all();

        $selectedClassroom = $request->query('classroom_id');

        $feeStructure = null;
        $charges = [];

        if ($selectedClassroom) {
            $feeStructure = FeeStructure::with('charges')->where('classroom_id', $selectedClassroom)->first();

            if ($feeStructure) {
                $charges = $feeStructure->charges;
            }
        }

        return view('finance.fee_structures.manage', compact('classrooms', 'voteheads', 'selectedClassroom', 'feeStructure', 'charges'));
    }

    public function save(Request $request)
    {
        $validated = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'year' => 'required|numeric',
            'charges' => 'required|array',
            'charges.*.votehead_id' => 'required|exists:voteheads,id',
            'charges.*.term_1' => 'nullable|numeric|min:0',
            'charges.*.term_2' => 'nullable|numeric|min:0',
            'charges.*.term_3' => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated) {
            $structure = FeeStructure::updateOrCreate(
                ['classroom_id' => $validated['classroom_id']],
                ['year' => $validated['year']]
            );

            // Remove old charges
            $structure->charges()->delete();

            foreach ($validated['charges'] as $charge) {
                foreach ([1 => $charge['term_1'], 2 => $charge['term_2'], 3 => $charge['term_3']] as $term => $amount) {
                    if ($amount > 0) {
                        FeeCharge::create([
                            'fee_structure_id' => $structure->id,
                            'votehead_id' => $charge['votehead_id'],
                            'term' => $term,
                            'amount' => $amount,
                        ]);
                    }
                }
            }
        });

        return redirect()->route('finance.fee-structures.manage', ['classroom_id' => $request->classrooms_id])
                        ->with('success', 'Fee structure saved successfully.');
    }
   
    public function replicateTo(Request $request)
    {
        $request->validate([
            'source_classroom_id' => 'required|exists:classrooms,id',
            'target_classroom_ids' => 'required|array|min:1',
            'target_classroom_ids.*' => 'exists:classrooms,id',
        ]);

        $source = FeeStructure::with('charges')->where('classroom_id', $request->source_classroom_id)->first();

        if (!$source) {
            return back()->with('error', 'Source class has no fee structure.');
        }

        foreach ($request->target_classroom_ids as $targetId) {
            // Delete old structure if exists
            $existing = FeeStructure::where('classroom_id', $targetId)->first();
            if ($existing) {
                $existing->charges()->delete();
                $existing->delete();
            }

            // Clone fee structure
            $newStructure = FeeStructure::create([
                'classroom_id' => $targetId,
                'year' => $source->year,
            ]);

            foreach ($source->charges as $charge) {
                FeeCharge::create([
                    'fee_structure_id' => $newStructure->id,
                    'votehead_id' => $charge->votehead_id,
                    'term' => $charge->term,
                    'amount' => $charge->amount,
                ]);
            }
        }

        return back()->with('success', 'Fee structure replicated successfully.');
    }

}
