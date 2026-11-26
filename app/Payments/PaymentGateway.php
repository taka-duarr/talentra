<?php

namespace App\Payments;

use App\Models\Order;

/**
 * Contract every payment provider implements: manual transfer verified by an admin,
 * or Midtrans Core API with automatic confirmation.
 */
interface PaymentGateway
{
    public function name(): string;

    /**
     * Prepares the order for payment and returns what the order page shows the buyer.
     *
     * @return array{type: string, accounts?: array<int, array{bank: string, number: string, holder: string}>, contact?: string, channels?: array<string, array<string, mixed>>, preferred?: list<string>, charge?: array<string, mixed>|null, is_production?: bool, simulator_url?: string|null}
     */
    public function instructions(Order $order): array;

    /**
     * Whether the buyer has to upload a transfer receipt for an admin to verify.
     */
    public function requiresProof(): bool;
}
