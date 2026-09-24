<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isTenantOwner() || $user->isStoreStaff() || $user->isSuperAdmin();
    }

    public function view(User $user, Product $product): bool
    {
        return $this->owns($user, $product);
    }

    public function create(User $user): bool
    {
        return $user->isTenantOwner() || $user->isStoreStaff() || $user->isSuperAdmin();
    }

    public function update(User $user, Product $product): bool
    {
        return $this->owns($user, $product);
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->owns($user, $product) && ($user->isTenantOwner() || $user->isSuperAdmin());
    }

    protected function owns(User $user, Product $product): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return (int) $user->tenantId() === (int) $product->tenant_id;
    }
}
