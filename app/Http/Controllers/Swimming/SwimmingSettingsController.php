<?php

namespace App\Http\Controllers\Swimming;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SwimmingSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!Auth::user()->hasAnyRole(['Super Admin', 'Admin'])) {
                abort(403, 'Only administrators can access settings.');
            }
            return $next($request);
        });
    }

    /**
     * Show settings form
     */
    public function index()
    {
        $perVisitCost = setting('swimming_per_visit_cost', 0);
        $termlyPerVisitCost = setting('swimming_termly_per_visit_cost', 0);
        
        return view('swimming.settings.index', [
            'per_visit_cost' => $perVisitCost,
            'termly_per_visit_cost' => $termlyPerVisitCost,
        ]);
    }

    /**
     * Update settings
     */
    public function update(Request $request)
    {
        $request->validate([
            'swimming_per_visit_cost' => 'required|numeric|min:0',
            'swimming_termly_per_visit_cost' => 'nullable|numeric|min:0',
        ]);
        
        setting_set('swimming_per_visit_cost', $request->swimming_per_visit_cost);
        setting_set('swimming_termly_per_visit_cost', $request->swimming_termly_per_visit_cost ?? 0);
        
        return redirect()->route('swimming.settings.index')
            ->with('success', 'Swimming settings updated successfully.');
    }
}
