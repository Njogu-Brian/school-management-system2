<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParentInfo;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthApiController extends Controller
{
    /**
     * Login - returns token and user for mobile app.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $email = strtolower(trim($request->email));

        // Find user (case-insensitive)
        $user = User::whereRaw('LOWER(TRIM(email)) = ?', [$email])->first();

        if (!$user) {
            $staff = Staff::whereNotNull('work_email')
                ->whereRaw('LOWER(TRIM(work_email)) = ?', [$email])
                ->first();
            if ($staff && $staff->user_id) {
                $user = User::find($staff->user_id);
            }
        }

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'The provided credentials are incorrect.',
            ], 401);
        }

        $user->load('roles', 'roles.permissions', 'staff');

        // Revoke other tokens for this user (single device) or keep many - we'll keep one per login
        $user->tokens()->delete();

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'user' => $this->formatUserForApi($user),
            ],
        ]);
    }

    /**
     * Get current user (profile) - for auth:sanctum.
     */
    public function user(Request $request)
    {
        $user = $request->user();
        $user->load('roles', 'roles.permissions', 'staff');

        return response()->json([
            'success' => true,
            'data' => $this->formatUserForApi($user),
        ]);
    }

    /**
     * Logout - revoke current token.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Format user for API response (matches mobile app User type).
     */
    protected function formatUserForApi(User $user): array
    {
        $roleName = $user->roles->first()?->name ?? 'Teacher';
        $permissions = $user->getAllPermissions()->pluck('name')->values()->toArray();

        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $roleName,
            'permissions' => $permissions,
        ];

        $staff = $user->staff;
        if ($staff) {
            $data['staff_id'] = $staff->id;
            if (!empty($staff->phone_number)) {
                $data['phone'] = $staff->phone_number;
            }
            if (in_array(strtolower($roleName), ['teacher', 'senior teacher', 'supervisor'])) {
                $data['teacher_id'] = $staff->id;
            }
            $data['avatar'] = $staff->photo_url ?: null;
        }

        // Parent: User has parent_id -> parent_info
        if ($user->parent_id) {
            $data['parent_id'] = $user->parent_id;
        }

        // Student: linked via parent_id on students table (students.parent_id -> parent_info)
        // No user_id column on students; student users are rare - skip student_id if schema lacks it

        return $data;
    }
}
