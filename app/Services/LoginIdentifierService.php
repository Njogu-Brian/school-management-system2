<?php

namespace App\Services;

use App\Models\ParentInfo;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

/**
 * Resolve a parent/staff login from phone or email.
 *
 * Kenya numbers can be entered as 07XXXXXXXX, 7XXXXXXXX, or +2547XXXXXXXX.
 * Other countries should include the country code.
 */
class LoginIdentifierService
{
    /**
     * @return array{0: ?User, 1: ?Staff}
     */
    public function findUserAndStaff(string $identifier): array
    {
        $raw = trim($identifier);
        if ($raw === '') {
            return [null, null];
        }

        $user = null;
        $staff = null;

        if ($this->isEmail($raw)) {
            $email = strtolower($raw);
            $user = User::whereRaw('LOWER(TRIM(email)) = ?', [$email])->first();
            if (! $user) {
                $staff = Staff::whereNotNull('work_email')
                    ->whereRaw('LOWER(TRIM(work_email)) = ?', [$email])
                    ->first();
                if ($staff && $staff->user_id) {
                    $user = User::find($staff->user_id);
                }
            }
            if (! $user) {
                $user = $this->findParentUserByContact($raw, 'email');
            }
        } else {
            $variants = $this->phoneVariants($raw);
            if (Schema::hasColumn('users', 'phone_number') && $variants !== []) {
                $user = User::query()->whereIn('phone_number', $variants)->first();
                if (! $user) {
                    $needle = normalize_contact_for_parent_match($raw);
                    if (strlen($needle) >= 9) {
                        $user = User::query()
                            ->whereNotNull('phone_number')
                            ->whereRaw(
                                "RIGHT(REPLACE(REPLACE(REPLACE(phone_number, '+', ''), ' ', ''), '-', ''), 9) = ?",
                                [$needle]
                            )
                            ->first();
                    }
                }
            }

            $staff = Staff::whereNotNull('phone_number')
                ->whereIn('phone_number', $variants)
                ->first();
            if (! $staff) {
                $digits = ltrim($this->normalizePhone($raw), '+');
                $normalized = $this->normalizePhone($raw);
                $staff = Staff::whereNotNull('phone_number')
                    ->where(function ($q) use ($digits, $normalized) {
                        $q->where('phone_number', 'like', '%'.$digits.'%')
                            ->orWhere('phone_number', 'like', '%'.$normalized.'%');
                    })
                    ->first();
            }

            if (! $user && $staff && $staff->user_id) {
                $user = User::find($staff->user_id);
            }
            if (! $user) {
                $user = $this->findParentUserByContact($raw, 'phone');
            }
        }

        if ($user && ! $staff) {
            $staff = Staff::where('user_id', $user->id)->first();
        }

        return [$user, $staff];
    }

    /**
     * @return array{parent: ParentInfo, slot: string}|null
     */
    public function findParentSlotByContact(string $identifier): ?array
    {
        $raw = trim($identifier);
        if ($raw === '') {
            return null;
        }

        if ($this->isEmail($raw)) {
            $email = strtolower($raw);
            $parent = ParentInfo::query()
                ->where(function ($q) use ($email) {
                    $q->whereRaw('LOWER(TRIM(father_email)) = ?', [$email])
                        ->orWhereRaw('LOWER(TRIM(mother_email)) = ?', [$email])
                        ->orWhereRaw('LOWER(TRIM(guardian_email)) = ?', [$email]);
                })
                ->first();
            if (! $parent) {
                return null;
            }
            $slot = match (true) {
                strtolower(trim((string) $parent->father_email)) === $email => 'father',
                strtolower(trim((string) $parent->mother_email)) === $email => 'mother',
                default => 'guardian',
            };

            return ['parent' => $parent, 'slot' => $slot];
        }

        $needle = normalize_contact_for_parent_match($raw);
        if ($needle === '') {
            return null;
        }

        $columns = [
            'father' => ['father_phone', 'father_whatsapp'],
            'mother' => ['mother_phone', 'mother_whatsapp'],
            'guardian' => ['guardian_phone', 'guardian_whatsapp'],
        ];

        $query = ParentInfo::query()->where(function ($q) use ($needle, $columns) {
            foreach ($columns as $fields) {
                foreach ($fields as $col) {
                    $q->orWhere($col, 'like', '%'.$needle.'%');
                }
            }
        });

        foreach ($query->cursor() as $parent) {
            foreach ($columns as $slot => $fields) {
                foreach ($fields as $col) {
                    $candidate = normalize_contact_for_parent_match((string) ($parent->{$col} ?? ''));
                    if ($candidate !== '' && $candidate === $needle) {
                        return ['parent' => $parent, 'slot' => $slot];
                    }
                }
            }
        }

        return null;
    }

    public function isEmail(string $raw): bool
    {
        return (bool) filter_var(trim($raw), FILTER_VALIDATE_EMAIL);
    }

    public function isLikelyPhone(string $raw, ?string $channel = null): bool
    {
        if ($channel === 'phone') {
            return true;
        }
        if ($channel === 'email') {
            return false;
        }
        if ($this->isEmail($raw) || str_contains($raw, '@')) {
            return false;
        }
        $compact = preg_replace('/[\s\-()]/', '', $raw) ?? '';

        return (bool) preg_match('/^\+?[0-9]{7,15}$/', $compact);
    }

    public function normalizePhone(string $raw): string
    {
        $raw = trim($raw);
        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        if ($digits === '') {
            return $raw;
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '254') && strlen($digits) >= 12) {
            return '+'.$digits;
        }

        // Kenya local with leading 0: 07XXXXXXXX
        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            return '+254'.substr($digits, 1);
        }

        // Kenya mobile without leading 0 or country code: 7XXXXXXXX
        if (strlen($digits) === 9 && str_starts_with($digits, '7')) {
            return '+254'.$digits;
        }

        $plusForm = preg_replace('/[^0-9+]/', '', $raw) ?? '';
        if (str_starts_with($plusForm, '+')) {
            return '+'.$digits;
        }

        return '+'.$digits;
    }

    /**
     * Phone as parents type it in Kenya (07XXXXXXXX), otherwise E.164.
     */
    public function displayPhone(string $raw): string
    {
        $normalized = $this->normalizePhone($raw);
        $digits = ltrim($normalized, '+');
        if (str_starts_with($digits, '254') && strlen($digits) >= 12) {
            return '0'.substr($digits, 3);
        }

        return $normalized;
    }

    /**
     * @return list<string>
     */
    public function phoneVariants(string $raw): array
    {
        $normalized = $this->normalizePhone($raw);
        $digits = ltrim($normalized, '+');
        $local9 = normalize_contact_for_parent_match($raw);
        $local0 = (str_starts_with($digits, '254') && strlen($digits) >= 12)
            ? '0'.substr($digits, 3)
            : null;
        $national = (str_starts_with($digits, '254') && strlen($digits) >= 12)
            ? substr($digits, 3)
            : null;

        return array_values(array_unique(array_filter([
            trim($raw),
            $normalized,
            $digits,
            $local0,
            $national,
            $local9,
        ])));
    }

    protected function findParentUserByContact(string $identifier, string $channel): ?User
    {
        $match = $this->findParentSlotByContact($identifier);
        if (! $match) {
            return null;
        }

        $parent = $match['parent'];
        $slot = $match['slot'];
        $users = User::query()
            ->with('roles')
            ->where('parent_id', $parent->id)
            ->orderBy('id')
            ->get();
        if ($users->isEmpty()) {
            return null;
        }

        $emails = [];
        $phones = [];
        if ($slot === 'father') {
            $emails[] = (string) ($parent->father_email ?? '');
            $phones[] = (string) ($parent->father_phone ?? '');
            $phones[] = (string) ($parent->father_whatsapp ?? '');
        } elseif ($slot === 'mother') {
            $emails[] = (string) ($parent->mother_email ?? '');
            $phones[] = (string) ($parent->mother_phone ?? '');
            $phones[] = (string) ($parent->mother_whatsapp ?? '');
        } else {
            $emails[] = (string) ($parent->guardian_email ?? '');
            $phones[] = (string) ($parent->guardian_phone ?? '');
            $phones[] = (string) ($parent->guardian_whatsapp ?? '');
        }

        $emails = array_values(array_filter($emails));
        $phones = array_values(array_filter($phones));

        $matchUser = $users->first(function (User $u) use ($emails, $phones) {
            $userEmail = strtolower(trim((string) ($u->email ?? '')));
            if ($userEmail !== '' && ! str_ends_with($userEmail, '@parents.local') && ! str_ends_with($userEmail, '@noemail.local')) {
                foreach ($emails as $email) {
                    if ($userEmail === strtolower(trim($email))) {
                        return true;
                    }
                }
            }
            $userPhone = normalize_contact_for_parent_match($u->phone_number ?? '');
            if ($userPhone !== '') {
                foreach ($phones as $phone) {
                    if ($userPhone === normalize_contact_for_parent_match($phone)) {
                        return true;
                    }
                }
            }

            return false;
        });

        if ($matchUser) {
            return $matchUser;
        }

        return $users->first(fn (User $u) => ! $u->hasElevatedStaffRole()) ?? $users->first();
    }
}
