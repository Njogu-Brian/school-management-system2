<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParentInfo;
use App\Models\Student;
use App\Models\User;
use App\Services\CommunicationService;
use App\Services\PhoneNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Admin reset / share of parent portal login credentials.
 * App device PIN is local to the phone and cannot be reset from the server.
 */
class ApiParentCredentialsController extends Controller
{
    public function __construct(private CommunicationService $comm)
    {
    }

    protected function assertManageAccess(Request $request): void
    {
        $user = $request->user();
        if (! $user || ! $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary'])) {
            abort(403, 'You do not have permission to manage parent credentials.');
        }
    }

    /**
     * GET /api/students/{id}/parent-credentials
     */
    public function show(Request $request, int $id)
    {
        $this->assertManageAccess($request);
        $student = Student::with('parent')->findOrFail($id);
        $parent = $student->parent;
        if (! $parent) {
            return response()->json([
                'success' => true,
                'data' => [
                    'parent_info_id' => null,
                    'accounts' => [],
                ],
            ]);
        }

        $accounts = User::query()
            ->where('parent_id', $parent->id)
            ->get(['id', 'name', 'email', 'phone_number', 'must_change_password'])
            ->map(fn (User $u) => [
                'user_id' => $u->id,
                'name' => $u->name,
                'login' => $u->email,
                'phone' => $u->phone_number,
                'must_change_password' => (bool) $u->must_change_password,
            ])
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'data' => [
                'parent_info_id' => $parent->id,
                'accounts' => $accounts,
            ],
        ]);
    }

    /**
     * POST /api/students/{id}/parent-credentials/reset
     * Body: { user_id?: int, password_option: random|custom, new_password?: string, share?: bool }
     */
    public function reset(Request $request, int $id)
    {
        $this->assertManageAccess($request);
        $student = Student::with('parent')->findOrFail($id);
        $parent = $student->parent;
        if (! $parent) {
            return response()->json(['success' => false, 'message' => 'Student has no parent record.'], 422);
        }

        $validated = $request->validate([
            'user_id' => 'nullable|integer|exists:users,id',
            'password_option' => 'required|in:random,custom',
            'new_password' => 'nullable|string|min:6',
            'share' => 'nullable|boolean',
        ]);

        $query = User::query()->where('parent_id', $parent->id);
        if (! empty($validated['user_id'])) {
            $query->where('id', (int) $validated['user_id']);
        }
        $user = $query->first();
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'No parent app account linked. Ask the parent to claim access, or create an account first.',
            ], 422);
        }

        if ($validated['password_option'] === 'custom' && ! empty($validated['new_password'])) {
            $newPassword = $validated['new_password'];
        } else {
            $newPassword = Str::random(8);
        }

        $user->update([
            'password' => Hash::make($newPassword),
            'must_change_password' => true,
        ]);

        $share = (bool) ($validated['share'] ?? false);
        $sharedVia = [];
        if ($share) {
            $sharedVia = $this->shareCredentials($user, $parent, $newPassword);
        }

        return response()->json([
            'success' => true,
            'message' => $share
                ? 'Password reset. Share channels attempted where contact details exist.'
                : 'Password reset. Share the temporary password securely.',
            'data' => [
                'user_id' => $user->id,
                'login' => $user->email,
                'temporary_password' => $newPassword,
                'must_change_password' => true,
                'shared_via' => $sharedVia,
                'note' => 'Device app PIN stays on the parent phone — they reset it in Settings.',
            ],
        ]);
    }

    /**
     * @return list<string>
     */
    protected function shareCredentials(User $user, ParentInfo $parent, string $password): array
    {
        $shared = [];
        $schoolName = \Illuminate\Support\Facades\DB::table('settings')->where('key', 'school_name')->value('value')
            ?? config('app.name', 'School');
        $body = "{$schoolName}: Your parent portal login is {$user->email}. Temporary password: {$password}. Please change it after signing in.";

        if ($user->email) {
            try {
                $this->comm->sendEmail(
                    'parent',
                    $parent->id,
                    $user->email,
                    'Parent portal password reset',
                    nl2br(e($body)),
                    null
                );
                $shared[] = 'email';
            } catch (\Throwable $e) {
                Log::warning('Parent credentials email failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $phone = $user->phone_number
            ?: ($parent->primary_contact_phone ?? $parent->father_phone ?? $parent->mother_phone ?? $parent->guardian_phone);
        if ($phone) {
            try {
                $phoneService = app(PhoneNumberService::class);
                $smsPhone = $phoneService->formatWithCountryCode($phone, '+254');
                $result = $this->comm->sendSMS('parent', $parent->id, $smsPhone, $body, 'Password reset');
                if ($result['success'] ?? false) {
                    $shared[] = 'sms';
                }
            } catch (\Throwable $e) {
                Log::warning('Parent credentials SMS failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $shared;
    }
}
