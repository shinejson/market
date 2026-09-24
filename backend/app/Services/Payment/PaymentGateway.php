<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\PaymentTransaction;

interface PaymentGateway
{
    public function createIntent(Order $order, string $amount): PaymentIntentResult;

    public function verifyAndParse(WebhookRequest $req): ?WebhookResult;

    public function refund(PaymentTransaction $tx, string $amount): RefundResult;
}
