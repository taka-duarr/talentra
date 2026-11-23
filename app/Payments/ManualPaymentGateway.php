<?php

namespace App\Payments;

use App\Models\Order;

class ManualPaymentGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'manual';
    }

    public function instructions(Order $order): array
    {
        return [
            'type' => 'manual_transfer',
            'accounts' => config('talentra.payments.manual.accounts'),
            'contact' => config('talentra.payments.manual.contact'),
        ];
    }

    public function requiresProof(): bool
    {
        return true;
    }
}
