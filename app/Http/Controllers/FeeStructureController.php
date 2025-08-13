<?php

namespace App\Http\Controllers;

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
    public function create()
    {
        $classrooms = \App\Models\Classroom::all();
        $voteheads = \App\Models\Votehead::all();
        return view('finance.fee_structures.create', compact('classrooms', 'voteheads'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'year' => 'required|numeric',
            'charges' => 'required|array',
            'charges.*.votehead_id' => 'nullable|exists:voteheads,id',
            'charges.*.term_1' => 'nullable|numeric|min:0',
            'charges.*.term_2' => 'nullable|numeric|min:0',
            'charges.*.term_3' => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($request) {
            $structure = FeeStructure::create([
                'classroom_id' => $request->classroom_id,
                'year' => $request->year,
            ]);

            foreach ($request->charges as $charge) {
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

        return redirect()->route('fee-structures.index')->with('success', 'Fee structure created.');
    }
    public function edit(FeeStructure $feeStructure)
    {
        $feeStructure->load('charges'); // preload related charges
        $classrooms = \App\Models\Classroom::all();
        $voteheads = \App\Models\Votehead::all();

        return view('finance.fee_structures.edit', compact('feeStructure', 'classrooms', 'voteheads'));
    }
    public function destroy(FeeStructure $feeStructure)
    {
        $feeStructure->charges()->delete();
        $feeStructure->delete();
        return back()->with('success', 'Deleted successfully.');
    }
}
