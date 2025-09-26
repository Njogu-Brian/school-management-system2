<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\ReportCard;
use Illuminate\Http\Request;

class ReportCardController extends Controller
{
    public function index()
    {
        $reports = ReportCard::with(['student','publisher'])->latest()->paginate(20);
        return view('academics.report_cards.index', compact('reports'));
    }

    public function show(ReportCard $report_card)
    {
        return view('academics.report_cards.show', compact('report_card'));
    }

    public function destroy(ReportCard $report_card)
    {
        $report_card->delete();
        return redirect()->route('academics.report-cards.index')
            ->with('success','Report card deleted.');
    }

    public function publish(ReportCard $report)
    {
        $report->update(['published_at'=>now()]);
        return redirect()->route('academics.report-cards.index')->with('success','Report published.');
    }
}
