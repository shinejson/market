<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiKeyController extends Controller
{
    public const SCOPES = [
        'products:read',
        'products:write',
        'orders:read',
        'orders:fulfill',
        'inventory:write',
        'settlements:read',
        'webhooks:manage',
    ];

    public function index(): JsonResponse
    {
        return response()->json(['data' => ApiKey::query()->orderByDesc('id')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'scopes' => ['required', 'array', 'min:1'],
            'scopes.*' => ['in:'.implode(',', self::SCOPES)],
            'environment' => ['nullable', 'in:live,test'],
        ]);

        $env = $data['environment'] ?? 'live';
        $plain = 'mk_'.$env.'_'.Str::random(40);
        $key = ApiKey::query()->create([
            'name' => $data['name'],
            'key_prefix' => substr($plain, 0, 16),
            'key_hash' => hash('sha256', $plain),
            'scopes' => $data['scopes'],
            'environment' => $env,
        ]);

        return response()->json([
            'data' => [
                'key' => $key,
                'secret' => $plain,
            ],
        ], 201);
    }

    public function destroy(ApiKey $apiKey): JsonResponse
    {
        $apiKey->update(['revoked_at' => now()]);

        return response()->json(['data' => $apiKey->fresh()]);
    }
}
