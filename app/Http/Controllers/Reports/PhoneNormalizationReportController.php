<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\PhoneNumberNormalizationLog;
use Illuminate\Http\Request;

class PhoneNormalizationReportController extends Controller
{
    public function index(Request $request)
    {
        $query = PhoneNumberNormalizationLog::query()->orderByDesc('id');

        if ($request->filled('model_type')) {
            $query->where('model_type', $request->model_type);
        }
        if ($request->filled('field')) {
            $query->where('field', $request->field);
        }
        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('old_value', 'like', "%{$search}%")
                    ->orWhere('new_value', 'like', "%{$search}%")
                    ->orWhere('model_id', $search);
            });
        }

        $logs = $query->paginate(50)->withQueryString();

        $modelTypes = PhoneNumberNormalizationLog::query()
            ->select('model_type')
            ->distinct()
            ->orderBy('model_type')
            ->pluck('model_type');

        $fields = PhoneNumberNormalizationLog::query()
            ->select('field')
            ->distinct()
            ->orderBy('field')
            ->pluck('field');

        $sources = PhoneNumberNormalizationLog::query()
            ->select('source')
            ->distinct()
            ->orderBy('source')
            ->pluck('source');

        return view('reports.phone-normalization.index', compact('logs', 'modelTypes', 'fields', 'sources'));
    }
}
