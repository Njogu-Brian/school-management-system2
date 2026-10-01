<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\SchoolRegistry;
use App\Services\CpanelMysqlProvisioner;
use App\Services\TenantProvisioningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OperatorSchoolController extends Controller
{
    public function index(): View
    {
        $schools = SchoolRegistry::query()->orderByDesc('id')->paginate(25);

        return view('operator.schools.index', compact('schools'));
    }

    public function create(): View
    {
        return view('operator.schools.create', [
            'cpanelReady' => app(CpanelMysqlProvisioner::class)->isConfigured(),
            'defaultFee' => config('edulynk.default_monthly_fee'),
        ]);
    }

    public function store(Request $request, TenantProvisioningService $provisioning): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:64', 'alpha_dash'],
            'code' => ['nullable', 'string', 'max:32'],
            'contact_email' => ['nullable', 'email'],
            'contact_phone' => ['nullable', 'string', 'max:32'],
            'primary_color' => ['nullable', 'string', 'max:16'],
            'secondary_color' => ['nullable', 'string', 'max:16'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email'],
            'admin_phone' => ['nullable', 'string', 'max:32'],
            'monthly_fee' => ['nullable', 'numeric', 'min:0'],
            'db_name' => ['nullable', 'string', 'max:64'],
            'db_username' => ['nullable', 'string', 'max:64'],
            'db_password' => ['nullable', 'string', 'max:128'],
            'manual_db' => ['nullable', 'boolean'],
        ]);

        $input = [
            'name' => $data['name'],
            'slug' => $data['slug'] ?? null,
            'code' => $data['code'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'primary_color' => $data['primary_color'] ?? '#0d6efd',
            'secondary_color' => $data['secondary_color'] ?? '#198754',
            'admin_name' => $data['admin_name'],
            'admin_email' => $data['admin_email'],
            'admin_phone' => $data['admin_phone'] ?? null,
            'monthly_fee' => $data['monthly_fee'] ?? config('edulynk.default_monthly_fee'),
            'seed_classes' => true,
        ];

        if ($request->boolean('manual_db')) {
            $request->validate([
                'db_name' => ['required', 'string'],
                'db_username' => ['required', 'string'],
                'db_password' => ['required', 'string'],
            ]);
            $input['db_name'] = $data['db_name'];
            $input['db_username'] = $data['db_username'];
            $input['db_password'] = $data['db_password'];
            $input['skip_cpanel'] = true;
        }

        try {
            $result = $provisioning->provisionNewSchool($input);
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['provision' => $e->getMessage()]);
        }

        return redirect()
            ->route('operator.schools.show', $result['school'])
            ->with('status', 'School provisioned.')
            ->with('admin_password', $result['admin_password']);
    }

    public function show(SchoolRegistry $school, TenantProvisioningService $provisioning): View
    {
        $provisioning->refreshStudentCount($school);
        $school->refresh();
        $subscriptions = $school->subscriptions()->orderByDesc('period')->limit(12)->get();
        $payments = $school->payments()->orderByDesc('paid_at')->limit(20)->get();
        $invite = "Welcome to EduLynk!\n\nSchool: {$school->name}\nApp school code: {$school->code}\nWeb portal: {$school->tenantWebUrl()}\n\nOpen the EduLynk app, enter the school code once, then sign in with the credentials from your school.";

        return view('operator.schools.show', compact('school', 'subscriptions', 'payments', 'invite'));
    }

    public function suspend(SchoolRegistry $school, TenantProvisioningService $provisioning): RedirectResponse
    {
        $provisioning->suspend($school);

        return back()->with('status', 'School suspended.');
    }

    public function activate(SchoolRegistry $school, TenantProvisioningService $provisioning): RedirectResponse
    {
        $provisioning->activate($school);

        return back()->with('status', 'School activated.');
    }
}
