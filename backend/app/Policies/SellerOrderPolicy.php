<?php

namespace App\Policies;

use App\Models\SellerOrder;
use App\Models\User;

class SellerOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isTenantOwner() || $user->isStoreStaff() || $user->isSuperAdmin();
    }

    public function view(User $user, SellerOrder $order): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return (int) $user->tenantId() === (int) $order->tenant_id;
    }

    public function update(User $user, SellerOrder $order): bool
    {
        return $this->view($user, $order) && ($user->isTenantOwner() || $user->isStoreStaff());
    }
}
