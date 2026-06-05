<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * In-app notifications for the mobile app (Laravel database notifications when available).
 */
class ApiNotificationController extends Controller
{
    public function index(Request $request)
    {
        $perPage = max(1, min(100, (int) $request->input('per_page', 50)));
        $user = $request->user();

        if (! Schema::hasTable('notifications')) {
            return $this->emptyPaginated($perPage);
        }

        /** @var User $user */
        $query = $user->notifications()->orderByDesc('created_at');

        if ($request->boolean('is_read') === false) {
            $query->whereNull('read_at');
        } elseif ($request->boolean('is_read') === true) {
            $query->whereNotNull('read_at');
        }

        if ($request->filled('category')) {
            $category = $request->string('category');
            $query->where('data->category', $category);
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('data->title', 'like', "%{$search}%")
                    ->orWhere('data->body', 'like', "%{$search}%")
                    ->orWhere('data->message', 'like', "%{$search}%");
            });
        }

        $paginated = $query->paginate($perPage);

        $data = $paginated->getCollection()->map(fn ($n) => $this->formatNotification($n))->values();

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $data,
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'from' => $paginated->firstItem(),
                'to' => $paginated->lastItem(),
            ],
        ]);
    }

    public function markRead(Request $request, string $id)
    {
        $user = $request->user();
        if (! Schema::hasTable('notifications')) {
            return response()->json(['success' => true, 'message' => 'OK']);
        }

        /** @var User $user */
        $n = $user->notifications()->where('id', $id)->firstOrFail();
        $n->markAsRead();

        return response()->json([
            'success' => true,
            'data' => $this->formatNotification($n->fresh()),
        ]);
    }

    public function markAllRead(Request $request)
    {
        $user = $request->user();
        if (! Schema::hasTable('notifications')) {
            return response()->json(['success' => true, 'data' => ['count' => 0]]);
        }

        /** @var User $user */
        $count = $user->unreadNotifications()->count();
        $user->unreadNotifications->markAsRead();

        return response()->json([
            'success' => true,
            'data' => ['count' => $count],
        ]);
    }

    public function unreadCount(Request $request)
    {
        $user = $request->user();
        if (! Schema::hasTable('notifications')) {
            return response()->json(['success' => true, 'data' => ['count' => 0]]);
        }

        /** @var User $user */
        $count = $user->unreadNotifications()->count();

        return response()->json([
            'success' => true,
            'data' => ['count' => $count],
        ]);
    }

    public function destroy(Request $request, string $id)
    {
        $user = $request->user();
        if (! Schema::hasTable('notifications')) {
            return response()->json(['success' => true]);
        }

        /** @var User $user */
        $user->notifications()->where('id', $id)->delete();

        return response()->json(['success' => true]);
    }

    private function formatNotification($n): array
    {
        $payload = is_string($n->data) ? json_decode($n->data, true) : (array) $n->data;
        $title = $payload['title'] ?? $payload['subject'] ?? class_basename($n->type);
        $body = $payload['body'] ?? $payload['message'] ?? '';

        $category = $payload['category'] ?? $payload['module'] ?? 'general';
        $sourceModule = $payload['source_module'] ?? $payload['module'] ?? $category;
        $deepLink = $payload['deep_link'] ?? $payload['action_url'] ?? null;

        return [
            'id' => $n->id,
            'user_id' => $n->notifiable_id,
            'title' => (string) $title,
            'body' => (string) $body,
            'type' => $payload['type'] ?? 'info',
            'category' => (string) $category,
            'source_module' => (string) $sourceModule,
            'deep_link' => $deepLink,
            'data' => $payload,
            'is_read' => $n->read_at !== null,
            'created_at' => $n->created_at?->toIso8601String() ?? '',
            'read_at' => $n->read_at?->toIso8601String(),
        ];
    }

    private function emptyPaginated(int $perPage)
    {
        return response()->json([
            'success' => true,
            'data' => [
                'data' => [],
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => $perPage,
                'total' => 0,
                'from' => null,
                'to' => null,
            ],
        ]);
    }
}
