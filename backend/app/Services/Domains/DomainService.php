<?php

namespace App\Services\Domains;

use App\Models\TenantDomain;
use Illuminate\Support\Str;

class DomainService
{
    public function request(int $tenantId, string $domain): TenantDomain
    {
        $domain = strtolower(trim($domain));

        return TenantDomain::query()->create([
            'tenant_id' => $tenantId,
            'domain' => $domain,
            'status' => TenantDomain::STATUS_DNS_PENDING,
            'verification_token' => Str::lower(Str::random(32)),
            'cert_status' => 'none',
        ]);
    }

    public function verify(TenantDomain $domain, bool $force = false): TenantDomain
    {
        $domain->check_attempts = (int) $domain->check_attempts + 1;
        $domain->last_check_at = now();

        $ok = $force || $this->txtMatches($domain);
        if ($ok) {
            $domain->status = TenantDomain::STATUS_ACTIVE;
            $domain->dns_verified_at = now();
            $domain->cert_status = 'issued';
        } elseif ($domain->check_attempts >= 8 || ($domain->created_at && $domain->created_at->lt(now()->subHours(72)))) {
            $domain->status = TenantDomain::STATUS_FAILED;
        } else {
            $domain->status = TenantDomain::STATUS_DNS_PENDING;
        }
        $domain->save();

        return $domain;
    }

    public function resolveHost(?string $host): ?TenantDomain
    {
        if (! $host) {
            return null;
        }
        $host = strtolower(preg_replace('/:\d+$/', '', $host) ?? $host);

        return TenantDomain::withoutGlobalScopes()
            ->where('domain', $host)
            ->where('status', TenantDomain::STATUS_ACTIVE)
            ->first();
    }

    protected function txtMatches(TenantDomain $domain): bool
    {
        $expected = $domain->verification_token;
        $name = '_markethub-verify.'.$domain->domain;
        if (function_exists('dns_get_record')) {
            $records = @dns_get_record($name, DNS_TXT) ?: [];
            foreach ($records as $record) {
                $txt = $record['txt'] ?? ($record['entries'][0] ?? '');
                if ($txt === $expected) {
                    return true;
                }
            }
        }

        return false;
    }
}
