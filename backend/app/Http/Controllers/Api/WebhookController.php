<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Services\Integration\WebhookDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WebhookController extends Controller
{
    public function __construct(protected WebhookDispatcher $dispatcher) {}

    public function index(): JsonResponse
    {
        return response()->json(['data' => WebhookEndpoint::query()->orderByDesc('id')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'url' => ['required', 'url', 'max:500'],
            'event_types' => ['required', 'array', 'min:1'],
            'event_types.*' => ['string'],
        ]);

        foreach ($data['event_types'] as $type) {
            if ($type !== '*' && ! in_array($type, WebhookDispatcher::CATALOG, true)) {
                throw ValidationException::withMessages(['event_types' => 'Unknown event type '.$type]);
            }
        }

        $plain = 'whsec_'.Str::random(32);
        $endpoint = WebhookEndpoint::query()->create([
            'url' => $data['url'],
            'secret_hash' => hash('sha256', $plain),
            'secret_prefix' => substr($plain, 0, 10),
            'event_types' => $data['event_types'],
            'status' => WebhookEndpoint::STATUS_PENDING,
        ]);

        $verified = $this->dispatcher->challenge($endpoint, $plain);
        $endpoint->update(['status' => $verified ? WebhookEndpoint::STATUS_ACTIVE : WebhookEndpoint::STATUS_PENDING]);

        return response()->json([
            'data' => [
                'endpoint' => $endpoint->fresh(),
                'secret' => $plain,
                'verified' => $verified,
            ],
        ], 201);
    }

    public function destroy(WebhookEndpoint $endpoint): JsonResponse
    {
        $endpoint->delete();

        return response()->json(['data' => ['ok' => true]]);
    }

    public function deliveries(WebhookEndpoint $endpoint): JsonResponse
    {
        $page = WebhookDelivery::query()->where('endpoint_id', $endpoint->id)->orderByDesc('id')->paginate(20);

        return response()->json([
            'data' => $page->items(),
            'meta' => ['page' => $page->currentPage(), 'total' => $page->total(), 'last_page' => $page->lastPage(), 'per_page' => $page->perPage()],
        ]);
    }

    public function replay(WebhookDelivery $delivery): JsonResponse
    {
        $endpoint = $delivery->endpoint;
        $fresh = $this->dispatcher->attempt($endpoint, $delivery->event_id, $delivery->event_type, $delivery->payload['data'] ?? $delivery->payload);

        return response()->json(['data' => $fresh]);
    }

    public function catalog(): JsonResponse
    {
        return response()->json(['data' => WebhookDispatcher::CATALOG]);
    }
}
