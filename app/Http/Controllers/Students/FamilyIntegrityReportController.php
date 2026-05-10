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

    private const QUICK_RETURN_ROUTES = [
        'families.integrity-report',
        'families.integrity-report.missing-contacts',
    ];

    /**
     * Admin report: duplicate parent contacts (actionable only).
     */
    public function index(Request $request)
    {
        $dupLimit = min(100, max(10, (int) $request->input('dup_limit', 40)));

        $duplicatePhoneGroups = $this->buildDuplicatePhoneGroups($dupLimit);
        $duplicateEmailGroups = $this->buildDuplicateEmailGroups($dupLimit);

        return view('families.integrity_report', [
            'duplicatePhoneGroups' => $duplicatePhoneGroups,
            'duplicateEmailGroups' => $duplicateEmailGroups,
            'dupLimit' => $dupLimit,
        ]);
    }

    /**
     * Missing father/mother contact fields — split into “both missing” vs “exactly one”.
     */
    public function missingContacts(Request $request)
    {
        $perBoth = min(100, max(5, (int) $request->input('per_both', 25)));
        $perOne = min(100, max(5, (int) $request->input('per_one', 25)));

        $phoneBlankFather = fn ($q) => $q->whereNull('father_phone')->orWhere('father_phone', '=', '');
        $phoneBlankMother = fn ($q) => $q->whereNull('mother_phone')->orWhere('mother_phone', '=', '');

        $missingBoth = Student::query()
            ->where('archive', 0)
            ->whereHas('parent', function ($q) use ($phoneBlankFather, $phoneBlankMother) {
                $q->where(fn ($qq) => $phoneBlankFather($qq))
                    ->where(fn ($qq) => $phoneBlankMother($qq));
            })
            ->with(['parent', 'classroom'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate($perBoth, ['*'], 'both_page')
            ->withQueryString();

        $missingOne = Student::query()
            ->where('archive', 0)
            ->whereHas('parent', function ($q) use ($phoneBlankFather, $phoneBlankMother) {
                $q->where(function ($qq) use ($phoneBlankFather, $phoneBlankMother) {
                    $qq->where(fn ($a) => $phoneBlankFather($a)->where(fn ($b) => $b->whereNotNull('mother_phone')->where('mother_phone', '!=', '')))
                        ->orWhere(fn ($a) => $phoneBlankMother($a)->where(fn ($b) => $b->whereNotNull('father_phone')->where('father_phone', '!=', '')));
                });
            })
            ->with(['parent', 'classroom'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate($perOne, ['*'], 'one_page')
            ->withQueryString();

        return view('families.integrity_missing_contacts', [
            'missingBoth' => $missingBoth,
            'missingOne' => $missingOne,
            'perBoth' => $perBoth,
            'perOne' => $perOne,
            'countryCodes' => $this->countryDialCodesForSelect(),
        ]);
    }

    /**
     * Fill previously-empty parent contact fields from the integrity / missing-contact flows.
     */
    public function quickUpdateParentPhones(Request $request)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'return_route' => 'nullable|string|max:120',
            'ret_dup_limit' => 'nullable|integer|min:10|max:100',
            'ret_both_page' => 'nullable|integer|min:1|max:5000',
            'ret_one_page' => 'nullable|integer|min:1|max:5000',
            'ret_per_both' => 'nullable|integer|min:5|max:100',
            'ret_per_one' => 'nullable|integer|min:5|max:100',
            'father_name' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'father_email' => 'nullable|email|max:255',
            'mother_email' => 'nullable|email|max:255',
            'father_phone_country_code' => 'nullable|string|max:12',
            'mother_phone_country_code' => 'nullable|string|max:12',
            'father_whatsapp_country_code' => 'nullable|string|max:12',
            'mother_whatsapp_country_code' => 'nullable|string|max:12',
            'father_phone' => ['nullable', 'string', 'max:50', 'regex:/^([0-9]{4,15})?$/'],
            'mother_phone' => ['nullable', 'string', 'max:50', 'regex:/^([0-9]{4,15})?$/'],
            'father_whatsapp' => ['nullable', 'string', 'max:50', 'regex:/^([0-9]{4,15})?$/'],
            'mother_whatsapp' => ['nullable', 'string', 'max:50', 'regex:/^([0-9]{4,15})?$/'],
        ]);

        $student = Student::query()->where('archive', 0)->with('parent')->findOrFail($data['student_id']);

        if (! $student->parent) {
            return $this->quickRedirect($request, $data)->with('error', 'This student has no parent record to update.');
        }

        $parent = $student->parent;
        $phoneSvc = app(PhoneNumberService::class);

        $fatherCc = $phoneSvc->normalizeCountryCode($data['father_phone_country_code'] ?? $parent->father_phone_country_code ?? '+254');
        $motherCc = $phoneSvc->normalizeCountryCode($data['mother_phone_country_code'] ?? $parent->mother_phone_country_code ?? '+254');
        $fatherWaCc = $phoneSvc->normalizeCountryCode($data['father_whatsapp_country_code'] ?? $parent->father_whatsapp_country_code ?? $fatherCc);
        $motherWaCc = $phoneSvc->normalizeCountryCode($data['mother_whatsapp_country_code'] ?? $parent->mother_whatsapp_country_code ?? $motherCc);

        $updates = [];

        if (! filled($parent->father_name) && filled($data['father_name'] ?? null)) {
            $updates['father_name'] = trim((string) $data['father_name']);
        }
        if (! filled($parent->mother_name) && filled($data['mother_name'] ?? null)) {
            $updates['mother_name'] = trim((string) $data['mother_name']);
        }

        if (! filled($parent->father_email) && filled($data['father_email'] ?? null)) {
            $updates['father_email'] = strtolower(trim((string) $data['father_email']));
        }
        if (! filled($parent->mother_email) && filled($data['mother_email'] ?? null)) {
            $updates['mother_email'] = strtolower(trim((string) $data['mother_email']));
        }

        if (! filled($parent->father_phone) && filled($data['father_phone'] ?? null)) {
            $check = $phoneSvc->validateLocalDigitsLength($data['father_phone'], $fatherCc);
            if (! $check['ok']) {
                return $this->quickRedirect($request, $data)->withInput()->with(
                    'error',
                    'Father phone must be '.$check['min'].'–'.$check['max'].' digits for '.$check['code'].' (you entered '.$check['digits'].').'
                );
            }
            $updates['father_phone'] = $phoneSvc->formatWithCountryCode($data['father_phone'], $fatherCc);
            $updates['father_phone_country_code'] = $fatherCc;
        }

        if (! filled($parent->mother_phone) && filled($data['mother_phone'] ?? null)) {
            $check = $phoneSvc->validateLocalDigitsLength($data['mother_phone'], $motherCc);
            if (! $check['ok']) {
                return $this->quickRedirect($request, $data)->withInput()->with(
                    'error',
                    'Mother phone must be '.$check['min'].'–'.$check['max'].' digits for '.$check['code'].' (you entered '.$check['digits'].').'
                );
            }
            $updates['mother_phone'] = $phoneSvc->formatWithCountryCode($data['mother_phone'], $motherCc);
            $updates['mother_phone_country_code'] = $motherCc;
        }

        if (! filled($parent->father_whatsapp) && filled($data['father_whatsapp'] ?? null)) {
            $check = $phoneSvc->validateLocalDigitsLength($data['father_whatsapp'], $fatherWaCc);
            if (! $check['ok']) {
                return $this->quickRedirect($request, $data)->withInput()->with(
                    'error',
                    'Father WhatsApp must be '.$check['min'].'–'.$check['max'].' digits for '.$check['code'].' (you entered '.$check['digits'].').'
                );
            }
            $updates['father_whatsapp'] = $phoneSvc->formatWithCountryCode($data['father_whatsapp'], $fatherWaCc);
            $updates['father_whatsapp_country_code'] = $fatherWaCc;
        }

        if (! filled($parent->mother_whatsapp) && filled($data['mother_whatsapp'] ?? null)) {
            $check = $phoneSvc->validateLocalDigitsLength($data['mother_whatsapp'], $motherWaCc);
            if (! $check['ok']) {
                return $this->quickRedirect($request, $data)->withInput()->with(
                    'error',
                    'Mother WhatsApp must be '.$check['min'].'–'.$check['max'].' digits for '.$check['code'].' (you entered '.$check['digits'].').'
                );
            }
            $updates['mother_whatsapp'] = $phoneSvc->formatWithCountryCode($data['mother_whatsapp'], $motherWaCc);
            $updates['mother_whatsapp_country_code'] = $motherWaCc;
        }

        if ($updates === []) {
            return $this->quickRedirect($request, $data)->with(
                'error',
                'Nothing to save: only blank fields on this parent record are filled.'
            );
        }

        $parent->update($updates);

        return $this->quickRedirect($request, $data)->with(
            'success',
            'Contact saved for '.$student->full_name.' ('.$student->admission_number.').'
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function quickRedirect(Request $request, array $data): \Illuminate\Http\RedirectResponse
    {
        $route = $data['return_route'] ?? 'families.integrity-report.missing-contacts';
        if (! in_array($route, self::QUICK_RETURN_ROUTES, true)) {
            $route = 'families.integrity-report.missing-contacts';
        }

        $qs = array_filter([
            'dup_limit' => $request->input('ret_dup_limit'),
            'both_page' => $request->input('ret_both_page'),
            'one_page' => $request->input('ret_one_page'),
            'per_both' => $request->input('ret_per_both'),
            'per_one' => $request->input('ret_per_one'),
        ], fn ($v) => $v !== null && $v !== '');

        return redirect()->route($route, $qs);
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
     * @return list<array<string, mixed>>
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
                $students = Student::query()
                    ->where('archive', 0)
                    ->whereIn('parent_id', $parentIds)
                    ->with(['classroom', 'parent'])
                    ->orderBy('admission_number')
                    ->get();

                if ($students->isEmpty()) {
                    continue;
                }

                $distinctParentRows = $students->pluck('parent_id')->unique()->filter()->count();
                if ($distinctParentRows < 2) {
                    continue;
                }

                $out[] = [
                    'field' => $field,
                    'value' => $value,
                    'count_parents_db' => $parentIds->count(),
                    'distinct_parent_rows' => $distinctParentRows,
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
     * @return list<array<string, mixed>>
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

                $students = Student::query()
                    ->where('archive', 0)
                    ->whereIn('parent_id', $parentIds)
                    ->with(['classroom', 'parent'])
                    ->orderBy('admission_number')
                    ->get();

                if ($students->isEmpty()) {
                    continue;
                }

                $distinctParentRows = $students->pluck('parent_id')->unique()->filter()->count();
                if ($distinctParentRows < 2) {
                    continue;
                }

                $sampleValue = ParentInfo::query()
                    ->whereIn('id', $parentIds)
                    ->value($field) ?? $norm;

                $out[] = [
                    'field' => $field,
                    'value' => (string) $sampleValue,
                    'normalized' => $norm,
                    'count_parents_db' => $parentIds->count(),
                    'distinct_parent_rows' => $distinctParentRows,
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
