<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CommunicationTemplate;

class SmsTemplateController extends Controller
{
    public function index()
    {
        $smsTemplates = CommunicationTemplate::where('type', 'sms')->get();
        return view('communication.sms_templates.index', compact('smsTemplates'));
    }

    public function create()
    {
        return view('communication.sms_templates.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:300',
        ]);

        $data['type'] = 'sms';
        $data['content'] = $data['message'];
        unset($data['message']);

        CommunicationTemplate::create($data);

        return redirect()->route('sms-templates.index')->with('success', 'SMS Template created successfully.');
    }

    public function edit($id)
    {
        $smsTemplate = CommunicationTemplate::where('type', 'sms')->findOrFail($id);
        return view('communication.sms_templates.edit', compact('smsTemplate'));
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:300',
        ]);

        $smsTemplate = CommunicationTemplate::where('type', 'sms')->findOrFail($id);
        $smsTemplate->update([
            'title' => $data['title'],
            'content' => $data['message'],
        ]);

        return redirect()->route('sms-templates.index')->with('success', 'SMS Template updated successfully.');
    }

    public function destroy($id)
    {
        CommunicationTemplate::where('type', 'sms')->where('id', $id)->delete();
        return redirect()->route('sms-templates.index')->with('success', 'Template deleted successfully.');
    }
}
