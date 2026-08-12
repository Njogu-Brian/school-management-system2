<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParentForcedAction;
use App\Models\ParentInfo;
use App\Services\ParentCredentialsService;
use Illuminate\Http\Request;

class ApiParentForcedActionsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (! $user->parent_id && ! $user->shouldScopeAsParent() && ! $user->isLinkedParentAccount()) {
            return response()->json(['success' => false, 'message' => 'Not a parent account.'], 403);
        }

        $query = ParentForcedAction::query()
            ->pending()
            ->orderBy('priority')
            ->orderBy('id');

        if ($user->parent_id) {
            $query->where(function ($q) use ($user) {
                $q->where('parent_info_id', $user->parent_id)
                    ->orWhere('user_id', $user->id);
            });
        } else {
            $query->where('user_id', $user->id);
        }

        $actions = $query->get()->map(fn (ParentForcedAction $a) => [
            'id' => $a->id,
            'type' => $a->type,
            'title' => $a->title,
            'payload' => $a->payload,
            'priority' => $a->priority,
            'blocking' => (bool) $a->blocking,
            'due_at' => $a->due_at?->toIso8601String(),
        ])->values();

        // Also surface legacy password / profile flags as synthetic actions if no rows yet.
        $list = $actions->all();
        if ($user->must_change_password && ! collect($list)->contains(fn ($a) => $a['type'] === ParentForcedAction::TYPE_CHANGE_PASSWORD)) {
            array_unshift($list, [
                'id' => 0,
                'type' => ParentForcedAction::TYPE_CHANGE_PASSWORD,
                'title' => 'Change your password',
                'payload' => null,
                'priority' => 10,
                'blocking' => true,
                'due_at' => null,
            ]);
        }
        if ($user->parent_profile_review_required && ! collect($list)->contains(fn ($a) => in_array($a['type'], [
            ParentForcedAction::TYPE_PROFILE_REVIEW,
            ParentForcedAction::TYPE_UPLOAD_DOCUMENTS,
        ], true))) {
            $list[] = [
                'id' => 0,
                'type' => ParentForcedAction::TYPE_PROFILE_REVIEW,
                'title' => 'Update family profile & upload documents',
                'payload' => [
                    'require_documents' => [
                        'student_profile_photo',
                        'student_birth_certificate',
                        'parent_id_card',
                    ],
                ],
                'priority' => 20,
                'blocking' => true,
                'due_at' => null,
            ];
        }

        usort($list, fn ($a, $b) => ($a['priority'] <=> $b['priority']) ?: (($a['id'] <=> $b['id'])));

        return response()->json([
            'success' => true,
            'data' => array_values($list),
        ]);
    }

    public function complete(Request $request, int $id, ParentCredentialsService $credentials)
    {
        $user = $request->user();
        $payload = $request->input('payload');

        if ($id === 0) {
            // Synthetic legacy actions — completion handled by dedicated endpoints.
            return response()->json([
                'success' => false,
                'message' => 'Complete this step using the dedicated password or profile screen.',
            ], 422);
        }

        $action = ParentForcedAction::query()->pending()->findOrFail($id);
        if ((int) $action->parent_info_id !== (int) $user->parent_id && (int) $action->user_id !== (int) $user->id) {
            abort(403);
        }

        $action->markCompleted(is_array($payload) ? $payload : null);

        if ($action->type === ParentForcedAction::TYPE_CHANGE_PASSWORD) {
            $user->forceFill([
                'must_change_password' => false,
                'password_changed_at' => now(),
            ])->save();
        }

        if (in_array($action->type, [
            ParentForcedAction::TYPE_PROFILE_REVIEW,
            ParentForcedAction::TYPE_UPLOAD_DOCUMENTS,
        ], true)) {
            $user->forceFill([
                'parent_profile_review_required' => false,
                'profile_completed_at' => now(),
            ])->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Action completed.',
            'data' => [
                'stage' => $credentials->stageForUser($user->fresh()),
            ],
        ]);
    }

    /**
     * Staff: assign a forced action to a parent family.
     */
    public function storeForParent(Request $request)
    {
        $actor = $request->user();
        if (! $actor->hasAnyRole(['Super Admin', 'Admin', 'Secretary'])) {
            abort(403);
        }

        $validated = $request->validate([
            'parent_info_id' => 'required|integer|exists:parent_info,id',
            'type' => 'required|string|max:64',
            'title' => 'required|string|max:190',
            'payload' => 'nullable|array',
            'priority' => 'nullable|integer|min:1|max:1000',
            'blocking' => 'nullable|boolean',
        ]);

        $parent = ParentInfo::findOrFail($validated['parent_info_id']);
        $user = \App\Models\User::query()->where('parent_id', $parent->id)->orderBy('id')->first();

        $action = ParentForcedAction::create([
            'user_id' => $user?->id,
            'parent_info_id' => $parent->id,
            'type' => $validated['type'],
            'title' => $validated['title'],
            'payload' => $validated['payload'] ?? null,
            'priority' => $validated['priority'] ?? 100,
            'blocking' => $request->boolean('blocking', true),
            'status' => ParentForcedAction::STATUS_PENDING,
            'created_by' => $actor->id,
        ]);

        if (in_array($action->type, [
            ParentForcedAction::TYPE_PROFILE_REVIEW,
            ParentForcedAction::TYPE_UPLOAD_DOCUMENTS,
        ], true) && $user) {
            $user->forceFill([
                'parent_profile_review_required' => true,
                'profile_completed_at' => null,
            ])->save();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $action->id,
                'type' => $action->type,
                'title' => $action->title,
            ],
        ], 201);
    }
}
