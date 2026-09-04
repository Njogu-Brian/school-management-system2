<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\CommunicationTemplate;
use App\Models\ParentForcedAction;
use App\Models\ParentInfo;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Parent app login provisioning, credential sharing (one message per family), and onboarding funnel.
 */
class ParentCredentialsService
{
    public const STAGE_NOT_PROVISIONED = 'not_provisioned';

    public const STAGE_CREDENTIALS_SENT = 'credentials_sent';

    public const STAGE_PASSWORD_PENDING = 'password_pending';

    public const STAGE_PROFILE_PENDING = 'profile_pending';

    public const STAGE_COMPLETE = 'complete';

    public function __construct(private CommunicationService $comm)
    {
    }

    /**
     * @return array{parent_info_id: ?int, accounts: list<array{user_id:int,name:?string,login:?string,phone:?string,must_change_password:bool,stage:?string}>}
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
            ->get()
            ->map(fn (User $u) => [
                'user_id' => $u->id,
                'name' => $u->name,
                'login' => $u->email ?: $u->phone_number,
                'phone' => $u->phone_number,
                'must_change_password' => (bool) $u->must_change_password,
                'stage' => $this->stageForUser($u),
            ])
            ->values()
            ->all();

        return [
            'parent_info_id' => (int) $parent->id,
            'accounts' => $accounts,
        ];
    }

    public function activeAcademicYearValue(): string
    {
        $year = AcademicYear::query()->where('is_active', true)->value('year');
        if (! $year) {
            return (string) now()->year;
        }

        return trim((string) $year);
    }

    /**
     * Four-digit year used in parent passwords, e.g. 2026.
     */
    public function academicYearFull(?string $year = null): string
    {
        $year = $year ?? $this->activeAcademicYearValue();
        if (preg_match('/(\d{4})/', $year, $m)) {
            return $m[1];
        }

        return (string) now()->year;
    }

    /**
     * Two-digit year (legacy), e.g. 2026 → 26. Still accepted at login.
     */
    public function academicYearSuffix(?string $year = null): string
    {
        return substr($this->academicYearFull($year), -2);
    }

    public function formulaPasswordForAdmission(string $admissionNumber): string
    {
        return trim($admissionNumber).'-'.$this->academicYearFull();
    }

    /**
     * Accept both RKS001-2026 (current) and RKS001-26 (legacy).
     *
     * @return list<string>
     */
    public function formulaPasswordCandidatesForAdmission(string $admissionNumber): array
    {
        $adm = trim($admissionNumber);
        if ($adm === '') {
            return [];
        }

        $yyyy = $this->academicYearFull();
        $yy = substr($yyyy, -2);

        return array_values(array_unique([
            $adm.'-'.$yyyy,
            $adm.'-'.$yy,
        ]));
    }

    /**
     * @return list<string>
     */
    public function childrenAdmissionNumbers(ParentInfo $parent): array
    {
        $direct = Student::query()
            ->where('parent_id', $parent->id)
            ->where('archive', 0);
        $directIds = $direct->pluck('id');
        $familyIds = Student::query()
            ->where('parent_id', $parent->id)
            ->whereNotNull('family_id')
            ->pluck('family_id')
            ->unique()
            ->filter();

        $query = Student::query()
            ->where('archive', 0)
            ->whereNotNull('admission_number')
            ->where('admission_number', '!=', '');

        if ($familyIds->isNotEmpty()) {
            $query->where(function ($q) use ($directIds, $familyIds) {
                $q->whereIn('id', $directIds)->orWhereIn('family_id', $familyIds);
            });
        } else {
            $query->where('parent_id', $parent->id);
        }

        return $query
            ->orderBy('id')
            ->pluck('admission_number')
            ->map(fn ($n) => trim((string) $n))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function formulaPassword(ParentInfo $parent, ?Student $preferredChild = null): string
    {
        $child = $preferredChild && (int) $preferredChild->parent_id === (int) $parent->id
            ? $preferredChild
            : $this->pickPasswordChild($parent);

        if (! $child || ! filled($child->admission_number)) {
            throw new \RuntimeException('No child admission number available for the parent password.');
        }

        return $this->formulaPasswordForAdmission((string) $child->admission_number);
    }

    /**
     * Any child's admission-year password is valid for a parent account
     * (siblings share the family login; father and mother keep distinct usernames).
     */
    public function matchesAnyTemporaryPassword(User $user, string $plain): bool
    {
        if (! $user->parent_id || $user->hasElevatedStaffRole()) {
            return false;
        }

        $parent = ParentInfo::query()->find($user->parent_id);
        if (! $parent) {
            return false;
        }

        $given = mb_strtolower(trim($plain));
        if ($given === '') {
            return false;
        }

        foreach ($this->childrenAdmissionNumbers($parent) as $admission) {
            foreach ($this->formulaPasswordCandidatesForAdmission($admission) as $candidate) {
                $needle = mb_strtolower($candidate);
                if (strlen($needle) === strlen($given) && hash_equals($needle, $given)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function passwordIsValid(User $user, string $plain): bool
    {
        if ($user->password && Hash::check($plain, (string) $user->password)) {
            return true;
        }

        return $this->matchesAnyTemporaryPassword($user, $plain);
    }

    /**
     * Reset every non-staff parent login to the admission-year temporary password.
     *
     * @return array{ok:int,fail:int,skipped:int,errors:list<string>}
     */
    public function resetAllParentTempPasswords(bool $dryRun = false): array
    {
        $ok = 0;
        $fail = 0;
        $skipped = 0;
        $errors = [];

        $users = User::query()
            ->whereNotNull('parent_id')
            ->with(['roles', 'staff'])
            ->orderBy('id')
            ->get();

        foreach ($users as $user) {
            // Staff (and staff who are also parents) already have working logins — do not overwrite.
            if ($user->staff || $user->hasElevatedStaffRole()) {
                $skipped++;

                continue;
            }

            $parent = ParentInfo::query()->find($user->parent_id);
            if (! $parent) {
                $fail++;
                $errors[] = "User #{$user->id} has no parent record.";

                continue;
            }

            try {
                $password = $this->formulaPassword($parent);
                if (! $dryRun) {
                    $user->update([
                        'password' => Hash::make($password),
                        'must_change_password' => false,
                    ]);
                    $this->clearPasswordChangeAction($parent, $user);
                }
                $ok++;
            } catch (\Throwable $e) {
                $fail++;
                $errors[] = "User #{$user->id} ({$user->email}): ".$e->getMessage();
            }
        }

        return compact('ok', 'fail', 'skipped', 'errors');
    }

    public function pickPasswordChild(ParentInfo $parent): ?Student
    {
        return Student::query()
            ->where('parent_id', $parent->id)
            ->where('archive', 0)
            ->orderBy('id')
            ->first();
    }

    /**
     * Ensure a Parent user exists for this family (create if missing).
     * When father and mother both have contacts, the first slot (father, then mother) is returned.
     */
    public function ensureParentUser(ParentInfo $parent): User
    {
        $accounts = $this->ensureParentUsers($parent);
        if ($accounts === []) {
            throw new \RuntimeException('Parent has no phone or email to create a login.');
        }

        return $accounts[0]['user'];
    }

    /**
     * Distinct father / mother / guardian logins. Username prefers phone over email.
     *
     * @return list<array{slot:string,user:User}>
     */
    public function ensureParentUsers(ParentInfo $parent): array
    {
        $accounts = [];
        foreach ($this->contactSlots($parent) as $slot) {
            try {
                $accounts[] = [
                    'slot' => $slot,
                    'user' => $this->ensureParentUserForSlot($parent, $slot),
                ];
            } catch (\Throwable $e) {
                Log::warning('Could not provision parent slot login', [
                    'parent_info_id' => $parent->id,
                    'slot' => $slot,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $accounts;
    }

    public function ensureParentUserForSlot(ParentInfo $parent, string $slot, ?Student $preferredChild = null): User
    {
        $contact = $this->slotContact($parent, $slot);
        $phone = $contact['phone'];
        $email = $contact['email'];
        $name = $contact['name'] !== '' ? $contact['name'] : $this->resolveDisplayName($parent);

        if (! $phone && ! $email) {
            throw new \RuntimeException('This parent has no phone or email to create a login.');
        }

        $existing = $this->findUserForSlot($parent, $slot, $phone, $email);
        if ($existing) {
            $dirty = false;
            if (! $existing->parent_id) {
                $existing->parent_id = $parent->id;
                $dirty = true;
            }
            if ($phone && empty($existing->phone_number)) {
                $existing->phone_number = $phone;
                $dirty = true;
            }
            if ($name !== '' && (empty($existing->name) || $existing->name === $existing->email)) {
                $existing->name = $name;
                $dirty = true;
            }
            if ($dirty) {
                $existing->save();
            }
            $this->assignParentRoleIfEligible($existing);

            return $existing;
        }

        $loginEmail = $this->uniqueLoginEmail($parent, $email, $phone);
        $password = $this->formulaPassword($parent, $preferredChild);

        $user = new User();
        $user->name = $name;
        $user->email = $loginEmail;
        $user->phone_number = $phone;
        $user->password = Hash::make($password);
        $user->parent_id = $parent->id;
        $user->must_change_password = false;
        $user->parent_profile_review_required = true;
        $user->save();

        $this->assignParentRoleIfEligible($user);
        $this->seedOnboardingActions($user, $parent);

        return $user;
    }

    /**
     * Username shown to the parent: phone when available, otherwise a real email.
     */
    public function loginUsername(User $user): string
    {
        $ids = app(LoginIdentifierService::class);
        if (filled($user->phone_number)) {
            return $ids->displayPhone((string) $user->phone_number);
        }

        $email = strtolower(trim((string) ($user->email ?? '')));
        if ($email !== '' && ! $this->isPlaceholderEmail($email)) {
            return $email;
        }

        return (string) ($user->phone_number ?: $user->email ?: '');
    }

    public function displayUsername(?string $phone, ?string $email): ?string
    {
        if (filled($phone)) {
            return app(LoginIdentifierService::class)->displayPhone((string) $phone);
        }
        $email = strtolower(trim((string) $email));
        if ($email !== '' && ! $this->isPlaceholderEmail($email)) {
            return $email;
        }

        return null;
    }

    public function appDownloadUrl(): string
    {
        $url = trim((string) config('app.mobile_app_download_url', ''));

        return $url !== '' ? $url : url('/');
    }

    /**
     * Placeholders for Send Communication templates (per father/mother slot).
     *
     * @return array<string, string>
     */
    public function placeholderExtrasForSlot(ParentInfo $parent, string $slot, ?Student $child = null): array
    {
        $user = $this->ensureParentUserForSlot($parent, $slot, $child);
        $password = $this->formulaPassword($parent, $child);
        $username = $this->loginUsername($user);

        return [
            'username' => $username,
            'login' => $username,
            'password' => $password,
            'parent_password' => $password,
        ];
    }

    public function plainMatchesFormula(ParentInfo $parent, string $plain): bool
    {
        $given = mb_strtolower(trim($plain));
        if ($given === '') {
            return false;
        }

        foreach ($this->childrenAdmissionNumbers($parent) as $admission) {
            foreach ($this->formulaPasswordCandidatesForAdmission($admission) as $candidate) {
                $needle = mb_strtolower($candidate);
                if (strlen($needle) === strlen($given) && hash_equals($needle, $given)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Provision father and mother logins, set formula password, share via selected channels.
     *
     * @param  list<string>  $channels  sms|whatsapp|email
     * @return array{user: User, temporary_password: string, password: string, shared_via: list<string>, stage: string}
     */
    public function provisionAndShare(
        ParentInfo $parent,
        array $channels = ['sms'],
        ?Student $preferredChild = null,
        bool $resetPassword = true,
    ): array {
        $accounts = $this->ensureParentUsers($parent);
        if ($accounts === []) {
            throw new \RuntimeException('Parent has no phone or email to create a login.');
        }

        $password = $this->formulaPassword($parent, $preferredChild);
        $sharedVia = [];
        $primary = $accounts[0]['user'];

        foreach ($accounts as $account) {
            $user = $account['user']->loadMissing(['staff', 'roles']);
            if ($user->staff || $user->hasElevatedStaffRole()) {
                continue;
            }
            if ($resetPassword) {
                $user->update([
                    'password' => Hash::make($password),
                    'must_change_password' => false,
                ]);
                $this->clearPasswordChangeAction($parent, $user);
            }

            $via = $this->shareCredentials($user->fresh(), $parent, $password, $channels, $account['slot']);
            $sharedVia = array_values(array_unique(array_merge($sharedVia, $via)));

            if ($via !== [] && Schema::hasColumn('users', 'credentials_sent_at')) {
                $user->forceFill([
                    'credentials_sent_at' => now(),
                    'credentials_sent_via' => implode(',', $via),
                ])->saveQuietly();
            }
        }

        $primary = $primary->fresh();

        return [
            'user' => $primary,
            'temporary_password' => $password,
            'password' => $password,
            'shared_via' => $sharedVia,
            'stage' => $this->stageForUser($primary),
        ];
    }

    /**
     * Send credentials to every family with an active child (staff parent logins skipped).
     *
     * @param  list<string>  $channels
     * @return array{ok:int,fail:int,skipped:int,errors:list<string>}
     */
    public function shareToAllParents(array $channels, bool $dryRun = false): array
    {
        $parentIds = Student::query()
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->whereNotNull('parent_id')
            ->distinct()
            ->orderBy('parent_id')
            ->pluck('parent_id');

        $ok = 0;
        $fail = 0;
        $skipped = 0;
        $errors = [];

        foreach ($parentIds as $pid) {
            $parent = ParentInfo::query()->find($pid);
            if (! $parent) {
                $fail++;
                $errors[] = "Parent #{$pid} not found.";

                continue;
            }

            if ($dryRun) {
                $ok++;

                continue;
            }

            try {
                $result = $this->provisionAndShare($parent, $channels, null, false);
                if ($result['shared_via'] === []) {
                    $skipped++;
                } else {
                    $ok++;
                }
            } catch (\Throwable $e) {
                $fail++;
                $errors[] = "Parent #{$pid}: ".$e->getMessage();
            }
        }

        return compact('ok', 'fail', 'skipped', 'errors');
    }

    /**
     * @return array{user: User, temporary_password: string, password: string, shared_via: list<string>}
     */
    public function resetPassword(
        Student $student,
        ?int $userId,
        string $passwordOption,
        ?string $customPassword,
        bool $share,
        array $channels = ['sms', 'email'],
    ): array {
        $parent = $student->parent;
        if (! $parent) {
            throw new \RuntimeException('Student has no parent record.');
        }

        $user = $this->resolveAccount($parent, $userId) ?? $this->ensureParentUser($parent);

        if ($passwordOption === 'custom' && filled($customPassword)) {
            $newPassword = $customPassword;
        } elseif ($passwordOption === 'random') {
            $newPassword = Str::random(8);
        } elseif ($passwordOption === 'formula' || $passwordOption === 'admission') {
            $newPassword = $this->formulaPassword($parent, $student);
        } else {
            try {
                $newPassword = $this->formulaPassword($parent, $student);
            } catch (\Throwable) {
                $newPassword = Str::random(8);
            }
        }

        $forceChange = $passwordOption === 'custom' || $passwordOption === 'random';
        $user->update([
            'password' => Hash::make($newPassword),
            'must_change_password' => $forceChange,
        ]);
        if (! $forceChange) {
            $this->clearPasswordChangeAction($parent, $user);
        }

        $slot = $this->slotForUser($parent, $user);
        $sharedVia = $share ? $this->shareCredentials($user->fresh(), $parent, $newPassword, $channels, $slot) : [];

        if ($sharedVia !== [] && Schema::hasColumn('users', 'credentials_sent_at')) {
            $user->forceFill([
                'credentials_sent_at' => now(),
                'credentials_sent_via' => implode(',', $sharedVia),
            ])->saveQuietly();
        }

        return [
            'user' => $user->fresh(),
            'temporary_password' => $newPassword,
            'password' => $newPassword,
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
        $this->ensureForcedAction(
            $user,
            $parent,
            ParentForcedAction::TYPE_CHANGE_PASSWORD,
            'Change your password',
            10
        );

        return $user->fresh();
    }

    /**
     * Send device-PIN reset instructions (PIN is local; server cannot clear it).
     *
     * @param  list<string>  $channels
     * @return list<string>
     */
    public function sendPinResetInstructions(ParentInfo $parent, array $channels = ['sms']): array
    {
        $user = User::query()->where('parent_id', $parent->id)->orderBy('id')->first();
        $schoolName = DB::table('settings')->where('key', 'school_name')->value('value')
            ?? config('app.name', 'School');
        $body = "{$schoolName}: To reset your parent app PIN, sign in with your password, then open Settings and set a new PIN. The school cannot reset the PIN remotely.";

        return $this->deliverMessage($parent, $user, $body, 'PIN reset help', $channels);
    }

    /**
     * @param  list<string>  $channels
     * @return list<string>
     */
    public function shareCredentials(
        User $user,
        ParentInfo $parent,
        string $password,
        array $channels = ['sms', 'email', 'whatsapp'],
        ?string $slot = null,
    ): array {
        $slot = $slot ?: $this->slotForUser($parent, $user);
        $child = $this->pickPasswordChild($parent);
        $username = $this->loginUsername($user);
        $extras = [
            'username' => $username,
            'login' => $username,
            'password' => $password,
            'parent_password' => $password,
            'parent_name' => $user->name ?: $this->resolveDisplayName($parent),
        ];

        $shared = [];
        $channels = array_values(array_unique(array_map('strtolower', $channels)));
        foreach ($channels as $channel) {
            $code = match ($channel) {
                'email' => 'parent_app_credentials_email',
                'whatsapp' => 'parent_app_credentials_whatsapp',
                default => 'parent_app_credentials_sms',
            };
            $tpl = CommunicationTemplate::query()->where('code', $code)->first();
            $body = $tpl?->content ?: $this->defaultCredentialsBody($channel);
            if (preg_match('/app_link|https?:\/\//i', (string) $body)) {
                $body = $this->defaultCredentialsBody($channel);
            }
            $title = $tpl?->subject ?: ($tpl?->title ?: 'Parent app login');
            $personalized = replace_placeholders($body, $child, $extras);
            $title = replace_placeholders($title, $child, $extras);

            $via = $this->deliverMessage($parent, $user, $personalized, $title, [$channel], $slot);
            $shared = array_values(array_unique(array_merge($shared, $via)));
        }

        return $shared;
    }

    /**
     * One delivery attempt per channel for this parent slot (father and mother get their own username).
     *
     * @param  list<string>  $channels
     * @return list<string>
     */
    public function deliverMessage(
        ParentInfo $parent,
        ?User $user,
        string $body,
        string $title,
        array $channels,
        ?string $slot = null,
    ): array {
        $shared = [];
        $channels = array_values(array_unique(array_map('strtolower', $channels)));
        $contact = $slot ? $this->slotContact($parent, $slot) : [
            'phone' => $this->resolvePhone($parent),
            'email' => $this->resolveEmail($parent),
            'name' => $this->resolveDisplayName($parent),
        ];

        $email = $user?->email;
        if ($email && $this->isPlaceholderEmail($email)) {
            $email = $contact['email'] ?? $this->resolveEmail($parent);
        } elseif (! $email) {
            $email = $contact['email'] ?? $this->resolveEmail($parent);
        }

        $phone = $user?->phone_number ?: ($contact['phone'] ?? $this->resolvePhone($parent));

        if (in_array('email', $channels, true) && $email && ! $this->isPlaceholderEmail($email)) {
            try {
                $this->comm->sendEmail(
                    'parent',
                    $parent->id,
                    $email,
                    $title,
                    nl2br(e($body)),
                    null
                );
                $shared[] = 'email';
            } catch (\Throwable $e) {
                Log::warning('Parent credentials email failed', [
                    'parent_info_id' => $parent->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (in_array('sms', $channels, true) && $phone) {
            try {
                $phoneService = app(PhoneNumberService::class);
                $smsPhone = $phoneService->formatWithCountryCode($phone, '+254');
                $result = $this->comm->sendSMS('parent', $parent->id, $smsPhone, $body, $title);
                if ($result['success'] ?? false) {
                    $shared[] = 'sms';
                }
            } catch (\Throwable $e) {
                Log::warning('Parent credentials SMS failed', [
                    'parent_info_id' => $parent->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (in_array('whatsapp', $channels, true) && $phone) {
            try {
                WhatsAppBulkRateLimiter::waitBeforeSend('global');
                $phoneService = app(PhoneNumberService::class);
                $waPhone = $phoneService->formatWithCountryCode($phone, '+254');
                $this->comm->sendWhatsApp('parent', $parent->id, $waPhone, $body, $title);
                $shared[] = 'whatsapp';
            } catch (\Throwable $e) {
                Log::warning('Parent credentials WhatsApp failed', [
                    'parent_info_id' => $parent->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $shared;
    }

    public function stageForUser(?User $user): string
    {
        if (! $user || ! $user->parent_id) {
            return self::STAGE_NOT_PROVISIONED;
        }

        if ($user->must_change_password) {
            if ($user->first_app_login_at || $user->last_login_at) {
                return self::STAGE_PASSWORD_PENDING;
            }

            return self::STAGE_CREDENTIALS_SENT;
        }

        $pendingProfile = ParentForcedAction::query()
            ->where('parent_info_id', $user->parent_id)
            ->where('status', ParentForcedAction::STATUS_PENDING)
            ->whereIn('type', [
                ParentForcedAction::TYPE_PROFILE_REVIEW,
                ParentForcedAction::TYPE_UPLOAD_DOCUMENTS,
            ])
            ->exists();

        if ($user->parent_profile_review_required || $pendingProfile) {
            return self::STAGE_PROFILE_PENDING;
        }

        return self::STAGE_COMPLETE;
    }

    public function stageForParentInfo(ParentInfo $parent): string
    {
        $users = User::query()->where('parent_id', $parent->id)->orderBy('id')->get();

        return $this->stageForFamily($parent, $users);
    }

    /**
     * Family funnel stage: least progressed among father/mother/(guardian) slots.
     * Elevated staff linked via parent_id are ignored.
     *
     * @param  Collection<int, User>|iterable<User>  $users
     */
    public function stageForFamily(ParentInfo $parent, iterable $users): string
    {
        $accounts = $this->parentSlotAccounts($parent, collect($users));
        if ($accounts === []) {
            return self::STAGE_NOT_PROVISIONED;
        }

        $rank = [
            self::STAGE_NOT_PROVISIONED => 0,
            self::STAGE_CREDENTIALS_SENT => 1,
            self::STAGE_PASSWORD_PENDING => 2,
            self::STAGE_PROFILE_PENDING => 3,
            self::STAGE_COMPLETE => 4,
        ];

        $worst = self::STAGE_COMPLETE;
        $worstRank = $rank[$worst];
        foreach ($accounts as $account) {
            $s = $account['user']
                ? $this->stageForUser($account['user'])
                : self::STAGE_NOT_PROVISIONED;
            $r = $rank[$s] ?? 0;
            if ($r < $worstRank) {
                $worst = $s;
                $worstRank = $r;
            }
        }

        return $worst;
    }

    /**
     * @return array<string, int>
     */
    public function funnelCounts(?string $search = null): array
    {
        $stages = [
            self::STAGE_NOT_PROVISIONED => 0,
            self::STAGE_CREDENTIALS_SENT => 0,
            self::STAGE_PASSWORD_PENDING => 0,
            self::STAGE_PROFILE_PENDING => 0,
            self::STAGE_COMPLETE => 0,
        ];

        foreach ($this->familyRows($search) as $row) {
            $stages[$row['stage']] = ($stages[$row['stage']] ?? 0) + 1;
        }

        return $stages;
    }

    /**
     * Distinct families with active children.
     * One row per family; father + mother (and guardian when needed) tracked separately.
     *
     * @return list<array<string, mixed>>
     */
    public function familyRows(?string $search = null, ?string $stage = null): array
    {
        $parentIds = Student::query()
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->whereNotNull('parent_id')
            ->when($search, function ($q) use ($search) {
                $term = '%'.trim($search).'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('admission_number', 'like', $term)
                        ->orWhere('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhereHas('parent', function ($p) use ($term) {
                            $p->where('father_name', 'like', $term)
                                ->orWhere('mother_name', 'like', $term)
                                ->orWhere('guardian_name', 'like', $term)
                                ->orWhere('father_phone', 'like', $term)
                                ->orWhere('mother_phone', 'like', $term)
                                ->orWhere('guardian_phone', 'like', $term)
                                ->orWhere('father_email', 'like', $term)
                                ->orWhere('mother_email', 'like', $term);
                        });
                });
            })
            ->distinct()
            ->pluck('parent_id');

        $parents = ParentInfo::query()->whereIn('id', $parentIds)->orderBy('id')->get();
        $usersByParent = User::query()
            ->with('roles')
            ->whereIn('parent_id', $parentIds)
            ->orderBy('id')
            ->get()
            ->groupBy('parent_id');
        $childrenByParent = Student::query()
            ->whereIn('parent_id', $parentIds)
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->with(['classroom:id,name'])
            ->orderBy('admission_number')
            ->orderBy('id')
            ->get()
            ->groupBy('parent_id');

        $rows = [];
        foreach ($parents as $parent) {
            /** @var Collection<int, User> $users */
            $users = $usersByParent->get($parent->id, collect());
            $accounts = $this->parentSlotAccounts($parent, $users);
            $rowStage = $this->stageForFamily($parent, $users);
            if ($stage && $rowStage !== $stage) {
                continue;
            }

            $child = $this->pickPasswordChild($parent);
            $kids = $childrenByParent->get($parent->id, collect());
            $primary = collect($accounts)->first(fn (array $a) => $a['user'] !== null);
            $anyLogin = collect($accounts)
                ->map(fn (array $a) => $this->loginAtForUser($a['user']))
                ->filter()
                ->sort()
                ->first();
            $anySent = collect($accounts)
                ->map(fn (array $a) => $a['user']?->credentials_sent_at)
                ->filter()
                ->sort()
                ->first();

            $primaryUser = is_array($primary) ? ($primary['user'] ?? null) : null;

            $rows[] = [
                'parent_info_id' => $parent->id,
                'family_name' => $this->resolveDisplayName($parent),
                'phone' => $this->resolvePhone($parent),
                'email' => $this->resolveEmail($parent),
                'child_admission' => $child?->admission_number,
                'child_name' => $child ? trim($child->first_name.' '.$child->last_name) : null,
                'children_count' => $kids->count(),
                'children' => $kids->map(function (Student $s) {
                    $admission = trim((string) ($s->admission_number ?? ''));

                    return [
                        'id' => (int) $s->id,
                        'name' => trim($s->first_name.' '.$s->last_name),
                        'admission_number' => $admission !== '' ? $admission : null,
                        'password' => $admission !== '' ? $this->formulaPasswordForAdmission($admission) : null,
                        'class_name' => $s->classroom?->name,
                    ];
                })->values()->all(),
                'user_id' => $primaryUser?->id,
                'login' => $primaryUser ? ($this->loginUsername($primaryUser) ?: ($primaryUser->phone_number ?: $primaryUser->email)) : null,
                'stage' => $rowStage,
                'credentials_sent_at' => $anySent,
                'credentials_sent_via' => $primaryUser?->credentials_sent_via,
                'first_app_login_at' => $anyLogin,
                'must_change_password' => (bool) collect($accounts)->contains(fn (array $a) => $a['user']?->must_change_password),
                'profile_completed_at' => $primaryUser?->profile_completed_at,
                'accounts' => array_map(function (array $a) {
                    $user = $a['user'];
                    $loginAt = $this->loginAtForUser($user);

                    return [
                        'slot' => $a['slot'],
                        'label' => $a['label'],
                        'name' => $a['name'],
                        'contact' => $a['contact'],
                        'username' => $a['username'] ?? ($user ? $this->loginUsername($user) : $a['contact']),
                        'user_id' => $user?->id,
                        'login' => $a['username'] ?? ($user ? ($this->loginUsername($user) ?: ($user->phone_number ?: $user->email)) : $a['contact']),
                        'stage' => $user ? $this->stageForUser($user) : self::STAGE_NOT_PROVISIONED,
                        'credentials_sent_at' => $user?->credentials_sent_at,
                        'first_app_login_at' => $loginAt,
                        'must_change_password' => (bool) ($user?->must_change_password),
                    ];
                }, $accounts),
            ];
        }

        return $rows;
    }

    /**
     * Expected father/mother/(guardian) login slots for a family, matched to users by email/phone.
     * Elevated staff with parent_id are excluded from matching.
     *
     * @param  Collection<int, User>  $users
     * @return list<array{slot:string,label:string,name:?string,contact:?string,user:?User}>
     */
    public function parentSlotAccounts(ParentInfo $parent, Collection $users): array
    {
        $slots = [];
        if ($this->slotHasContact($parent, 'father')) {
            $slots[] = [
                'slot' => 'father',
                'label' => 'Father',
                'name' => filled($parent->father_name) ? trim((string) $parent->father_name) : null,
                'emails' => array_filter([(string) ($parent->father_email ?? '')]),
                'phones' => array_filter([
                    (string) ($parent->father_phone ?? ''),
                    (string) ($parent->father_whatsapp ?? ''),
                ]),
            ];
        }
        if ($this->slotHasContact($parent, 'mother')) {
            $slots[] = [
                'slot' => 'mother',
                'label' => 'Mother',
                'name' => filled($parent->mother_name) ? trim((string) $parent->mother_name) : null,
                'emails' => array_filter([(string) ($parent->mother_email ?? '')]),
                'phones' => array_filter([
                    (string) ($parent->mother_phone ?? ''),
                    (string) ($parent->mother_whatsapp ?? ''),
                ]),
            ];
        }
        if ($slots === [] && $this->slotHasContact($parent, 'guardian')) {
            $slots[] = [
                'slot' => 'guardian',
                'label' => 'Guardian',
                'name' => filled($parent->guardian_name) ? trim((string) $parent->guardian_name) : null,
                'emails' => array_filter([(string) ($parent->guardian_email ?? '')]),
                'phones' => array_filter([
                    (string) ($parent->guardian_phone ?? ''),
                    (string) ($parent->guardian_whatsapp ?? ''),
                ]),
            ];
        }

        $pool = $users->values();

        $usedIds = [];
        $accounts = [];
        foreach ($slots as $slot) {
            $match = $pool->first(function (User $u) use ($slot, $usedIds) {
                if (isset($usedIds[$u->id])) {
                    return false;
                }

                return $this->userMatchesSlot($u, $slot['emails'], $slot['phones']);
            });

            // School staff emails often differ from parent_info emails — match leftover by name.
            if (! $match && filled($slot['name'])) {
                $needle = strtolower(trim((string) $slot['name']));
                $match = $pool->first(function (User $u) use ($needle, $usedIds) {
                    if (isset($usedIds[$u->id])) {
                        return false;
                    }

                    return strtolower(trim((string) $u->name)) === $needle;
                });
            }

            if ($match) {
                $usedIds[$match->id] = true;
            }

            $contact = $slot['phones'][0] ?? ($slot['emails'][0] ?? null);
            $username = $match
                ? ($this->loginUsername($match) ?: $this->displayUsername($slot['phones'][0] ?? null, $slot['emails'][0] ?? null))
                : $this->displayUsername($slot['phones'][0] ?? null, $slot['emails'][0] ?? null);
            $accounts[] = [
                'slot' => $slot['slot'],
                'label' => $slot['label'],
                'name' => $slot['name'] ?: ($match?->name),
                'contact' => $contact,
                'username' => $username,
                'user' => $match,
            ];
        }

        // Claimed parent accounts that did not match father/mother/guardian contacts.
        // Skip elevated staff leftovers (e.g. Super Admin claimed a child) so they do not
        // appear as a third “parent login” on the family row.
        foreach ($pool as $user) {
            if (isset($usedIds[$user->id])) {
                continue;
            }
            if ($user->hasElevatedStaffRole()) {
                continue;
            }
            $accounts[] = [
                'slot' => 'other',
                'label' => 'Parent',
                'name' => $user->name,
                'contact' => $user->phone_number ?: $user->email,
                'username' => $this->loginUsername($user) ?: ($user->phone_number ?: $user->email),
                'user' => $user,
            ];
        }

        return $accounts;
    }

    /**
     * Prefer first_app_login_at; fall back to last_login_at (older logins before first_app was stamped).
     */
    public function loginAtForUser(?User $user): ?\Illuminate\Support\Carbon
    {
        if (! $user) {
            return null;
        }

        return $user->first_app_login_at ?: $user->last_login_at;
    }

    protected function slotHasContact(ParentInfo $parent, string $slot): bool
    {
        return match ($slot) {
            'father' => filled($parent->father_phone)
                || filled($parent->father_email)
                || filled($parent->father_whatsapp)
                || filled($parent->father_name),
            'mother' => filled($parent->mother_phone)
                || filled($parent->mother_email)
                || filled($parent->mother_whatsapp)
                || filled($parent->mother_name),
            'guardian' => filled($parent->guardian_phone)
                || filled($parent->guardian_email)
                || filled($parent->guardian_whatsapp)
                || filled($parent->guardian_name),
            default => false,
        };
    }

    /**
     * @param  list<string>  $emails
     * @param  list<string>  $phones
     */
    protected function userMatchesSlot(User $user, array $emails, array $phones): bool
    {
        $userEmail = strtolower(trim((string) ($user->email ?? '')));
        if ($userEmail !== '' && ! $this->isPlaceholderEmail($userEmail)) {
            foreach ($emails as $email) {
                if ($userEmail === strtolower(trim($email))) {
                    return true;
                }
            }
        }

        $userPhone = normalize_contact_for_parent_match($user->phone_number ?? '');
        if ($userPhone !== '') {
            foreach ($phones as $phone) {
                if ($userPhone === normalize_contact_for_parent_match($phone)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function seedOnboardingActions(User $user, ParentInfo $parent): void
    {
        $this->ensureForcedAction(
            $user,
            $parent,
            ParentForcedAction::TYPE_PROFILE_REVIEW,
            'Update family profile & upload documents',
            20,
            [
                'require_documents' => [
                    'student_profile_photo',
                    'student_birth_certificate',
                    'parent_id_card',
                ],
            ]
        );
    }

    public function clearPasswordChangeAction(ParentInfo $parent, ?User $user = null): void
    {
        ParentForcedAction::query()
            ->where('parent_info_id', $parent->id)
            ->where('type', ParentForcedAction::TYPE_CHANGE_PASSWORD)
            ->where('status', ParentForcedAction::STATUS_PENDING)
            ->when($user, fn ($q) => $q->where(function ($inner) use ($user) {
                $inner->where('user_id', $user->id)->orWhereNull('user_id');
            }))
            ->update(['status' => ParentForcedAction::STATUS_COMPLETED]);

        if ($user && Schema::hasColumn('users', 'must_change_password')) {
            $user->forceFill(['must_change_password' => false])->saveQuietly();
        }
    }

    public function ensureForcedAction(
        User $user,
        ParentInfo $parent,
        string $type,
        string $title,
        int $priority = 100,
        ?array $payload = null,
    ): ParentForcedAction {
        $existing = ParentForcedAction::query()
            ->where('parent_info_id', $parent->id)
            ->where('type', $type)
            ->where('status', ParentForcedAction::STATUS_PENDING)
            ->first();

        if ($existing) {
            if (! $existing->user_id) {
                $existing->update(['user_id' => $user->id]);
            }

            return $existing;
        }

        return ParentForcedAction::create([
            'user_id' => $user->id,
            'parent_info_id' => $parent->id,
            'type' => $type,
            'title' => $title,
            'payload' => $payload,
            'priority' => $priority,
            'blocking' => true,
            'status' => ParentForcedAction::STATUS_PENDING,
        ]);
    }

    protected function resolveAccount(ParentInfo $parent, ?int $userId): ?User
    {
        $query = User::query()->where('parent_id', $parent->id);
        if ($userId) {
            $query->where('id', $userId);
        }

        return $query->first();
    }

    protected function assignParentRoleIfEligible(User $user): void
    {
        if ($user->hasElevatedStaffRole()) {
            return;
        }

        try {
            $guard = config('auth.defaults.guard', 'web');
            Role::firstOrCreate(['name' => 'Parent', 'guard_name' => $guard]);
            if (! $user->hasRole('Parent')) {
                $user->assignRole('Parent');
            }
        } catch (\Throwable $e) {
            Log::warning('Could not assign Parent role', ['error' => $e->getMessage()]);
        }
    }

    protected function resolveDisplayName(ParentInfo $parent): string
    {
        foreach (['father_name', 'mother_name', 'guardian_name'] as $field) {
            if (filled($parent->{$field})) {
                return trim((string) $parent->{$field});
            }
        }

        return 'Parent #'.$parent->id;
    }

    protected function resolvePhone(ParentInfo $parent): ?string
    {
        foreach ([
            'primary_contact_phone',
            'father_phone',
            'mother_phone',
            'guardian_phone',
            'father_whatsapp',
            'mother_whatsapp',
            'guardian_whatsapp',
        ] as $field) {
            if (isset($parent->{$field}) && filled($parent->{$field})) {
                return trim((string) $parent->{$field});
            }
        }

        return null;
    }

    protected function resolveEmail(ParentInfo $parent): ?string
    {
        foreach (['father_email', 'mother_email', 'guardian_email'] as $field) {
            if (filled($parent->{$field})) {
                return strtolower(trim((string) $parent->{$field}));
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function contactSlots(ParentInfo $parent): array
    {
        $slots = [];
        if ($this->slotHasUsableLogin($parent, 'father')) {
            $slots[] = 'father';
        }
        if ($this->slotHasUsableLogin($parent, 'mother')) {
            $slots[] = 'mother';
        }
        if ($slots === [] && $this->slotHasUsableLogin($parent, 'guardian')) {
            $slots[] = 'guardian';
        }

        return $slots;
    }

    /**
     * @return array{name:string,phone:?string,email:?string}
     */
    public function slotContact(ParentInfo $parent, string $slot): array
    {
        $ids = app(LoginIdentifierService::class);

        $rawPhone = match ($slot) {
            'father' => $parent->father_phone ?: $parent->father_whatsapp,
            'mother' => $parent->mother_phone ?: $parent->mother_whatsapp,
            default => $parent->guardian_phone ?: $parent->guardian_whatsapp,
        };
        $rawEmail = match ($slot) {
            'father' => $parent->father_email,
            'mother' => $parent->mother_email,
            default => $parent->guardian_email,
        };
        $name = match ($slot) {
            'father' => trim((string) ($parent->father_name ?? '')),
            'mother' => trim((string) ($parent->mother_name ?? '')),
            default => trim((string) ($parent->guardian_name ?? '')),
        };

        $phone = filled($rawPhone) ? $ids->normalizePhone((string) $rawPhone) : null;
        $email = filled($rawEmail) && filter_var(trim((string) $rawEmail), FILTER_VALIDATE_EMAIL)
            ? strtolower(trim((string) $rawEmail))
            : null;

        return ['name' => $name, 'phone' => $phone, 'email' => $email];
    }

    protected function slotHasUsableLogin(ParentInfo $parent, string $slot): bool
    {
        $contact = $this->slotContact($parent, $slot);

        return filled($contact['phone']) || filled($contact['email']);
    }

    protected function findUserForSlot(ParentInfo $parent, string $slot, ?string $phone, ?string $email): ?User
    {
        $users = User::query()
            ->with('roles')
            ->where('parent_id', $parent->id)
            ->orderBy('id')
            ->get();

        $emails = array_filter([(string) $email]);
        $phones = array_filter([(string) $phone]);
        $match = $users->first(fn (User $u) => $this->userMatchesSlot($u, $emails, $phones));
        if ($match) {
            return $match;
        }

        $ids = app(LoginIdentifierService::class);
        if ($phone) {
            $byPhone = User::query()->whereIn('phone_number', $ids->phoneVariants($phone))->first();
            if ($byPhone && (! $byPhone->parent_id || (int) $byPhone->parent_id === (int) $parent->id)) {
                return $byPhone;
            }
        }
        if ($email) {
            $byEmail = User::whereRaw('LOWER(TRIM(email)) = ?', [strtolower($email)])->first();
            if ($byEmail && (! $byEmail->parent_id || (int) $byEmail->parent_id === (int) $parent->id)) {
                return $byEmail;
            }
        }

        return null;
    }

    public function slotForUser(ParentInfo $parent, User $user): ?string
    {
        foreach ($this->contactSlots($parent) as $slot) {
            $contact = $this->slotContact($parent, $slot);
            if ($this->userMatchesSlot($user, array_filter([(string) $contact['email']]), array_filter([(string) $contact['phone']]))) {
                return $slot;
            }
        }

        return $this->contactSlots($parent)[0] ?? null;
    }

    protected function uniqueLoginEmail(ParentInfo $parent, ?string $email, ?string $phone): string
    {
        $loginEmail = $email;
        if (! $loginEmail && $phone) {
            $digits = preg_replace('/\D+/', '', $phone) ?: 'unknown';
            $loginEmail = 'parent'.$digits.'@parents.local';
        }
        if (! $loginEmail) {
            $loginEmail = 'parent'.$parent->id.'.'.Str::lower(Str::random(4)).'@parents.local';
        }

        $collision = User::where('email', $loginEmail)->first();
        if (! $collision) {
            return $loginEmail;
        }

        $digits = preg_replace('/\D+/', '', (string) $phone) ?: (string) $parent->id;
        do {
            $loginEmail = 'parent'.$digits.'.'.Str::lower(Str::random(4)).'@parents.local';
        } while (User::where('email', $loginEmail)->exists());

        return $loginEmail;
    }

    protected function isPlaceholderEmail(?string $email): bool
    {
        $email = strtolower(trim((string) $email));
        if ($email === '') {
            return true;
        }

        return str_ends_with($email, '@parents.local') || str_ends_with($email, '@noemail.local');
    }

    protected function defaultCredentialsBody(string $channel): string
    {
        if ($channel === 'email') {
            return "Dear {{parent_name}},\n\nYour {{school_name}} parent app login:\n\nUsername: {{username}}\nPassword: {{password}}\n\nYou can also sign in with any child's admission number and the year, for example RKS001-2026.\n\n{{school_name}}";
        }

        return "{{school_name}} parent app\nUsername: {{username}}\nPassword: {{password}}";
    }
}
