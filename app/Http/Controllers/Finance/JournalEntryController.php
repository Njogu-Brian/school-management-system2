<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Services\Finance\JournalPostingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JournalEntryController extends Controller
{
    public function index(): View
    {
        $entries = JournalEntry::with(['creator', 'lines.account'])
            ->latest('entry_date')
            ->latest('id')
            ->paginate(25);

        return view('finance.accounting.journal_entries.index', compact('entries'));
    }

    public function create(): View
    {
        $accounts = Account::query()
            ->where('is_postable', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return view('finance.accounting.journal_entries.create', compact('accounts'));
    }

    public function store(Request $request, JournalPostingService $posting): RedirectResponse
    {
        $validated = $request->validate([
            'entry_date' => 'required|date',
            'description' => 'required|string|max:500',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
            'lines.*.description' => 'nullable|string|max:255',
        ]);

        $entry = $posting->post(
            $validated['lines'],
            $validated['description'],
            $validated['entry_date'],
            'manual_journal',
            null,
            $request->user(),
        );

        return redirect()->route('finance.journal-entries.show', $entry)
            ->with('success', 'Manual journal entry posted.');
    }

    public function show(JournalEntry $journalEntry): View
    {
        $journalEntry->load(['lines.account', 'creator', 'poster']);

        return view('finance.accounting.journal_entries.show', ['entry' => $journalEntry]);
    }
}
