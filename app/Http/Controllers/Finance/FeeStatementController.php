<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FeeStatement;
use Illuminate\Http\Request;

class FeeStatementController extends Controller
{
    public function index()
    {
        $statements = FeeStatement::with('student', 'term', 'year')->latest()->paginate(20);
        return view('finance.fee_statements.index', compact('statements'));
    }

    public function show(FeeStatement $feeStatement)
    {
        return view('finance.fee_statements.show', compact('feeStatement'));
    }

    public function generate()
    {
        // logic to generate statements...
        return redirect()->route('fee-statements.index')->with('success', 'Statements generated.');
    }
}
