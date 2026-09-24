<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Order $order): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return (int) $order->user_id === (int) $user->id;
    }

    public function cancel(User $user, Order $order): bool
    {
        return $this->view($user, $order) && $order->canCancel();
    }
}
