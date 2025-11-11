<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Models\StudentCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StudentCategoryController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        
        // Filtering based on search query
        $categories = StudentCategory::when($search, function ($query, $search) {
            return $query->where('name', 'like', "%{$search}%");
        })->paginate(10);
    
        return view('student_categories.index', compact('categories', 'request'));
    }
    

    public function create()
    {
        return view('student_categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:student_categories,name',
        ]);

        try {
            StudentCategory::create($request->all());
            return redirect()->route('student-categories.index')->with('success', 'Student Category added successfully.');
        } catch (\Exception $e) {
            Log::error('Error creating category: ' . $e->getMessage());
            return back()->with('error', 'Failed to create category.');
        }
    }

    public function edit($id)
    {
        try {
            $category = StudentCategory::findOrFail($id);
            return view('student_categories.edit', compact('category'));
        } catch (\Exception $e) {
            Log::error('Error loading category for edit: ' . $e->getMessage());
            return back()->with('error', 'Failed to load category details.');
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:student_categories,name,'.$id,
        ]);

        try {
            $category = StudentCategory::findOrFail($id);
            $category->update($request->all());
            return redirect()->route('student-categories.index')->with('success', 'Student Category updated successfully.');
        } catch (\Exception $e) {
            Log::error('Error updating category: ' . $e->getMessage());
            return back()->with('error', 'Failed to update category.');
        }
    }

    public function destroy($id)
    {
        try {
            $category = StudentCategory::findOrFail($id);
            $category->delete();
            return redirect()->route('student-categories.index')->with('success', 'Student Category deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Error deleting category: ' . $e->getMessage());
            return back()->with('error', 'Failed to delete category.');
        }
    }
}
