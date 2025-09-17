<?php

namespace App\Http\Controllers;

use App\Models\CommunicationTemplate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CommunicationTemplateController extends Controller
{
    public function index()
    {
        $templates = CommunicationTemplate::latest()->paginate(20);
        return view('communication.templates.index', compact('templates'));
    }

    public function create()
    {
        return view('communication.templates.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code'       => ['required','string','max:100','unique:communication_templates,code'],
            'title'      => ['required','string','max:255'],
            'type'       => ['required', Rule::in(['email','sms'])],
            'subject'    => ['nullable','string','max:255'],
            'content'    => ['required','string'],
            'attachment' => ['nullable','file','mimes:jpg,jpeg,png,pdf,doc,docx'],
        ]);

        if ($request->hasFile('attachment')) {
            $data['attachment'] = $request->file('attachment')->store('communication_attachments', 'public');
        }

        CommunicationTemplate::create($data);

        return redirect()->route('communication-templates.index')
            ->with('success', 'Template created successfully.');
    }

    public function edit(CommunicationTemplate $communication_template)
    {
        $template = $communication_template;
        return view('communication.templates.edit', compact('template'));
    }

    public function update(Request $request, CommunicationTemplate $communication_template)
    {
        $template = $communication_template;

        $data = $request->validate([
            'code'       => ['required','string','max:100', Rule::unique('communication_templates','code')->ignore($template->id)],
            'title'      => ['required','string','max:255'],
            'type'       => ['required', Rule::in(['email','sms'])],
            'subject'    => ['nullable','string','max:255'],
            'content'    => ['required','string'],
            'attachment' => ['nullable','file','mimes:jpg,jpeg,png,pdf,doc,docx'],
        ]);

        if ($request->hasFile('attachment')) {
            $data['attachment'] = $request->file('attachment')->store('communication_attachments', 'public');
        }

        $template->update($data);

        return redirect()->route('communication-templates.index')
            ->with('success', 'Template updated successfully.');
    }

    public function destroy(CommunicationTemplate $communication_template)
    {
        $communication_template->delete();

        return redirect()->route('communication-templates.index')
            ->with('success', 'Template deleted.');
    }
}
