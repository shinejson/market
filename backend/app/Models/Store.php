<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    use Auditable, BelongsToTenant;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'status',
        'currency',
        'tax_inclusive',
        'delivery_fee',
        'delivery_days',
        'logo_path',
        'banner_path',
        'description',
        'contact_email',
        'contact_phone',
        'address_line',
        'city',
        'country',
        'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'tax_inclusive' => 'boolean',
            'is_featured' => 'boolean',
            'delivery_fee' => 'decimal:2',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
