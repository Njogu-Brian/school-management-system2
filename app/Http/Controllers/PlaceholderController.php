<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CommunicationPlaceholder;

class PlaceholderController extends Controller
{
    public function index()
    {
        $placeholders = CommunicationPlaceholder::latest()->paginate(15);
        return view('settings.placeholders.index', compact('placeholders'));
    }

    public function create()
    {
        return view('settings.placeholders.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'key'   => 'required|string|unique:communication_placeholders,key',
            'value' => 'nullable|string',
        ]);
        CommunicationPlaceholder::create($data);
        return redirect()->route('settings.placeholders.index')->with('success','Placeholder created successfully.');
    }

    public function edit(CommunicationPlaceholder $placeholder)
    {
        return view('settings.placeholders.edit', compact('placeholder'));
    }

    public function update(Request $request, CommunicationPlaceholder $placeholder)
    {
        $data = $request->validate([
            'key'   => 'required|string|unique:communication_placeholders,key,'.$placeholder->id,
            'value' => 'nullable|string',
        ]);
        $placeholder->update($data);
        return redirect()->route('settings.placeholders.index')->with('success','Placeholder updated successfully.');
    }

    public function destroy(CommunicationPlaceholder $placeholder)
    {
        $placeholder->delete();
        return back()->with('success','Placeholder deleted.');
    }
}
