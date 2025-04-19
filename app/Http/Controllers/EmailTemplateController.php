<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use Illuminate\Http\Request;

class EmailTemplateController extends Controller
{
    public function index()
    {
        $emailTemplates = EmailTemplate::latest()->get();
        return view('communication.email_templates.index', compact('emailTemplates'));
    }

    public function create()
    {
        return view('communication.email_templates.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|unique:email_templates,code',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx',
        ]);

        if ($request->hasFile('attachment')) {
            $data['attachment'] = $request->file('attachment')->store('email_attachments', 'public');
        }

        EmailTemplate::create($data);
        return redirect()->route('email-templates.index')->with('success', 'Email Template created successfully.');
    }

    public function edit($id)
    {
        $emailTemplate = EmailTemplate::findOrFail($id);
        return view('communication.email_templates.edit', compact('emailTemplate'));
    }

    public function update(Request $request, $id)
    {
        $template = EmailTemplate::findOrFail($id);

        $data = $request->validate([
            'code' => 'required|string|unique:email_templates,code,' . $id,
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx',
        ]);

        if ($request->hasFile('attachment')) {
            $data['attachment'] = $request->file('attachment')->store('email_attachments', 'public');
        }

        $template->update($data);
        return redirect()->route('email-templates.index')->with('success', 'Email Template updated.');
    }

    public function destroy($id)
    {
        EmailTemplate::destroy($id);
        return back()->with('success', 'Email Template deleted.');
    }
}

