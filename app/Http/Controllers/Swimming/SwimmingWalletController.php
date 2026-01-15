<?php

namespace App\Http\Controllers\Swimming;

use App\Http\Controllers\Controller;
use App\Models\{SwimmingWallet, Student};
use App\Services\SwimmingWalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SwimmingWalletController extends Controller
{
    protected $walletService;

    public function __construct(SwimmingWalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * List all wallets
     */
    public function index(Request $request)
    {
        $query = SwimmingWallet::with(['student.classroom']);
        
        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('student', function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('admission_number', 'like', "%{$search}%");
            });
        }
        
        // Filter by classroom
        if ($request->filled('classroom_id')) {
            $query->whereHas('student', function($q) use ($request) {
                $q->where('classroom_id', $request->classroom_id);
            });
        }
        
        // Filter by balance
        if ($request->filled('balance_filter')) {
            if ($request->balance_filter === 'positive') {
                $query->where('balance', '>', 0);
            } elseif ($request->balance_filter === 'zero') {
                $query->where('balance', '=', 0);
            } elseif ($request->balance_filter === 'negative') {
                $query->where('balance', '<', 0);
            }
        }
        
        $wallets = $query->orderBy('balance', 'desc')
            ->paginate(50);
        
        $classrooms = \App\Models\Academics\Classroom::orderBy('name')->get();
        
        return view('swimming.wallets.index', [
            'wallets' => $wallets,
            'classrooms' => $classrooms,
            'filters' => $request->only(['search', 'classroom_id', 'balance_filter']),
        ]);
    }

    /**
     * Show wallet details for a student
     */
    public function show(Student $student)
    {
        $wallet = SwimmingWallet::getOrCreateForStudent($student->id);
        $wallet->load(['student', 'ledgerEntries' => function($q) {
            $q->orderBy('created_at', 'desc')->limit(100);
        }]);
        
        return view('swimming.wallets.show', [
            'wallet' => $wallet,
            'student' => $student,
        ]);
    }

    /**
     * Adjust wallet balance (admin only)
     */
    public function adjust(Request $request, Student $student)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|in:credit,debit',
            'description' => 'required|string|max:500',
        ]);
        
        if (!Auth::user()->hasAnyRole(['Super Admin', 'Admin'])) {
            abort(403, 'Only administrators can adjust wallet balances.');
        }
        
        try {
            if ($request->type === 'credit') {
                $this->walletService->creditFromAdjustment(
                    $student,
                    $request->amount,
                    $request->description,
                    Auth::user()
                );
                $message = "Credited {$request->amount} to wallet.";
            } else {
                // For debit, we need to check balance first
                $wallet = SwimmingWallet::getOrCreateForStudent($student->id);
                if ($wallet->balance < $request->amount) {
                    return redirect()->back()
                        ->with('error', "Insufficient balance. Current balance: {$wallet->balance}");
                }
                
                // Create a negative adjustment (debit)
                $this->walletService->creditFromAdjustment(
                    $student,
                    -$request->amount,
                    $request->description,
                    Auth::user()
                );
                $message = "Debited {$request->amount} from wallet.";
            }
            
            return redirect()->route('swimming.wallets.show', $student)
                ->with('success', $message);
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to adjust wallet: ' . $e->getMessage())
                ->withInput();
        }
    }
}
