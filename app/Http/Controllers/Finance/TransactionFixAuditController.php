<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\TransactionFixAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionFixAuditController extends Controller
{
    public function index(Request $request)
    {
        $query = TransactionFixAudit::with(['appliedBy', 'reversedBy'])
            ->orderBy('created_at', 'desc');

        // Filters
        if ($request->filled('fix_type')) {
            $query->where('fix_type', $request->fix_type);
        }

        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }

        if ($request->filled('applied')) {
            $query->where('applied', $request->boolean('applied'));
        }

        if ($request->filled('reversed')) {
            $query->where('reversed', $request->boolean('reversed'));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('entity_id', $search)
                  ->orWhere('reason', 'LIKE', "%{$search}%");
            });
        }

        $audits = $query->paginate(50);

        // Statistics
        $stats = [
            'total' => TransactionFixAudit::count(),
            'applied' => TransactionFixAudit::where('applied', true)->count(),
            'reversed' => TransactionFixAudit::where('reversed', true)->count(),
            'pending' => TransactionFixAudit::where('applied', false)->count(),
            'by_type' => TransactionFixAudit::select('fix_type', DB::raw('count(*) as count'))
                ->groupBy('fix_type')
                ->get()
                ->pluck('count', 'fix_type'),
        ];

        return view('finance.transaction-fixes.index', compact('audits', 'stats'));
    }

    public function show(TransactionFixAudit $audit)
    {
        $audit->load(['appliedBy', 'reversedBy']);

        // Get entity details
        $entity = null;
        if ($audit->entity_type === 'bank_statement_transaction') {
            $entity = \App\Models\BankStatementTransaction::with(['student', 'payment'])->find($audit->entity_id);
        } elseif ($audit->entity_type === 'mpesa_c2b_transaction') {
            $entity = \App\Models\MpesaC2BTransaction::with(['student', 'payment'])->find($audit->entity_id);
        } elseif ($audit->entity_type === 'payment') {
            $entity = \App\Models\Payment::with(['student'])->find($audit->entity_id);
        }

        return view('finance.transaction-fixes.show', compact('audit', 'entity'));
    }

    public function reverse(TransactionFixAudit $audit)
    {
        if ($audit->reversed) {
            return back()->with('error', 'This change has already been reversed.');
        }

        if (!$audit->applied) {
            return back()->with('error', 'This change has not been applied yet.');
        }

        try {
            DB::beginTransaction();

            // Reverse the change
            $oldValues = $audit->old_values;
            $entityType = $audit->entity_type;
            $entityId = $audit->entity_id;

            if ($entityType === 'bank_statement_transaction') {
                \App\Models\BankStatementTransaction::where('id', $entityId)->update($oldValues);
            } elseif ($entityType === 'mpesa_c2b_transaction') {
                \App\Models\MpesaC2BTransaction::where('id', $entityId)->update($oldValues);
            } elseif ($entityType === 'payment') {
                \App\Models\Payment::where('id', $entityId)->update($oldValues);
            } elseif ($entityType === 'swimming_wallet') {
                \App\Models\SwimmingWallet::where('id', $entityId)->update($oldValues);
            }

            // Mark as reversed
            $audit->update([
                'reversed' => true,
                'reversed_at' => now(),
                'reversed_by' => auth()->id(),
            ]);

            DB::commit();

            return back()->with('success', 'Change reversed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to reverse change: ' . $e->getMessage());
        }
    }

    public function bulkReverse(Request $request)
    {
        $request->validate([
            'audit_ids' => 'required|array',
            'audit_ids.*' => 'exists:transaction_fix_audit,id',
        ]);

        $audits = TransactionFixAudit::whereIn('id', $request->audit_ids)
            ->where('applied', true)
            ->where('reversed', false)
            ->get();

        if ($audits->isEmpty()) {
            return back()->with('error', 'No valid changes selected for reversal.');
        }

        try {
            DB::beginTransaction();

            $reversed = 0;
            foreach ($audits as $audit) {
                $oldValues = $audit->old_values;
                $entityType = $audit->entity_type;
                $entityId = $audit->entity_id;

                if ($entityType === 'bank_statement_transaction') {
                    \App\Models\BankStatementTransaction::where('id', $entityId)->update($oldValues);
                } elseif ($entityType === 'mpesa_c2b_transaction') {
                    \App\Models\MpesaC2BTransaction::where('id', $entityId)->update($oldValues);
                } elseif ($entityType === 'payment') {
                    \App\Models\Payment::where('id', $entityId)->update($oldValues);
                } elseif ($entityType === 'swimming_wallet') {
                    \App\Models\SwimmingWallet::where('id', $entityId)->update($oldValues);
                }

                $audit->update([
                    'reversed' => true,
                    'reversed_at' => now(),
                    'reversed_by' => auth()->id(),
                ]);

                $reversed++;
            }

            DB::commit();

            return back()->with('success', "Successfully reversed {$reversed} change(s).");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to reverse changes: ' . $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        $query = TransactionFixAudit::with(['appliedBy', 'reversedBy']);

        // Apply same filters as index
        if ($request->filled('fix_type')) {
            $query->where('fix_type', $request->fix_type);
        }

        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }

        if ($request->filled('applied')) {
            $query->where('applied', $request->boolean('applied'));
        }

        if ($request->filled('reversed')) {
            $query->where('reversed', $request->boolean('reversed'));
        }

        $audits = $query->orderBy('created_at', 'desc')->get();

        $filename = 'transaction-fixes-' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($audits) {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, [
                'ID',
                'Fix Type',
                'Entity Type',
                'Entity ID',
                'Old Values',
                'New Values',
                'Reason',
                'Applied',
                'Reversed',
                'Applied At',
                'Applied By',
                'Reversed At',
                'Reversed By',
                'Created At',
            ]);

            // Data
            foreach ($audits as $audit) {
                fputcsv($file, [
                    $audit->id,
                    $audit->fix_type,
                    $audit->entity_type,
                    $audit->entity_id,
                    json_encode($audit->old_values),
                    json_encode($audit->new_values),
                    $audit->reason,
                    $audit->applied ? 'Yes' : 'No',
                    $audit->reversed ? 'Yes' : 'No',
                    $audit->applied_at ? $audit->applied_at->format('Y-m-d H:i:s') : '',
                    $audit->appliedBy ? $audit->appliedBy->name : 'System',
                    $audit->reversed_at ? $audit->reversed_at->format('Y-m-d H:i:s') : '',
                    $audit->reversedBy ? $audit->reversedBy->name : '',
                    $audit->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
