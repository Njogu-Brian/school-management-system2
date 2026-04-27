<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use Illuminate\Http\Request;

class AcademicYearTermsController extends Controller
{
    public function index(Request $request, AcademicYear $academic_year)
    {
        $terms = $academic_year->terms()
            ->orderBy('name')
            ->get(['id', 'name', 'academic_year_id']);

        return response()->json([
            'terms' => $terms,
        ]);
    }
}

