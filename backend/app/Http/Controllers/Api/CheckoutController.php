<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function __construct(protected CheckoutService $checkout) {}

    public function quote(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->checkout->quote($request->user())]);
    }

    public function store(Request $request): JsonResponse
    {
        $key = $request->header('Idempotency-Key');
        if (! $key) {
            throw ValidationException::withMessages([
                'Idempotency-Key' => 'Idempotency-Key header is required.',
            ]);
        }
        $data = $request->validate([
            'shipping_address_id' => ['required', 'integer', 'exists:addresses,id'],
        ]);

        $payload = $this->checkout->checkout(
            $request->user(),
            (int) $data['shipping_address_id'],
            $key,
        );

        return response()->json($payload, 201);
    }
}
