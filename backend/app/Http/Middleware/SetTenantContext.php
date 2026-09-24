<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user) {
            $user->loadMissing('roles');
            if ($user->isSuperAdmin()) {
                TenantContext::bypass(true);
            } else {
                $tenantId = $user->tenantId();
                if (! $tenantId) {
                    abort(403, 'No tenant context.');
                }
                TenantContext::set($tenantId);
            }
        }

        try {
            return $next($request);
        } finally {
            TenantContext::clear();
        }
    }
}
