<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function roles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function ownedTenants(): HasMany
    {
        return $this->hasMany(Tenant::class, 'owner_user_id');
    }

    public function hasRole(string $role, ?int $tenantId = null): bool
    {
        return $this->roles->contains(function (UserRole $userRole) use ($role, $tenantId) {
            if ($userRole->role !== $role) {
                return false;
            }
            if ($tenantId === null) {
                return true;
            }

            return (int) $userRole->tenant_id === $tenantId;
        });
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function isTenantOwner(?int $tenantId = null): bool
    {
        return $this->hasRole('tenant_owner', $tenantId);
    }

    public function isStoreStaff(?int $tenantId = null): bool
    {
        return $this->hasRole('store_staff', $tenantId);
    }

    public function isCustomer(): bool
    {
        return $this->hasRole('customer') || $this->roles->isEmpty();
    }

    public function tenantId(): ?int
    {
        $role = $this->roles->first(fn (UserRole $r) => in_array($r->role, ['tenant_owner', 'store_staff'], true));

        return $role?->tenant_id;
    }

    public function primaryRole(): string
    {
        if ($this->isSuperAdmin()) {
            return 'super_admin';
        }
        if ($this->isTenantOwner()) {
            return 'tenant_owner';
        }
        if ($this->isStoreStaff()) {
            return 'store_staff';
        }

        return 'customer';
    }
}
