<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Services\GoogleLinkPromptService;
use Illuminate\Http\Request;

class ForceGoogleLinkController extends Controller
{
    public function __construct(private GoogleLinkPromptService $service)
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

        return view('users.force-google-link', [
            'users' => $users,
            'group' => $group,
            'search' => $search,
            'mode' => $this->service->mode(),
        ]);
    }

    public function updateMode(Request $request)
    {
        $validated = $request->validate([
            'google_link_prompt_mode' => 'required|in:off,all,selected',
        ]);

        $this->service->setMode($validated['google_link_prompt_mode']);

        return back()->with('success', 'Google link prompt mode updated. Mobile apps pick this up on the next sign-in.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'group' => 'required|in:staff,parents,all',
            'apply_to' => 'required|in:selected,all_matching,clear_selected',
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

        if ($validated['apply_to'] === 'clear_selected') {
            $count = $this->service->clearRequireLink($ids);

            return back()->with('success', "Cleared Google link requirement for {$count} user(s).");
        }

        $count = $this->service->requireLink($ids);

        return back()->with(
            'success',
            "{$count} user(s) will be asked to link Google after their next password/OTP sign-in (they can still skip)."
        );
    }
}
