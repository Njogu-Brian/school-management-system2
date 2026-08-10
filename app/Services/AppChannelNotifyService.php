<?php

namespace App\Services;

use App\Models\CommunicationLog;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use App\Notifications\CustomAppMessageNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Fan-out in-app (database) + Expo push for manual communications.
 */
class AppChannelNotifyService
{
    public function __construct(
        protected ExpoPushService $push,
        protected ParentAppNotifyService $parents,
    ) {}

    /**
     * @param  Collection<int, User>|iterable<User>  $users
     * @param  array<string, mixed>  $data
     * @return array{notified: int, pushed: int}
     */
    public function notifyUsers($users, string $title, string $body, array $data = []): array
    {
        $collection = $users instanceof Collection ? $users : collect($users);
        $collection = $collection->filter(fn ($u) => $u instanceof User)->unique('id')->values();
        if ($collection->isEmpty()) {
            return ['notified' => 0, 'pushed' => 0];
        }

        try {
            Notification::send($collection, new CustomAppMessageNotification($title, $body, $data));
        } catch (\Throwable $e) {
            Log::warning('App channel database notify failed: '.$e->getMessage());
        }

        $tokens = DB::table('user_device_tokens')
            ->whereIn('user_id', $collection->pluck('id')->all())
            ->pluck('token')
            ->filter(fn ($t) => is_string($t) && $t !== '')
            ->values()
            ->all();

        $pushed = 0;
        if ($tokens !== []) {
            $this->push->sendToTokens($tokens, $title, $body, array_merge(['type' => 'custom_app_message'], $data));
            $pushed = count($tokens);
        }

        foreach ($collection as $user) {
            try {
                CommunicationLog::create([
                    'recipient_type' => 'user',
                    'recipient_id' => $user->id,
                    'contact' => $user->email ?? $user->phone_number,
                    'channel' => 'app',
                    'title' => $title,
                    'message' => $body,
                    'type' => 'app',
                    'status' => 'sent',
                    'scope' => 'app',
                    'sent_at' => now(),
                ]);
            } catch (\Throwable $e) {
                Log::warning('App channel log failed: '.$e->getMessage());
            }
        }

        return ['notified' => $collection->count(), 'pushed' => $pushed];
    }

    /**
     * Resolve Users from CommunicationController-style targeting.
     *
     * @param  array<string, mixed>  $data  Same shape as collectRecipients input
     * @return Collection<int, User>
     */
    public function usersForCommunicationTarget(array $data): Collection
    {
        $target = $data['target'] ?? '';

        if ($target === 'staff') {
            return $this->staffUsers();
        }

        if (in_array($target, ['parents', 'class', 'student', 'specific_students'], true)) {
            $students = $this->studentsForTarget($data);
            $users = collect();
            foreach ($students as $student) {
                $users = $users->merge($this->parents->parentUsersForStudent($student));
            }

            return $users->unique('id')->values();
        }

        return collect();
    }

    /**
     * @return Collection<int, User>
     */
    public function staffUsers(): Collection
    {
        $userIds = Staff::query()
            ->whereNotNull('user_id')
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'archived');
            })
            ->pluck('user_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($userIds === []) {
            return collect();
        }

        return User::query()->whereIn('id', $userIds)->get();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return Collection<int, Student>
     */
    protected function studentsForTarget(array $data): Collection
    {
        $target = $data['target'] ?? '';
        $query = Student::query()->where('archive', 0)->where('is_alumni', false);

        if ($target === 'student' && ! empty($data['student_id'])) {
            return $query->where('id', (int) $data['student_id'])->get();
        }

        if ($target === 'specific_students') {
            $ids = [];
            if (! empty($data['selected_student_ids'])) {
                $ids = array_filter(array_map('intval', explode(',', (string) $data['selected_student_ids'])));
            }
            if ($ids === []) {
                return collect();
            }

            return $query->whereIn('id', $ids)->get();
        }

        if ($target === 'class') {
            $classroomIds = CommunicationHelperService::normalizeClassroomIds($data);
            if ($classroomIds === []) {
                return collect();
            }

            return $query->whereIn('classroom_id', $classroomIds)->get();
        }

        // parents = all active students
        return $query->get();
    }

    /**
     * Notify parent app users for a single student (document share).
     *
     * @param  array<string, mixed>  $data
     */
    public function notifyParentsForStudent(Student $student, string $title, string $body, array $data = []): array
    {
        return $this->notifyUsers($this->parents->parentUsersForStudent($student), $title, $body, $data);
    }

    public function truncateBody(string $body): string
    {
        return Str::limit(trim(strip_tags($body)), 500);
    }
}
