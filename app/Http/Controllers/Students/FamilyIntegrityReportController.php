<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Models\ParentInfo;
use App\Models\Student;
use App\Services\PhoneNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FamilyIntegrityReportController extends Controller
{
    private const PHONE_FIELDS = ['father_phone', 'mother_phone', 'guardian_phone'];

    private const EMAIL_FIELDS = ['father_email', 'mother_email', 'guardian_email'];

    /**
     * Admin report: duplicate parent contacts and students missing parent phones.
     */
    public function index(Request $request)
    {
        $dupLimit = min(100, max(10, (int) $request->input('dup_limit', 40)));
        $missingPerPage = min(100, max(10, (int) $request->input('missing_per_page', 50)));

        $duplicatePhoneGroups = $this->buildDuplicatePhoneGroups($dupLimit);
        $duplicateEmailGroups = $this->buildDuplicateEmailGroups($dupLimit);

        $missingQuery = Student::query()
            ->where('archive', 0)
            ->whereHas('parent', function ($q) {
                $q->where(function ($qq) {
                    $qq->whereNull('father_phone')->orWhere('father_phone', '=', '');
                })->orWhere(function ($qq) {
                    $qq->whereNull('mother_phone')->orWhere('mother_phone', '=', '');
                });
            })
            ->with(['parent', 'classroom'])
            ->orderBy('last_name')
            ->orderBy('first_name');

        $missingPhones = $missingQuery->paginate($missingPerPage)->withQueryString();

        return view('families.integrity_report', [
            'duplicatePhoneGroups' => $duplicatePhoneGroups,
            'duplicateEmailGroups' => $duplicateEmailGroups,
            'missingPhones' => $missingPhones,
            'dupLimit' => $dupLimit,
            'missingPerPage' => $missingPerPage,
            'countryCodes' => $this->countryDialCodesForSelect(),
        ]);
    }

    /**
     * Fill previously-empty father/mother phone slots without leaving the integrity report.
     */
    public function quickUpdateParentPhones(Request $request)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'father_phone_country_code' => 'nullable|string|max:12',
            'mother_phone_country_code' => 'nullable|string|max:12',
            'father_phone' => ['nullable', 'string', 'max:50', 'regex:/^[0-9]{4,15}$/'],
            'mother_phone' => ['nullable', 'string', 'max:50', 'regex:/^[0-9]{4,15}$/'],
        ]);

        $student = Student::query()->where('archive', 0)->with('parent')->findOrFail($data['student_id']);

        if (! $student->parent) {
            return back()->with('error', 'This student has no parent record to update.');
        }

        $parent = $student->parent;

        $fatherWasEmpty = ! filled($parent->father_phone);
        $motherWasEmpty = ! filled($parent->mother_phone);

        $phoneSvc = app(PhoneNumberService::class);

        $fatherCc = $phoneSvc->normalizeCountryCode($data['father_phone_country_code'] ?? $parent->father_phone_country_code ?? '+254');
        $motherCc = $phoneSvc->normalizeCountryCode($data['mother_phone_country_code'] ?? $parent->mother_phone_country_code ?? '+254');

        $updates = [];

        if ($fatherWasEmpty && filled($data['father_phone'] ?? null)) {
            $check = $phoneSvc->validateLocalDigitsLength($data['father_phone'], $fatherCc);
            if (! $check['ok']) {
                return back()->withInput()->with(
                    'error',
                    'Father phone must be '.$check['min'].'–'.$check['max'].' digits for '.$check['code'].' (you entered '.$check['digits'].').'
                );
            }
            $updates['father_phone'] = $phoneSvc->formatWithCountryCode($data['father_phone'], $fatherCc);
            $updates['father_phone_country_code'] = $fatherCc;
        }

        if ($motherWasEmpty && filled($data['mother_phone'] ?? null)) {
            $check = $phoneSvc->validateLocalDigitsLength($data['mother_phone'], $motherCc);
            if (! $check['ok']) {
                return back()->withInput()->with(
                    'error',
                    'Mother phone must be '.$check['min'].'–'.$check['max'].' digits for '.$check['code'].' (you entered '.$check['digits'].').'
                );
            }
            $updates['mother_phone'] = $phoneSvc->formatWithCountryCode($data['mother_phone'], $motherCc);
            $updates['mother_phone_country_code'] = $motherCc;
        }

        if ($updates === []) {
            return back()->with(
                'error',
                'Nothing to save: enter a number only for contacts that are currently blank on this record.'
            );
        }

        $parent->update($updates);

        return back()->with('success', 'Contact saved for '.$student->full_name.' ('.$student->admission_number.').');
    }

    /**
     * @return list<array{code:string,label:string}>
     */
    protected function countryDialCodesForSelect(): array
    {
        $path = resource_path('data/country_codes.php');
        if (! is_file($path)) {
            return [['code' => '+254', 'label' => 'Kenya (+254)']];
        }

        /** @var array<string, string> $map */
        $map = include $path;
        $rows = [];
        foreach ($map as $code => $label) {
            $rows[] = ['code' => $code, 'label' => $label];
        }
        usort($rows, fn ($a, $b) => strcmp($a['label'], $b['label']));
        $kenya = array_values(array_filter($rows, fn ($r) => $r['code'] === '+254'));
        $others = array_values(array_filter($rows, fn ($r) => $r['code'] !== '+254'));

        return array_merge($kenya, $others);
    }

    /**
     * @return list<array{field:string,value:string,count_parents:int,students:\Illuminate\Support\Collection,family_summary:string}>
     */
    protected function buildDuplicatePhoneGroups(int $limit): array
    {
        $out = [];
        $seenValueField = [];

        foreach (self::PHONE_FIELDS as $field) {
            $dupVals = DB::table('parent_info')
                ->select($field.' as val', DB::raw('COUNT(*) as c'))
                ->whereNotNull($field)
                ->where($field, '!=', '')
                ->groupBy($field)
                ->having('c', '>', 1)
                ->orderByDesc('c')
                ->limit($limit)
                ->get();

            foreach ($dupVals as $row) {
                $value = (string) $row->val;
                $key = $field.'|'.$value;
                if (isset($seenValueField[$key])) {
                    continue;
                }
                $seenValueField[$key] = true;

                $parentIds = ParentInfo::query()->where($field, $value)->pluck('id');
                $students = Student::withArchived()
                    ->whereIn('parent_id', $parentIds)
                    ->with(['classroom', 'parent'])
                    ->orderBy('admission_number')
                    ->get();

                $out[] = [
                    'field' => $field,
                    'value' => $value,
                    'count_parents' => $parentIds->count(),
                    'students' => $students,
                    'family_summary' => $this->familySummary($students),
                ];

                if (count($out) >= $limit) {
                    return $out;
                }
            }
        }

        return $out;
    }

    /**
     * @return list<array{field:string,value:string,normalized:string,count_parents:int,students:\Illuminate\Support\Collection,family_summary:string}>
     */
    protected function buildDuplicateEmailGroups(int $limit): array
    {
        $out = [];
        $seenNormField = [];

        foreach (self::EMAIL_FIELDS as $field) {
            $dupNorms = DB::table('parent_info')
                ->select(
                    DB::raw('LOWER(TRIM(`'.$field.'`)) as norm'),
                    DB::raw('COUNT(*) as c')
                )
                ->whereNotNull($field)
                ->where($field, '!=', '')
                ->groupBy(DB::raw('LOWER(TRIM(`'.$field.'`))'))
                ->having('c', '>', 1)
                ->orderByDesc('c')
                ->limit($limit)
                ->get();

            foreach ($dupNorms as $row) {
                $norm = (string) $row->norm;
                if ($norm === '') {
                    continue;
                }
                $key = $field.'|'.$norm;
                if (isset($seenNormField[$key])) {
                    continue;
                }
                $seenNormField[$key] = true;

                $parentIds = ParentInfo::query()
                    ->whereRaw('LOWER(TRIM(`'.$field.'`)) = ?', [$norm])
                    ->pluck('id');

                $students = Student::withArchived()
                    ->whereIn('parent_id', $parentIds)
                    ->with(['classroom', 'parent'])
                    ->orderBy('admission_number')
                    ->get();

                $sampleValue = ParentInfo::query()
                    ->whereIn('id', $parentIds)
                    ->value($field) ?? $norm;

                $out[] = [
                    'field' => $field,
                    'value' => (string) $sampleValue,
                    'normalized' => $norm,
                    'count_parents' => $parentIds->count(),
                    'students' => $students,
                    'family_summary' => $this->familySummary($students),
                ];

                if (count($out) >= $limit) {
                    return $out;
                }
            }
        }

        return $out;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Student>  $students
     */
    protected function familySummary($students): string
    {
        $ids = $students->pluck('family_id')->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return 'None linked';
        }
        if ($ids->count() === 1) {
            return 'Family #'.$ids->first();
        }

        return 'Split across families: '.$ids->implode(', ');
    }
}
