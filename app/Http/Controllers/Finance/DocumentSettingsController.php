<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class DocumentSettingsController extends Controller
{
    public function index()
    {
        $settings = [
            'receipt_header' => Setting::get('receipt_header', ''),
            'receipt_footer' => Setting::get('receipt_footer', ''),
            'invoice_header' => Setting::get('invoice_header', ''),
            'invoice_footer' => Setting::get('invoice_footer', ''),
            'statement_header' => Setting::get('statement_header', ''),
            'statement_footer' => Setting::get('statement_footer', ''),
        ];
        
        return view('finance.document_settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'receipt_header' => 'nullable|string',
            'receipt_footer' => 'nullable|string',
            'invoice_header' => 'nullable|string',
            'invoice_footer' => 'nullable|string',
            'statement_header' => 'nullable|string',
            'statement_footer' => 'nullable|string',
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, $value ?? '');
        }

        return redirect()
            ->route('finance.document-settings.index')
            ->with('success', 'Document settings updated successfully.');
    }
}
