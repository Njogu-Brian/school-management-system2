<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\ParentCredentialsService;
use Illuminate\Http\Request;

/**
 * Admin reset / share of parent portal login credentials (mobile Admin API).
 */
class ApiParentCredentialsController extends Controller
{
    public function __construct(private ParentCredentialsService $credentials)
    {
    }

    protected function assertManageAccess(Request $request): void
    {
        $user = $request->user();
        if (! $user || ! $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary'])) {
            abort(403, 'You do not have permission to manage parent credentials.');
        }
    }

    public function show(Request $request, int $id)
    {
        $this->assertManageAccess($request);
        $student = Student::with('parent')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->credentials->listForStudent($student),
        ]);
    }

    public function reset(Request $request, int $id)
    {
        $this->assertManageAccess($request);
        $student = Student::with('parent')->findOrFail($id);

        $validated = $request->validate([
            'user_id' => 'nullable|integer|exists:users,id',
            'password_option' => 'required|in:random,custom,formula,admission',
            'new_password' => 'nullable|string|min:6',
            'share' => 'nullable|boolean',
        ]);

        try {
            $result = $this->credentials->resetPassword(
                $student,
                isset($validated['user_id']) ? (int) $validated['user_id'] : null,
                $validated['password_option'],
                $validated['new_password'] ?? null,
                (bool) ($validated['share'] ?? false),
            );
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $user = $result['user'];
        $share = (bool) ($validated['share'] ?? false);

        return response()->json([
            'success' => true,
            'message' => $share
                ? 'Password reset. Share channels attempted where contact details exist.'
                : 'Password reset. Share the temporary password securely.',
            'data' => [
                'user_id' => $user->id,
                'login' => $user->email,
                'temporary_password' => $result['temporary_password'],
                'must_change_password' => true,
                'shared_via' => $result['shared_via'],
                'note' => 'Device app PIN stays on the parent phone — they reset it in Settings.',
            ],
        ]);
    }

    public function requirePasswordChange(Request $request, int $id)
    {
        $this->assertManageAccess($request);
        $student = Student::with('parent')->findOrFail($id);

        $validated = $request->validate([
            'user_id' => 'nullable|integer|exists:users,id',
        ]);

        try {
            $user = $this->credentials->requirePasswordChange(
                $student,
                isset($validated['user_id']) ? (int) $validated['user_id'] : null,
            );
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Parent will be asked to change password on next sign-in.',
            'data' => [
                'user_id' => $user->id,
                'login' => $user->email,
                'must_change_password' => true,
            ],
        ]);
    }
}
