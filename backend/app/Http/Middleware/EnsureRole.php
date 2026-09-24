<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        if (! $user) {
            abort(401, 'Authentication required.');
        }

        $user->loadMissing('roles');

        foreach ($roles as $role) {
            if ($role === 'super_admin' && $user->isSuperAdmin()) {
                return $next($request);
            }
            if ($role === 'tenant_owner' && $user->isTenantOwner()) {
                return $next($request);
            }
            if ($role === 'store_staff' && $user->isStoreStaff()) {
                return $next($request);
            }
            if ($role === 'customer' && ($user->isCustomer() || $user->isTenantOwner() || $user->isStoreStaff() || $user->isSuperAdmin())) {
                return $next($request);
            }
            if ($role === 'tenant' && ($user->isTenantOwner() || $user->isStoreStaff())) {
                return $next($request);
            }
        }

        abort(403, 'Insufficient role.');
    }
}
