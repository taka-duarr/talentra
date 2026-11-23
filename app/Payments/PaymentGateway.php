<?php

namespace App\Payments;

use App\Models\Order;

/**
 * Contract every payment provider implements. Today only ManualPaymentGateway exists;
 * Midtrans or Xendit can be added later without touching the checkout pages.
 */
interface PaymentGateway
{
    public function name(): string;

    /**
     * Prepares the order for payment and returns what the order page shows the buyer.
     *
     * @return array{type: string, accounts?: array<int, array{bank: string, number: string, holder: string}>, contact?: string, redirect_url?: string}
     */
    public function instructions(Order $order): array;

    /**
     * Whether the buyer has to upload a transfer receipt for an admin to verify.
     */
    public function requiresProof(): bool;
}
