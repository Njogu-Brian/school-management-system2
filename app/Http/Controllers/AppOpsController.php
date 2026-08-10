<?php

namespace App\Http\Controllers;

use App\Models\AppClientIssue;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AppOpsController extends Controller
{
    public function adoption(Request $request)
    {
        abort_unless(
            $request->user()?->hasAnyRole(['Super Admin', 'Admin', 'Secretary']),
            403
        );

        $audience = $request->input('audience', 'staff');
        $status = $request->input('status', 'all');
        $days = max(1, min(90, (int) $request->input('days', 7)));
        $q = trim((string) $request->input('q', ''));

        $query = User::query()->with('roles');
        if ($audience === 'parents') {
            $query->whereNotNull('parent_id')
                ->whereHas('roles', fn ($r) => $r->whereIn('name', ['Parent', 'Guardian']));
        } else {
            $staffUserIds = Staff::query()->whereNotNull('user_id')->pluck('user_id')->unique()->filter()->values()->all();
            $query->where(function ($w) use ($staffUserIds) {
                $w->whereIn('id', $staffUserIds)
                    ->orWhereHas('roles', fn ($r) => $r->whereIn('name', [
                        'Teacher', 'teacher', 'Senior Teacher', 'senior teacher',
                        'Admin', 'Super Admin', 'Secretary', 'Accountant', 'Finance', 'Driver',
                    ]));
            });
        }

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        if ($status === 'never') {
            $query->whereNull('last_login_at');
        } elseif ($status === 'used') {
            $query->whereNotNull('last_login_at');
        } elseif ($status === 'active') {
            $query->where('last_seen_at', '>=', now()->subDays($days));
        }

        if ($request->boolean('export')) {
            return $this->exportAdoptionCsv(
                (clone $query)->orderByRaw('last_login_at IS NULL')->orderByDesc('last_seen_at')->limit(5000)->get(),
                $audience
            );
        }

        $users = $query->orderByRaw('last_login_at IS NULL')->orderByDesc('last_seen_at')->paginate(50)->withQueryString();

        $base = User::query();
        if ($audience === 'parents') {
            $base->whereNotNull('parent_id')->whereHas('roles', fn ($r) => $r->whereIn('name', ['Parent', 'Guardian']));
        } else {
            $staffUserIds = Staff::query()->whereNotNull('user_id')->pluck('user_id')->unique()->filter()->values()->all();
            $base->where(function ($w) use ($staffUserIds) {
                $w->whereIn('id', $staffUserIds)
                    ->orWhereHas('roles', fn ($r) => $r->whereIn('name', [
                        'Teacher', 'teacher', 'Senior Teacher', 'senior teacher',
                        'Admin', 'Super Admin', 'Secretary', 'Accountant', 'Finance', 'Driver',
                    ]));
            });
        }

        $summary = [
            'never' => (clone $base)->whereNull('last_login_at')->count(),
            'used' => (clone $base)->whereNotNull('last_login_at')->count(),
            'active' => (clone $base)->where('last_seen_at', '>=', now()->subDays($days))->count(),
            'total' => (clone $base)->count(),
        ];

        return view('communication.app_adoption', compact('users', 'audience', 'status', 'days', 'q', 'summary'));
    }

    public function issues(Request $request)
    {
        abort_unless(
            $request->user()?->hasAnyRole(['Super Admin', 'Admin', 'Secretary']),
            403
        );

        $app = $request->input('app');
        $query = AppClientIssue::query()->with('user:id,name,email')->orderByDesc('id');
        if (in_array($app, ['users', 'admin'], true)) {
            $query->where('app', $app);
        }
        $issues = $query->paginate(40)->withQueryString();

        return view('communication.app_issues', compact('issues', 'app'));
    }

    /**
     * @param  \Illuminate\Support\Collection<int, User>  $users
     */
    protected function exportAdoptionCsv($users, string $audience): StreamedResponse
    {
        $filename = 'app-adoption-'.$audience.'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($users) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['user_id', 'name', 'email', 'last_login_at', 'last_seen_at', 'roles']);
            foreach ($users as $u) {
                fputcsv($out, [
                    $u->id,
                    $u->name,
                    $u->email,
                    optional($u->last_login_at)?->toDateTimeString(),
                    optional($u->last_seen_at)?->toDateTimeString(),
                    $u->getRoleNames()->implode('|'),
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
