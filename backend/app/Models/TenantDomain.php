<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class TenantDomain extends Model
{
    use BelongsToTenant;

    public const STATUS_REQUESTED = 'requested';
    public const STATUS_DNS_PENDING = 'dns_pending';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_TLS_PROVISIONING = 'tls_provisioning';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REMOVED = 'removed';

    protected $fillable = [
        'tenant_id',
        'domain',
        'status',
        'verification_token',
        'dns_verified_at',
        'cert_status',
        'last_check_at',
        'check_attempts',
    ];

    protected function casts(): array
    {
        return [
            'dns_verified_at' => 'datetime',
            'last_check_at' => 'datetime',
        ];
    }
}
