<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Website\SchoolMeal;
use App\Policies\Website\ManagesWebsiteCms;
use Illuminate\Http\Request;

class SchoolMealController extends Controller
{
    use ManagesWebsiteCms;

    public function __construct()
    {
        $this->middleware(fn ($r, $n) => $this->canManageWebsite($r->user()) ? $n($r) : abort(403));
    }

    public function index()
    {
        $meals = SchoolMeal::orderByDesc('meal_date')->paginate(30);

        return view('website.meals.index', compact('meals'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'meal_date' => 'required|date',
            'day_of_week' => 'nullable|string',
            'breakfast' => 'nullable|string|max:255',
            'lunch' => 'nullable|string|max:255',
            'snack' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        SchoolMeal::updateOrCreate(['meal_date' => $data['meal_date']], $data);

        return back()->with('success', 'Meal saved.');
    }
}
