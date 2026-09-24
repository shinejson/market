<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductVariant extends Model
{
    use Auditable, BelongsToTenant;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'sku',
        'options',
        'price_override',
        'weight',
        'barcode',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'price_override' => 'decimal:2',
            'weight' => 'decimal:3',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class, 'variant_id');
    }

    public function effectivePrice(): string
    {
        return $this->price_override ?? $this->product->price;
    }

    public function availableQty(): int
    {
        $inv = $this->inventory;
        if (! $inv) {
            return 0;
        }

        return max(0, (int) $inv->quantity - (int) $inv->reserved);
    }
}
