<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ParentCredentialsService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Minimum identity completion for the signed-in parent slot only
 * (name + phone) plus child name/DOB when those are missing.
 */
class ApiParentIdentityGateController extends Controller
{
    public function __construct(private ParentCredentialsService $parents)
    {
    }

    public function show(Request $request)
    {
        $user = $request->user();
        if (! $user?->parent_id) {
            return response()->json([
                'success' => true,
                'data' => $this->parents->identityGateForUser($user),
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $this->parents->identityGateForUser($user),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        if (! $user?->parent_id) {
            return response()->json([
                'success' => false,
                'message' => 'This account is not linked to a parent record.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'children' => 'sometimes|array',
            'children.*.id' => 'required|integer',
            'children.*.first_name' => 'required|string|max:255',
            'children.*.last_name' => 'required|string|max:255',
            'children.*.dob' => 'required|date',
        ]);

        try {
            $gate = $this->parents->applyIdentityGate($user, $validated);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        if ($gate['required']) {
            return response()->json([
                'success' => false,
                'message' => 'Please complete your name, phone number, and each child name and date of birth.',
                'data' => $gate,
            ], 422);
        }

        $user->refresh()->load(['roles', 'roles.permissions', 'staff']);

        return response()->json([
            'success' => true,
            'message' => 'Details saved.',
            'data' => [
                'gate' => $gate,
                'user' => app(AuthApiController::class)->formatUserForApiPublic($user),
            ],
        ]);
    }
}
