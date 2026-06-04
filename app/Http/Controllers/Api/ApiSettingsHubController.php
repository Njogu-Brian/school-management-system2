<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Academics\Classroom;
use App\Models\Academics\ExamType;
use App\Models\Academics\GradingBand;
use App\Models\Academics\GradingScheme;
use App\Models\Academics\Stream;
use App\Models\Academics\Subject;
use App\Models\Setting;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

/**
 * Read-only Settings Hub APIs for Admin mobile (Sprint 4 Batch 1).
 */
class ApiSettingsHubController extends Controller
{
    public function school(Request $request)
    {
        $this->assertSettingsAccess($request);

        $schoolName = trim((string) (Setting::get('school_name') ?: config('app.name', 'School'))) ?: 'School';

        $enabledModules = [];
        $raw = Setting::get('enabled_modules');
        if ($raw) {
            $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
            $enabledModules = is_array($decoded) ? array_values($decoded) : [];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'school_name' => $schoolName,
                'school_email' => Setting::get('school_email'),
                'school_phone' => Setting::get('school_phone'),
                'school_address' => Setting::get('school_address'),
                'timezone' => Setting::get('timezone') ?: config('app.timezone'),
                'currency' => Setting::get('currency') ?: 'KES',
                'logo_url' => $this->resolvePublicImageUrl(Setting::get('school_logo')),
                'login_background_url' => $this->resolvePublicImageUrl(Setting::get('login_background')),
                'colors' => $this->portalColors(),
                'enabled_modules' => $enabledModules,
                'system_version' => Setting::get('system_version'),
            ],
        ]);
    }

    public function academicYears(Request $request)
    {
        $this->assertSettingsAccess($request);

        $years = AcademicYear::query()
            ->orderByDesc('year')
            ->get()
            ->map(fn (AcademicYear $y) => [
                'id' => $y->id,
                'year' => (int) $y->year,
                'is_active' => (bool) $y->is_active,
                'label' => (string) $y->year,
            ]);

        return response()->json(['success' => true, 'data' => $years]);
    }

    public function terms(Request $request)
    {
        $this->assertSettingsAccess($request);

        $request->validate([
            'academic_year_id' => ['sometimes', 'integer', 'exists:academic_years,id'],
        ]);

        $query = Term::query()->with('academicYear')->orderBy('name');

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', (int) $request->academic_year_id);
        }

        $terms = $query->get()->map(fn (Term $t) => [
            'id' => $t->id,
            'name' => $t->name,
            'academic_year_id' => (int) $t->academic_year_id,
            'academic_year' => $t->academicYear?->year,
            'is_current' => (bool) $t->is_current,
            'opening_date' => $t->opening_date?->format('Y-m-d'),
            'closing_date' => $t->closing_date?->format('Y-m-d'),
            'expected_school_days' => $t->expected_school_days,
        ]);

        return response()->json(['success' => true, 'data' => $terms]);
    }

    /**
     * All classrooms (admin settings view — not teacher-scoped).
     */
    public function classes(Request $request)
    {
        $this->assertSettingsAccess($request);

        $classes = Classroom::query()
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'level' => $c->level ?? null,
                'code' => $c->code ?? null,
            ]);

        return response()->json(['success' => true, 'data' => $classes]);
    }

    public function streams(Request $request, int $classId)
    {
        $this->assertSettingsAccess($request);

        $streams = Stream::query()
            ->where('classroom_id', $classId)
            ->orderBy('name')
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'classroom_id' => (int) $s->classroom_id,
            ]);

        return response()->json(['success' => true, 'data' => $streams]);
    }

    public function subjects(Request $request)
    {
        $this->assertSettingsAccess($request);

        $subjects = Subject::query()
            ->orderBy('name')
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'code' => $s->code ?? null,
                'learning_area' => $s->learning_area ?? null,
                'is_active' => (bool) $s->is_active,
                'is_optional' => (bool) $s->is_optional,
            ]);

        return response()->json(['success' => true, 'data' => $subjects]);
    }

    public function gradingSchemes(Request $request)
    {
        $this->assertSettingsAccess($request);

        $schemes = GradingScheme::query()
            ->with(['bands' => fn ($q) => $q->orderByDesc('min')])
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get()
            ->map(fn (GradingScheme $scheme) => [
                'id' => $scheme->id,
                'name' => $scheme->name,
                'type' => $scheme->type,
                'is_default' => (bool) $scheme->is_default,
                'bands' => $scheme->bands->map(fn (GradingBand $b) => [
                    'id' => $b->id,
                    'min' => $b->min,
                    'max' => $b->max,
                    'label' => $b->label,
                    'descriptor' => $b->descriptor,
                    'rank' => $b->rank,
                ])->values(),
            ]);

        $examTypes = ExamType::query()
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'default_min_mark', 'default_max_mark'])
            ->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'code' => $t->code,
                'default_min_mark' => $t->default_min_mark,
                'default_max_mark' => $t->default_max_mark,
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'schemes' => $schemes,
                'exam_types' => $examTypes,
            ],
        ]);
    }

    /**
     * Roles and permissions (read-only).
     */
    public function roles(Request $request)
    {
        $this->assertSettingsAccess($request);

        $roles = Role::query()
            ->with('permissions')
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'permissions_count' => $role->permissions->count(),
                'permissions' => $role->permissions
                    ->pluck('name')
                    ->sort()
                    ->values(),
            ]);

        return response()->json(['success' => true, 'data' => $roles]);
    }

    protected function assertSettingsAccess(Request $request): void
    {
        $user = $request->user();
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if ($user->hasAnyRole(['Super Admin', 'Admin', 'Secretary'])) {
            return;
        }

        if ($user->can('settings.view') || $user->getAllPermissions()->contains('name', 'settings.view')) {
            return;
        }

        abort(403, 'You do not have access to settings.');
    }

    private function resolvePublicImageUrl(?string $filename): ?string
    {
        if ($filename === null || $filename === '') {
            return null;
        }

        $filename = ltrim($filename, '/');

        if (function_exists('public_images_path') && file_exists(public_images_path($filename))) {
            return public_image_url($filename);
        }

        $disk = config('filesystems.public_disk', 'public');
        if (Storage::disk($disk)->exists($filename)) {
            return url(Storage::disk($disk)->url($filename));
        }

        return null;
    }

    private function portalColors(): array
    {
        $defaults = [
            'primary' => '#004A99',
            'secondary' => '#14b8a6',
            'success' => '#059669',
            'warning' => '#d97706',
            'error' => '#dc2626',
        ];

        $keys = [
            'primary' => 'finance_primary_color',
            'secondary' => 'finance_secondary_color',
            'success' => 'finance_success_color',
            'warning' => 'finance_warning_color',
            'error' => 'finance_danger_color',
        ];

        $out = [];
        foreach ($keys as $jsonKey => $settingKey) {
            $v = trim((string) (Setting::get($settingKey) ?? ''));
            $out[$jsonKey] = ($v !== '' && preg_match('/^#[0-9A-Fa-f]{6}$/', $v)) ? $v : $defaults[$jsonKey];
        }

        return $out;
    }
}
