<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\PhoneNumberNormalizationLog;
use App\Models\Student;
use App\Models\ParentInfo;
use App\Models\Staff;
use App\Models\OnlineAdmission;
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

        $studentIds = $logs->where('model_type', Student::class)->pluck('model_id')->filter()->unique()->values();
        $parentIds = $logs->where('model_type', ParentInfo::class)->pluck('model_id')->filter()->unique()->values();
        $staffIds = $logs->where('model_type', Staff::class)->pluck('model_id')->filter()->unique()->values();
        $onlineIds = $logs->where('model_type', OnlineAdmission::class)->pluck('model_id')->filter()->unique()->values();

        $students = Student::with(['classroom', 'stream', 'parent'])
            ->whereIn('id', $studentIds)
            ->get()
            ->keyBy('id');
        $parents = ParentInfo::with(['students.classroom', 'students.stream'])
            ->whereIn('id', $parentIds)
            ->get()
            ->keyBy('id');
        $staff = Staff::whereIn('id', $staffIds)->get()->keyBy('id');
        $onlineAdmissions = OnlineAdmission::with(['classroom', 'stream'])
            ->whereIn('id', $onlineIds)
            ->get()
            ->keyBy('id');

        $logs->setCollection($logs->getCollection()->map(function ($log) use ($students, $parents, $staff, $onlineAdmissions) {
            $log->display_name = '—';
            $log->display_class = '—';
            $log->display_contact = '—';

            if ($log->model_type === Student::class) {
                $student = $students->get($log->model_id);
                if ($student) {
                    $log->display_name = $student->full_name ?? trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''));
                    $log->display_class = $student->classroom?->name ?? '—';
                    $log->display_contact = $student->parent?->primary_contact_name
                        ?? $student->emergency_contact_name
                        ?? '—';
                }
            } elseif ($log->model_type === ParentInfo::class) {
                $parent = $parents->get($log->model_id);
                if ($parent) {
                    $log->display_contact = $parent->primary_contact_name ?? '—';
                    $firstStudent = $parent->students->first();
                    if ($firstStudent) {
                        $log->display_name = $firstStudent->full_name ?? trim(($firstStudent->first_name ?? '') . ' ' . ($firstStudent->last_name ?? ''));
                        $log->display_class = $firstStudent->classroom?->name ?? '—';
                        $extraCount = max(0, $parent->students->count() - 1);
                        if ($extraCount > 0) {
                            $log->display_name .= " (+{$extraCount})";
                        }
                    }
                }
            } elseif ($log->model_type === OnlineAdmission::class) {
                $online = $onlineAdmissions->get($log->model_id);
                if ($online) {
                    $log->display_name = trim(($online->first_name ?? '') . ' ' . ($online->last_name ?? ''));
                    $log->display_class = $online->classroom?->name
                        ?? $online->stream?->name
                        ?? '—';
                    $log->display_contact = $online->father_name
                        ?? $online->mother_name
                        ?? $online->guardian_name
                        ?? $online->emergency_contact_name
                        ?? '—';
                }
            } elseif ($log->model_type === Staff::class) {
                $staffMember = $staff->get($log->model_id);
                if ($staffMember) {
                    $log->display_name = $staffMember->full_name ?? trim(($staffMember->first_name ?? '') . ' ' . ($staffMember->last_name ?? ''));
                    $log->display_contact = $staffMember->emergency_contact_name ?? '—';
                }
            }

            return $log;
        }));

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
