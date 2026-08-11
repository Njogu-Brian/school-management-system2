<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\ParentCredentialsService;
use Illuminate\Http\Request;

/**
 * Web ERP: manage parent Users-app login credentials from the student profile.
 */
class ParentCredentialsController extends Controller
{
    public function __construct(private ParentCredentialsService $credentials)
    {
        $this->middleware('role:Super Admin|Admin|Secretary');
    }

    public function reset(Request $request, int $id)
    {
        $student = Student::with('parent')->findOrFail($id);

        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'password_option' => 'required|in:random,custom',
            'new_password' => 'nullable|string|min:6',
            'share' => 'nullable|boolean',
        ]);

        if ($validated['password_option'] === 'custom' && empty($validated['new_password'])) {
            return back()->with('error', 'Enter a custom password (min 6 characters).');
        }

        try {
            $result = $this->credentials->resetPassword(
                $student,
                (int) $validated['user_id'],
                $validated['password_option'],
                $validated['new_password'] ?? null,
                $request->boolean('share'),
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $user = $result['user'];
        $via = $result['shared_via'];
        $shareNote = $via === []
            ? 'Share this temporary password securely (no email/SMS sent).'
            : 'Shared via: ' . implode(', ', $via) . '.';

        return back()
            ->with('success', "Password reset for {$user->name}. Login: " . ($user->email ?: $user->phone_number ?: '—') . ". Temporary password: {$result['temporary_password']}. {$shareNote} They must change it on next sign-in. App PIN stays on their device.")
            ->with('parent_temp_password', $result['temporary_password']);
    }

    public function requirePasswordChange(Request $request, int $id)
    {
        $student = Student::with('parent')->findOrFail($id);

        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        try {
            $user = $this->credentials->requirePasswordChange($student, (int) $validated['user_id']);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "{$user->name} will be asked to change password on next sign-in.");
    }
}
