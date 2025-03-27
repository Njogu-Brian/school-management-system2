<?php

namespace App\Http\Controllers;

use App\Models\ParentInfo;
use Illuminate\Http\Request;

class ParentInfoController extends Controller
{
    public function index()
    {
        $parents = ParentInfo::all();
        return view('parents.index', compact('parents'));
    }

    public function create()
    {
        return view('parents.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'father_name' => 'nullable|string|max:255',
            'father_phone' => 'nullable|string|max:15',
            'father_whatsapp' => 'nullable|string|max:15',
            'father_email' => 'nullable|email|max:255',
            'father_id_number' => 'nullable|string|max:20',
            
            'mother_name' => 'nullable|string|max:255',
            'mother_phone' => 'nullable|string|max:15',
            'mother_whatsapp' => 'nullable|string|max:15',
            'mother_email' => 'nullable|email|max:255',
            'mother_id_number' => 'nullable|string|max:20',
            
            'guardian_name' => 'nullable|string|max:255',
            'guardian_phone' => 'nullable|string|max:15',
            'guardian_whatsapp' => 'nullable|string|max:15',
            'guardian_email' => 'nullable|email|max:255',
            'guardian_id_number' => 'nullable|string|max:20',
        ]);

        ParentInfo::create($request->all());

        return redirect()->route('parents.index')->with('success', 'Parent information added successfully.');
    }

    public function edit($id)
    {
        $parent = ParentInfo::findOrFail($id);
        return view('parents.edit', compact('parent'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'father_name' => 'nullable|string|max:255',
            'father_phone' => 'nullable|string|max:15',
            'father_whatsapp' => 'nullable|string|max:15',
            'father_email' => 'nullable|email|max:255',
            'father_id_number' => 'nullable|string|max:20',
            
            'mother_name' => 'nullable|string|max:255',
            'mother_phone' => 'nullable|string|max:15',
            'mother_whatsapp' => 'nullable|string|max:15',
            'mother_email' => 'nullable|email|max:255',
            'mother_id_number' => 'nullable|string|max:20',
            
            'guardian_name' => 'nullable|string|max:255',
            'guardian_phone' => 'nullable|string|max:15',
            'guardian_whatsapp' => 'nullable|string|max:15',
            'guardian_email' => 'nullable|email|max:255',
            'guardian_id_number' => 'nullable|string|max:20',
        ]);

        $parent = ParentInfo::findOrFail($id);
        $parent->update($request->all());

        return redirect()->route('parents.index')->with('success', 'Parent information updated successfully.');
    }

    public function destroy($id)
    {
        $parent = ParentInfo::findOrFail($id);
        $parent->delete();

        return redirect()->route('parents.index')->with('success', 'Parent information deleted successfully.');
    }
}
