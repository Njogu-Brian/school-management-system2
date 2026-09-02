<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Services\ForcePasswordChangeService;
use Illuminate\Http\Request;

class ForcePasswordChangeController extends Controller
{
    public function __construct(private ForcePasswordChangeService $service)
    {
        $this->middleware('role:Super Admin|Admin|Secretary');
    }

    public function index(Request $request)
    {
        $group = $request->input('group', 'staff');
        if (! in_array($group, ['staff', 'parents', 'all'], true)) {
            $group = 'staff';
        }
        $search = $request->input('q');
        $users = $this->service->serializePage(
            $this->service->queryUsers($group, $search)
        );

        return view('users.force-password-change', [
            'users' => $users,
            'group' => $group,
            'search' => $search,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'group' => 'required|in:staff,parents,all',
            'apply_to' => 'required|in:selected,all_matching',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        if ($validated['apply_to'] === 'all_matching') {
            $ids = $this->service->queryUsers($validated['group'], $request->input('q'))
                ->limit(5000)
                ->pluck('id')
                ->all();
        } else {
            $ids = array_map('intval', $validated['user_ids'] ?? []);
        }

        if ($ids === []) {
            return back()->with('error', 'Select at least one user, or choose “everyone in this list”.');
        }

        $count = $this->service->requireChange($ids);

        return back()->with('success', "{$count} user(s) will be asked to change their password on next login (web and mobile).");
    }
}
