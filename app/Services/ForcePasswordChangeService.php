<?php

namespace App\Services;

use App\Models\ParentForcedAction;
use App\Models\ParentInfo;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ForcePasswordChangeService
{
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

    /**
     * @param  list<int>  $userIds
     */
    public function requireChange(array $userIds): int
    {
        $users = User::query()->whereIn('id', array_values(array_unique($userIds)))->get();
        $count = 0;

        foreach ($users as $user) {
            $user->update(['must_change_password' => true]);
            if ($user->parent_id) {
                $parent = ParentInfo::find($user->parent_id);
                if ($parent) {
                    app(ParentCredentialsService::class)->ensureForcedAction(
                        $user,
                        $parent,
                        ParentForcedAction::TYPE_CHANGE_PASSWORD,
                        'Change your password',
                        10
                    );
                }
            }
            $count++;
        }

        return $count;
    }

    /**
     * @return Collection<int, User>
     */
    public function serializePage(Builder $query, int $perPage = 40)
    {
        return $query->with(['staff', 'roles'])->paginate($perPage);
    }

    public function describeUser(User $user): array
    {
        $groups = [];
        if ($user->staff) {
            $groups[] = 'Staff';
        }
        if ($user->parent_id) {
            $groups[] = 'Parent';
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'login' => $user->email ?: $user->phone_number,
            'email' => $user->email,
            'phone' => $user->phone_number,
            'groups' => $groups,
            'must_change_password' => (bool) $user->must_change_password,
        ];
    }
}
