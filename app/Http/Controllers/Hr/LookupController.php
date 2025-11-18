<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StaffCategory;
use App\Models\Department;
use App\Models\JobTitle;
use App\Models\CustomField;

class LookupController extends Controller
{
    public function index()
    {
        $categories   = StaffCategory::all();
        $departments  = Department::all();
        $jobTitles    = JobTitle::with('department')->get();
        $customFields = CustomField::where('module','staff')->get();

        return view('lookups.index', compact('categories','departments','jobTitles','customFields'));
    }

    // ============ HR CATEGORIES ============
    public function storeCategory(Request $request)
    {
        $request->validate(['name' => 'required|unique:staff_categories']);
        StaffCategory::create(['name' => $request->name]);
        return back()->with('success', 'Category added');
    }

    public function deleteCategory($id)
    {
        StaffCategory::findOrFail($id)->delete();
        return back()->with('success', 'Category deleted');
    }

    // ============ DEPARTMENTS ============
    public function storeDepartment(Request $request)
    {
        $request->validate(['name' => 'required|unique:departments']);
        Department::create(['name' => $request->name]);
        return back()->with('success', 'Department added');
    }

    public function deleteDepartment($id)
    {
        Department::findOrFail($id)->delete();
        return back()->with('success', 'Department deleted');
    }

    // ============ JOB TITLES ============
    public function storeJobTitle(Request $request)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'name'          => 'required'
        ]);
        JobTitle::create($request->only('department_id', 'name'));
        return back()->with('success', 'Job Title added');
    }

    public function deleteJobTitle($id)
    {
        JobTitle::findOrFail($id)->delete();
        return back()->with('success', 'Job Title deleted');
    }

    // ============ CUSTOM FIELDS ============
    public function storeCustomField(Request $request)
    {
        $request->validate([
            'label'      => 'required',
            'field_key'  => 'required|unique:custom_fields',
            'field_type' => 'required|in:text,number,email,date,file',
        ]);

        CustomField::create([
            'module'     => 'staff',
            'label'      => $request->label,
            'field_key'  => $request->field_key,
            'field_type' => $request->field_type,
            'required'   => $request->has('required')
        ]);

        return back()->with('success', 'Custom Field added');
    }

    public function deleteCustomField($id)
    {
        CustomField::findOrFail($id)->delete();
        return back()->with('success', 'Custom Field deleted');
    }
}
