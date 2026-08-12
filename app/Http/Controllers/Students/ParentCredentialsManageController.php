<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Jobs\SendParentCredentialsBulkJob;
use App\Models\ParentForcedAction;
use App\Models\ParentInfo;
use App\Models\User;
use App\Services\ParentCredentialsService;
use Illuminate\Http\Request;

class ParentCredentialsManageController extends Controller
{
    public function __construct(private ParentCredentialsService $credentials)
    {
        $this->middleware('role:Super Admin|Admin|Secretary');
    }

    public function index(Request $request)
    {
        $search = $request->query('q');
        $stage = $request->query('stage');
        $counts = $this->credentials->funnelCounts($search);
        $rows = $this->credentials->familyRows($search, $stage ?: null);

        return view('parents.credentials.index', [
            'counts' => $counts,
            'rows' => $rows,
            'search' => $search,
            'stage' => $stage,
            'stages' => [
                ParentCredentialsService::STAGE_NOT_PROVISIONED => 'Not provisioned',
                ParentCredentialsService::STAGE_CREDENTIALS_SENT => 'Credentials sent',
                ParentCredentialsService::STAGE_PASSWORD_PENDING => 'Password pending',
                ParentCredentialsService::STAGE_PROFILE_PENDING => 'Profile pending',
                ParentCredentialsService::STAGE_COMPLETE => 'Complete',
            ],
        ]);
    }

    public function send(Request $request)
    {
        $validated = $request->validate([
            'parent_info_id' => 'required|integer|exists:parent_info,id',
            'channels' => 'required|array|min:1',
            'channels.*' => 'in:sms,whatsapp,email',
        ]);

        $parent = ParentInfo::findOrFail($validated['parent_info_id']);

        try {
            $result = $this->credentials->provisionAndShare($parent, $validated['channels']);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $via = $result['shared_via'] === []
            ? 'Account ready but no message delivered — check phone/email.'
            : 'Sent via: '.implode(', ', $result['shared_via']).'.';

        return back()->with(
            'success',
            "Credentials for {$result['user']->name}. Login: ".($result['user']->email ?: $result['user']->phone_number).
            ". Temp password: {$result['temporary_password']}. {$via}"
        )->with('parent_temp_password', $result['temporary_password']);
    }

    public function bulkSend(Request $request)
    {
        $validated = $request->validate([
            'parent_info_ids' => 'required|array|min:1',
            'parent_info_ids.*' => 'integer|exists:parent_info,id',
            'channels' => 'required|array|min:1',
            'channels.*' => 'in:sms,whatsapp,email',
        ]);

        $ids = array_values(array_unique(array_map('intval', $validated['parent_info_ids'])));

        if (class_exists(SendParentCredentialsBulkJob::class) && count($ids) > 15) {
            SendParentCredentialsBulkJob::dispatch($ids, $validated['channels'], $request->user()->id);

            return back()->with('success', 'Bulk send queued for '.count($ids).' families.');
        }

        $ok = 0;
        $fail = 0;
        foreach ($ids as $pid) {
            try {
                $this->credentials->provisionAndShare(ParentInfo::findOrFail($pid), $validated['channels']);
                $ok++;
            } catch (\Throwable $e) {
                $fail++;
                \Log::warning('Bulk parent credentials failed', ['parent_info_id' => $pid, 'error' => $e->getMessage()]);
            }
        }

        return back()->with('success', "Bulk send done. Success: {$ok}. Failed: {$fail}.");
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'parent_info_id' => 'required|integer|exists:parent_info,id',
            'channels' => 'nullable|array',
            'channels.*' => 'in:sms,whatsapp,email',
            'share' => 'nullable|boolean',
        ]);

        $parent = ParentInfo::findOrFail($validated['parent_info_id']);
        $child = $this->credentials->pickPasswordChild($parent);
        if (! $child) {
            return back()->with('error', 'No active child for this family.');
        }

        try {
            $result = $this->credentials->resetPassword(
                $child,
                null,
                'formula',
                null,
                $request->boolean('share', true),
                $validated['channels'] ?? ['sms'],
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with(
            'success',
            "Password reset. Temp: {$result['temporary_password']}. Via: ".implode(', ', $result['shared_via'] ?: ['none'])
        )->with('parent_temp_password', $result['temporary_password']);
    }

    public function sendPinHelp(Request $request)
    {
        $validated = $request->validate([
            'parent_info_id' => 'required|integer|exists:parent_info,id',
            'channels' => 'required|array|min:1',
            'channels.*' => 'in:sms,whatsapp,email',
        ]);

        $parent = ParentInfo::findOrFail($validated['parent_info_id']);
        $via = $this->credentials->sendPinResetInstructions($parent, $validated['channels']);

        return back()->with(
            'success',
            $via === []
                ? 'Could not deliver PIN help message.'
                : 'PIN reset instructions sent via: '.implode(', ', $via)
        );
    }

    public function assignForcedAction(Request $request)
    {
        $validated = $request->validate([
            'parent_info_id' => 'required|integer|exists:parent_info,id',
            'type' => 'required|in:profile_review,upload_documents,custom_form,acknowledge',
            'title' => 'required|string|max:190',
        ]);

        $parent = ParentInfo::findOrFail($validated['parent_info_id']);
        $user = User::query()->where('parent_id', $parent->id)->orderBy('id')->first();

        ParentForcedAction::create([
            'user_id' => $user?->id,
            'parent_info_id' => $parent->id,
            'type' => $validated['type'],
            'title' => $validated['title'],
            'priority' => 50,
            'blocking' => true,
            'status' => ParentForcedAction::STATUS_PENDING,
            'created_by' => $request->user()->id,
        ]);

        if (in_array($validated['type'], ['profile_review', 'upload_documents'], true) && $user) {
            $user->forceFill([
                'parent_profile_review_required' => true,
                'profile_completed_at' => null,
            ])->save();
        }

        return back()->with('success', 'Forced action assigned. Parent will see it on next sign-in.');
    }
}
