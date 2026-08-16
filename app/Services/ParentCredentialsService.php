<?php

namespace App\Services;

use App\Models\AcademicYear;
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

    public function formulaPassword(ParentInfo $parent, ?Student $preferredChild = null): string
    {
        $child = $preferredChild && (int) $preferredChild->parent_id === (int) $parent->id
            ? $preferredChild
            : $this->pickPasswordChild($parent);

        if (! $child || ! filled($child->admission_number)) {
            throw new \RuntimeException('No child admission number available for temporary password.');
        }

        $year = AcademicYear::query()->where('is_active', true)->value('year');
        if (! $year) {
            $year = (string) now()->year;
        }

        return trim((string) $child->admission_number).'-'.trim((string) $year);
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
     */
    public function ensureParentUser(ParentInfo $parent): User
    {
        $existing = User::query()
            ->with('roles')
            ->where('parent_id', $parent->id)
            ->orderBy('id')
            ->get()
            ->first(fn (User $u) => ! $u->hasElevatedStaffRole());
        if ($existing) {
            return $existing;
        }

        $name = $this->resolveDisplayName($parent);
        $phone = $this->resolvePhone($parent);
        $email = $this->resolveEmail($parent);

        if (! $phone && ! $email) {
            throw new \RuntimeException('Parent has no phone or email to create a login.');
        }

        // Prefer unique email login; synthesize from phone if needed.
        $loginEmail = $email;
        if (! $loginEmail && $phone) {
            $digits = preg_replace('/\D+/', '', $phone);
            $loginEmail = 'parent'.$digits.'@parents.local';
        }

        if ($loginEmail && User::where('email', $loginEmail)->exists()) {
            $collision = User::where('email', $loginEmail)->first();
            if ($collision && ! $collision->parent_id) {
                $collision->parent_id = $parent->id;
                $collision->phone_number = $collision->phone_number ?: $phone;
                $collision->parent_profile_review_required = true;
                $collision->save();
                $this->assignParentRoleIfEligible($collision);
                $this->seedOnboardingActions($collision, $parent);

                return $collision;
            }
            $loginEmail = 'parent'.$parent->id.'.'.Str::lower(Str::random(4)).'@parents.local';
        }

        $password = $this->formulaPassword($parent);

        $user = new User();
        $user->name = $name;
        $user->email = $loginEmail;
        $user->phone_number = $phone;
        $user->password = Hash::make($password);
        $user->parent_id = $parent->id;
        $user->must_change_password = true;
        $user->parent_profile_review_required = true;
        $user->save();

        $this->assignParentRoleIfEligible($user);
        $this->seedOnboardingActions($user, $parent);

        return $user;
    }

    /**
     * Provision (if needed), set formula password, share via selected channels — one message per family.
     *
     * @param  list<string>  $channels  sms|whatsapp|email
     * @return array{user: User, temporary_password: string, shared_via: list<string>, stage: string}
     */
    public function provisionAndShare(
        ParentInfo $parent,
        array $channels = ['sms'],
        ?Student $preferredChild = null,
        bool $resetPassword = true,
    ): array {
        $user = $this->ensureParentUser($parent);
        $password = $this->formulaPassword($parent, $preferredChild);

        if ($resetPassword) {
            $user->update([
                'password' => Hash::make($password),
                'must_change_password' => true,
            ]);
            $this->ensureForcedAction(
                $user,
                $parent,
                ParentForcedAction::TYPE_CHANGE_PASSWORD,
                'Change your password',
                10
            );
        }

        $sharedVia = $this->shareCredentials($user->fresh(), $parent, $password, $channels);

        if ($sharedVia !== [] && Schema::hasColumn('users', 'credentials_sent_at')) {
            $user->forceFill([
                'credentials_sent_at' => now(),
                'credentials_sent_via' => implode(',', $sharedVia),
            ])->saveQuietly();
        }

        $user = $user->fresh();

        return [
            'user' => $user,
            'temporary_password' => $password,
            'shared_via' => $sharedVia,
            'stage' => $this->stageForUser($user),
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

        $user->update([
            'password' => Hash::make($newPassword),
            'must_change_password' => true,
        ]);

        $this->ensureForcedAction(
            $user,
            $parent,
            ParentForcedAction::TYPE_CHANGE_PASSWORD,
            'Change your password',
            10
        );

        $sharedVia = $share ? $this->shareCredentials($user->fresh(), $parent, $newPassword, $channels) : [];

        if ($sharedVia !== [] && Schema::hasColumn('users', 'credentials_sent_at')) {
            $user->forceFill([
                'credentials_sent_at' => now(),
                'credentials_sent_via' => implode(',', $sharedVia),
            ])->saveQuietly();
        }

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
    ): array {
        $schoolName = DB::table('settings')->where('key', 'school_name')->value('value')
            ?? config('app.name', 'School');
        $login = $user->email && ! str_ends_with((string) $user->email, '@parents.local')
            ? $user->email
            : ($user->phone_number ?: 'your registered phone');
        $body = "{$schoolName}: Parent app login: {$login}. Temporary password: {$password}. Change it after signing in.";

        return $this->deliverMessage($parent, $user, $body, 'Parent login credentials', $channels);
    }

    /**
     * One delivery attempt per channel for the family (no per-sibling fan-out).
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
    ): array {
        $shared = [];
        $channels = array_values(array_unique(array_map('strtolower', $channels)));

        $email = $user?->email;
        if ($email && str_ends_with($email, '@parents.local')) {
            $email = $this->resolveEmail($parent);
        } elseif (! $email) {
            $email = $this->resolveEmail($parent);
        }

        $phone = $user?->phone_number ?: $this->resolvePhone($parent);

        if (in_array('email', $channels, true) && $email && ! str_ends_with($email, '@parents.local')) {
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
                'children_count' => Student::where('parent_id', $parent->id)->where('archive', 0)->count(),
                'user_id' => $primaryUser?->id,
                'login' => $primaryUser?->email ?: ($primaryUser?->phone_number ?? null),
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
                        'user_id' => $user?->id,
                        'login' => $user?->email ?: $user?->phone_number,
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

            $contact = $slot['emails'][0] ?? ($slot['phones'][0] ?? null);
            $accounts[] = [
                'slot' => $slot['slot'],
                'label' => $slot['label'],
                'name' => $slot['name'] ?: ($match?->name),
                'contact' => $contact,
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
                'contact' => $user->email ?: $user->phone_number,
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
        if ($userEmail !== '' && ! str_ends_with($userEmail, '@parents.local')) {
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
            ParentForcedAction::TYPE_CHANGE_PASSWORD,
            'Change your password',
            10
        );
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
}
