<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CommunicationTemplate;

class CommunicationTemplateController extends Controller
{
    public function index()
    {
        $templates = CommunicationTemplate::all();
        return view('communication.templates.index', compact('templates'));
    }

    public function create()
    {
        return view('communication.templates.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'type' => 'required|in:email,sms',
            'subject' => 'nullable|string',
            'content' => 'required',
        ]);

        CommunicationTemplate::create($request->all());
        return redirect()->route('templates.index')->with('success', 'Template created successfully');
    }

    public function edit($id)
    {
        $template = CommunicationTemplate::findOrFail($id);
        return view('communication.templates.edit', compact('template'));
    }

    public function update(Request $request, $id)
    {
        $template = CommunicationTemplate::findOrFail($id);
        $template->update($request->all());

        return redirect()->route('templates.index')->with('success', 'Template updated successfully');
    }

    public function destroy($id)
    {
        CommunicationTemplate::destroy($id);
        return redirect()->route('templates.index')->with('success', 'Template deleted');
    }
}
