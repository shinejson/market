<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TenantDomain;
use App\Services\Domains\DomainService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DomainController extends Controller
{
    public function __construct(protected DomainService $domains) {}

    public function index(): JsonResponse
    {
        return response()->json(['data' => TenantDomain::query()->orderByDesc('id')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'domain' => ['required', 'string', 'max:255', 'unique:tenant_domains,domain'],
        ]);
        $domain = $this->domains->request((int) $request->user()->tenantId(), $data['domain']);

        return response()->json([
            'data' => [
                'domain' => $domain,
                'txt_name' => '_markethub-verify.'.$domain->domain,
                'txt_value' => $domain->verification_token,
            ],
        ], 201);
    }

    public function verify(Request $request, TenantDomain $domain): JsonResponse
    {
        $force = $request->boolean('force') && app()->environment('local', 'testing');
        $updated = $this->domains->verify($domain, $force);

        return response()->json(['data' => $updated]);
    }

    public function destroy(TenantDomain $domain): JsonResponse
    {
        $domain->update(['status' => TenantDomain::STATUS_REMOVED]);

        return response()->json(['data' => $domain->fresh()]);
    }
}
