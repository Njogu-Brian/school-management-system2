<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\LegacyFinanceImportBatch;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class PaymentSettingsController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'bank-accounts');
        if (! in_array($tab, ['bank-accounts', 'payment-methods', 'legacy-imports'], true)) {
            $tab = 'bank-accounts';
        }

        $bankAccounts = BankAccount::orderBy('name')->get();

        $paymentMethods = PaymentMethod::with('bankAccount')
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        $batches = LegacyFinanceImportBatch::latest()
            ->paginate(15)
            ->appends(['tab' => 'legacy-imports']);

        return view('finance.payment_settings.index', compact(
            'tab',
            'bankAccounts',
            'paymentMethods',
            'batches'
        ));
    }
}
