<?php

namespace App\Services;

use App\Models\ParentInfo;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Shared parent app login credential management (web ERP + Admin API).
 * Device PIN stays on the phone and cannot be reset from the server.
 */
class ParentCredentialsService
{
    public function __construct(private CommunicationService $comm)
    {
    }

    /**
     * @return array{parent_info_id: ?int, accounts: list<array{user_id:int,name:?string,login:?string,phone:?string,must_change_password:bool}>}
     */
    public function listForStudent(Student $student): array
    {
        $parent = $student->parent;
        if (! $parent) {
            return ['parent_info_id' => null, 'accounts' => []];
        }

        $accounts = User::query()
            ->where('parent_id', $parent->id)
            ->orderBy('name')
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

        return [
            'parent_info_id' => (int) $parent->id,
            'accounts' => $accounts,
        ];
    }

    /**
     * @return array{user: User, temporary_password: string, shared_via: list<string>}
     */
    public function resetPassword(
        Student $student,
        ?int $userId,
        string $passwordOption,
        ?string $customPassword,
        bool $share,
    ): array {
        $parent = $student->parent;
        if (! $parent) {
            throw new \RuntimeException('Student has no parent record.');
        }

        $user = $this->resolveAccount($parent, $userId);
        if (! $user) {
            throw new \RuntimeException('No parent app account linked. Ask the parent to claim access first.');
        }

        if ($passwordOption === 'custom' && filled($customPassword)) {
            $newPassword = $customPassword;
        } else {
            $newPassword = Str::random(8);
        }

        $user->update([
            'password' => Hash::make($newPassword),
            'must_change_password' => true,
        ]);

        $sharedVia = $share ? $this->shareCredentials($user, $parent, $newPassword) : [];

        return [
            'user' => $user->fresh(),
            'temporary_password' => $newPassword,
            'shared_via' => $sharedVia,
        ];
    }

    public function requirePasswordChange(Student $student, ?int $userId): User
    {
        $parent = $student->parent;
        if (! $parent) {
            throw new \RuntimeException('Student has no parent record.');
        }

        $user = $this->resolveAccount($parent, $userId);
        if (! $user) {
            throw new \RuntimeException('No parent app account linked.');
        }

        $user->update(['must_change_password' => true]);

        return $user->fresh();
    }

    protected function resolveAccount(ParentInfo $parent, ?int $userId): ?User
    {
        $query = User::query()->where('parent_id', $parent->id);
        if ($userId) {
            $query->where('id', $userId);
        }

        return $query->first();
    }

    /**
     * @return list<string>
     */
    public function shareCredentials(User $user, ParentInfo $parent, string $password): array
    {
        $shared = [];
        $schoolName = DB::table('settings')->where('key', 'school_name')->value('value')
            ?? config('app.name', 'School');
        $login = $user->email ?: ($user->phone_number ?: 'your registered phone/email');
        $body = "{$schoolName}: Your parent app login is {$login}. Temporary password: {$password}. Please change it after signing in.";

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
