<?php

namespace App\Policies\Website;

use App\Models\User;

trait ManagesWebsiteCms
{
    protected function canManageWebsite(User $user): bool
    {
        return $user->hasAnyRole(['Super Admin', 'Director', 'Admin', 'Secretary']);
    }
}
