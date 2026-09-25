<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'platform' => ['required', 'in:ios,android,web'],
            'token' => ['required', 'string', 'max:512'],
        ]);
        $row = DeviceToken::query()->updateOrCreate(
            ['token' => $data['token']],
            [
                'user_id' => $request->user()->id,
                'platform' => $data['platform'],
                'last_seen_at' => now(),
            ],
        );

        return response()->json(['data' => $row], 201);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate(['token' => ['required', 'string']]);
        DeviceToken::query()->where('user_id', $request->user()->id)->where('token', $data['token'])->delete();

        return response()->json(['data' => ['ok' => true]]);
    }
}
