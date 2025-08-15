<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\FeeStructure;
use App\Models\Student;
use App\Models\Votehead;
use App\Services\DocumentNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::with('student.classroom')->latest()->paginate(20);
        return view('finance.invoices.index', compact('invoices'));
    }

    public function create()
    {
        $classrooms = \App\Models\Classroom::all();
        return view('finance.invoices.create', compact('classrooms'));
    }

    public function generate(Request $request)
    {
        $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'year' => 'required|integer',
            'term' => 'required|in:1,2,3',
        ]);

        $structure = FeeStructure::with('charges')
            ->where('classroom_id', $request->classroom_id)
            ->where('year', $request->year)
            ->first();

        if (!$structure) {
            return back()->with('error', 'Fee structure not found for selected class and year.');
        }

        $students = Student::where('classroom_id', $request->classroom_id)->get();

        DB::transaction(function () use ($students, $structure, $request) {
            foreach ($students as $student) {
                $invoiceNumber = DocumentNumberService::generate('invoice', 'INV');
                $total = 0;

                $invoice = Invoice::create([
                    'student_id' => $student->id,
                    'year' => $request->year,
                    'term' => $request->term,
                    'invoice_number' => $invoiceNumber,
                    'total' => 0,
                ]);

                foreach ($structure->charges->where('term', $request->term) as $charge) {
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'votehead_id' => $charge->votehead_id,
                        'amount' => $charge->amount,
                    ]);

                    $total += $charge->amount;
                }

                // Brought forward logic
                $prevBalance = Invoice::where('student_id', $student->id)
                    ->where('year', '<', $request->year)
                    ->where('status', '!=', 'paid')
                    ->sum(DB::raw('total - (SELECT SUM(amount) FROM payments WHERE payments.invoice_id = invoices.id)'));

                $invoice->update(['total' => $total + max(0, $prevBalance)]);
            }
        });

        return redirect()->route('finance.invoices.index')->with('success', 'Invoices generated successfully.');
    }

    public function show(Invoice $invoice)
    {
        $invoice->load('student.classroom', 'items.votehead');
        return view('finance.invoices.show', compact('invoice'));
    }

    public function importForm()
    {
        return view('finance.invoices.import');
    }

    public function import(Request $request)
    {
        // Excel import logic goes here (to be implemented separately)
        return back()->with('success', 'Imported successfully.');
    }
}
