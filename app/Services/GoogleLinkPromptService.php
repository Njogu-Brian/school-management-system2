<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Controls when mobile apps prompt users to link Google after password/OTP login.
 *
 * Setting `google_link_prompt_mode`:
 * - off: never prompt (unless a user has google_link_required)
 * - all: prompt every user who has not linked Google (default)
 * - selected: only users with google_link_required = true
 */
class GoogleLinkPromptService
{
    public const MODE_OFF = 'off';

    public const MODE_ALL = 'all';

    public const MODE_SELECTED = 'selected';

    public function mode(): string
    {
        $mode = strtolower(trim((string) Setting::get('google_link_prompt_mode', self::MODE_ALL)));

        return in_array($mode, [self::MODE_OFF, self::MODE_ALL, self::MODE_SELECTED], true)
            ? $mode
            : self::MODE_ALL;
    }

    public function setMode(string $mode): void
    {
        $mode = strtolower(trim($mode));
        if (! in_array($mode, [self::MODE_OFF, self::MODE_ALL, self::MODE_SELECTED], true)) {
            $mode = self::MODE_ALL;
        }
        Setting::set('google_link_prompt_mode', $mode);
    }

    /**
     * Whether the mobile client should show the post-login Link Google screen.
     */
    public function shouldPrompt(User $user): bool
    {
        if (filled($user->google_id)) {
            return false;
        }

        $mode = $this->mode();
        $forced = (bool) ($user->google_link_required ?? false);

        if ($forced) {
            return true;
        }

        if ($mode === self::MODE_ALL) {
            return true;
        }

        if ($mode === self::MODE_SELECTED) {
            return $forced;
        }

        return false;
    }

    /**
     * @param  list<int>  $userIds
     */
    public function requireLink(array $userIds): int
    {
        return User::query()
            ->whereIn('id', array_values(array_unique($userIds)))
            ->whereNull('google_id')
            ->update(['google_link_required' => true]);
    }

    /**
     * @param  list<int>  $userIds
     */
    public function clearRequireLink(array $userIds): int
    {
        return User::query()
            ->whereIn('id', array_values(array_unique($userIds)))
            ->update(['google_link_required' => false]);
    }

    public function clearRequireForUser(User $user): void
    {
        if ($user->google_link_required) {
            $user->forceFill(['google_link_required' => false])->saveQuietly();
        }
    }

    public function queryUsers(string $group, ?string $search = null): Builder
    {
        $query = User::query()->orderBy('name');

        if ($group === 'staff') {
            $query->whereHas('staff');
        } elseif ($group === 'parents') {
            $query->whereNotNull('parent_id');
        } else {
            $query->where(function (Builder $q) {
                $q->whereHas('staff')->orWhereNotNull('parent_id');
            });
        }

        if ($search) {
            $term = '%'.addcslashes($search, '%_\\').'%';
            $query->where(function (Builder $q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone_number', 'like', $term);
            });
        }

        return $query;
    }

    public function serializePage(Builder $query, int $perPage = 40)
    {
        return $query->with(['staff', 'roles'])->paginate($perPage);
    }
}
