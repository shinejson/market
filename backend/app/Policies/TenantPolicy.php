<?php

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;

class TenantPolicy
{
    public function view(User $user, Tenant $tenant): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return (int) $user->tenantId() === (int) $tenant->id;
    }

    public function update(User $user, Tenant $tenant): bool
    {
        return $this->view($user, $tenant) && ($user->isTenantOwner() || $user->isSuperAdmin());
    }

    public function approve(User $user, Tenant $tenant): bool
    {
        return $user->isSuperAdmin();
    }
}
