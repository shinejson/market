<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isTenantOwner() || $user->isStoreStaff() || $user->isSuperAdmin();
    }

    public function view(User $user, Category $category): bool
    {
        return $this->owns($user, $category);
    }

    public function create(User $user): bool
    {
        return $user->isTenantOwner() || $user->isStoreStaff();
    }

    public function update(User $user, Category $category): bool
    {
        return $this->owns($user, $category);
    }

    public function delete(User $user, Category $category): bool
    {
        return $this->owns($user, $category) && $user->isTenantOwner();
    }

    protected function owns(User $user, Category $category): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return (int) $user->tenantId() === (int) $category->tenant_id;
    }
}
