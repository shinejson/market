<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Analytics\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(protected AnalyticsService $analytics) {}

    public function tenant(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->analytics->marts($request->user()->tenantId())]);
    }

    public function platform(): JsonResponse
    {
        return response()->json(['data' => $this->analytics->marts(null)]);
    }

    public function platformInsights(): JsonResponse
    {
        return response()->json(['data' => $this->analytics->narrate(null, 'platform')]);
    }
}
