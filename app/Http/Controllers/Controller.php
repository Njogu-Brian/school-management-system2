<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Models\CommunicationTemplate;
use Illuminate\Http\Request;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function smsIndex()
    {
        $templates = CommunicationTemplate::where('type', 'sms')->get();
        return view('communication.templates.sms.index', compact('templates'));
    }

    public function edit($id)
    {
        $template = CommunicationTemplate::findOrFail($id);
        return view('communication.templates.sms.edit', compact('template'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $template = CommunicationTemplate::findOrFail($id);
        $template->update($request->only('title', 'content'));

        return redirect()->route('sms.templates.index')->with('success', 'Template updated!');
    }

}
