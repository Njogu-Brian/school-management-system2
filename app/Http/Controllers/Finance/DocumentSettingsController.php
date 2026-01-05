<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\DocumentCounter;
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
        
        // Get document counters
        $counters = DocumentCounter::whereIn('type', ['invoice', 'receipt', 'credit_note', 'debit_note', 'discount'])
            ->get()
            ->keyBy('type');
        
        // Ensure all types exist with defaults (create in DB if missing)
        $defaultCounters = [
            'invoice' => ['prefix' => 'INV', 'suffix' => '', 'padding_length' => 5, 'next_number' => 1, 'reset_period' => 'yearly'],
            'receipt' => ['prefix' => 'RCPT', 'suffix' => '', 'padding_length' => 6, 'next_number' => 1, 'reset_period' => 'yearly'],
            'credit_note' => ['prefix' => 'CN', 'suffix' => '', 'padding_length' => 5, 'next_number' => 1, 'reset_period' => 'never'],
            'debit_note' => ['prefix' => 'DN', 'suffix' => '', 'padding_length' => 5, 'next_number' => 1, 'reset_period' => 'never'],
            'discount' => ['prefix' => 'DISC', 'suffix' => '', 'padding_length' => 5, 'next_number' => 1, 'reset_period' => 'never'],
        ];
        
        foreach ($defaultCounters as $type => $defaults) {
            if (!$counters->has($type)) {
                $counters[$type] = DocumentCounter::create(array_merge(['type' => $type], $defaults));
            }
        }
        
        return view('finance.document_settings.index', compact('settings', 'counters'));
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
            
            // Document number settings
            'counters' => 'nullable|array',
            'counters.*.prefix' => 'nullable|string|max:20',
            'counters.*.suffix' => 'nullable|string|max:20',
            'counters.*.padding_length' => 'nullable|integer|min:1|max:10',
            'counters.*.next_number' => 'nullable|integer|min:1',
            'counters.*.reset_period' => 'nullable|in:never,yearly,monthly',
        ]);

        // Update header/footer settings
        foreach (['receipt_header', 'receipt_footer', 'invoice_header', 'invoice_footer', 'statement_header', 'statement_footer'] as $key) {
            if (isset($validated[$key])) {
                Setting::set($key, $validated[$key] ?? '');
            }
        }

        // Update document counters
        if (isset($validated['counters'])) {
            foreach ($validated['counters'] as $type => $counterData) {
                DocumentCounter::updateOrCreate(
                    ['type' => $type],
                    [
                        'prefix' => $counterData['prefix'] ?? '',
                        'suffix' => $counterData['suffix'] ?? '',
                        'padding_length' => $counterData['padding_length'] ?? 5,
                        'next_number' => $counterData['next_number'] ?? 1,
                        'reset_period' => $counterData['reset_period'] ?? 'never',
                    ]
                );
            }
        }

        return redirect()
            ->route('finance.document-settings.index')
            ->with('success', 'Document settings updated successfully.');
    }
}
