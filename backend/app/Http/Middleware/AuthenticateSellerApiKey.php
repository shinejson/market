<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateSellerApiKey
{
    public function handle(Request $request, Closure $next, string $scope = ''): Response
    {
        $header = (string) $request->header('Authorization', '');
        $token = str_starts_with($header, 'Bearer ') ? substr($header, 7) : (string) $request->header('X-Api-Key');
        if ($token === '') {
            abort(401, 'API key required.');
        }

        $hash = hash('sha256', $token);
        $key = ApiKey::withoutGlobalScopes()->where('key_hash', $hash)->first();
        if (! $key || ! $key->isActive()) {
            abort(401, 'Invalid API key.');
        }
        $normalized = $scope !== '' ? str_replace('.', ':', $scope) : '';
        if ($normalized !== '' && ! $key->hasScope($normalized)) {
            abort(403, 'API key missing scope '.$normalized.'.');
        }

        $key->update(['last_used_at' => now()]);
        TenantContext::set($key->tenant_id);
        $request->attributes->set('api_key', $key);

        try {
            return $next($request);
        } finally {
            TenantContext::clear();
        }
    }
}
