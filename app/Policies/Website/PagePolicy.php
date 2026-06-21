<?php

namespace App\Policies\Website;

use App\Models\User;
use App\Models\Website\Page;

class PagePolicy
{
    use ManagesWebsiteCms;

    public function viewAny(User $user): bool
    {
        return $this->canManageWebsite($user);
    }

    public function view(User $user, Page $page): bool
    {
        return $this->canManageWebsite($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageWebsite($user);
    }

    public function update(User $user, Page $page): bool
    {
        return $this->canManageWebsite($user);
    }

    public function delete(User $user, Page $page): bool
    {
        return $this->canManageWebsite($user) && ! $page->is_homepage;
    }
}
