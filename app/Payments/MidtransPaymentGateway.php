<?php

namespace App\Payments;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MidtransPaymentGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'midtrans';
    }

    /**
     * Prepares the order for payment and returns what the order page shows the buyer.
     *
     * @return array{type: string, snap_token: string, redirect_url: string, client_key: string, is_production: bool}
     */
    public function instructions(Order $order): array
    {
        $snapToken = $this->getOrCreateSnapToken($order);

        $isProduction = (bool) config('talentra.payments.midtrans.is_production', false);
        $clientKey = (string) config('talentra.payments.midtrans.client_key', '');

        $redirectUrl = $order->payload['redirect_url'] ?? (
            $isProduction
                ? "https://app.midtrans.com/snap/v4/redirection/{$snapToken}"
                : "https://app.sandbox.midtrans.com/snap/v4/redirection/{$snapToken}"
        );

        return [
            'type' => 'midtrans',
            'snap_token' => $snapToken,
            'redirect_url' => $redirectUrl,
            'client_key' => $clientKey,
            'is_production' => $isProduction,
        ];
    }

    public function requiresProof(): bool
    {
        return false;
    }

    /**
     * Resolves an existing Snap token from order or requests a new one from Midtrans API.
     */
    public function getOrCreateSnapToken(Order $order): string
    {
        if (filled($order->gateway_reference) && ! empty($order->payload['snap_token'])) {
            return (string) $order->payload['snap_token'];
        }

        $serverKey = (string) config('talentra.payments.midtrans.server_key');
        if (blank($serverKey)) {
            throw new RuntimeException('Midtrans Server Key belum dikonfigurasi.');
        }

        $isProduction = (bool) config('talentra.payments.midtrans.is_production', false);
        $endpoint = $isProduction
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

        $order->loadMissing(['user', 'plan']);

        $params = [
            'transaction_details' => [
                'order_id' => $order->code,
                'gross_amount' => (int) $order->total,
            ],
            'customer_details' => [
                'first_name' => $order->user->name,
                'email' => $order->user->email,
            ],
            'item_details' => [
                [
                    'id' => (string) $order->plan_id,
                    'price' => (int) $order->total,
                    'quantity' => 1,
                    'name' => mb_substr($order->plan->name.' ('.$order->durationLabel().')', 0, 50),
                ],
            ],
        ];

        if ($order->payment_method === 'qris') {
            $params['enabled_payments'] = ['qris', 'gopay', 'shopeepay'];
        } elseif ($order->payment_method === 'bank_transfer') {
            $params['enabled_payments'] = ['bca_va', 'bni_va', 'bri_va', 'echannel', 'permata_va', 'other_va'];
        } elseif ($order->payment_method === 'ewallet') {
            $params['enabled_payments'] = ['gopay', 'shopeepay', 'qris'];
        }

        $params['callbacks'] = [
            'finish' => route('orders.show', $order),
        ];

        $response = Http::withBasicAuth($serverKey, '')
            ->acceptJson()
            ->asJson()
            ->post($endpoint, $params);

        if ($response->failed()) {
            Log::error('Gagal membuat transaksi Midtrans Snap', [
                'order' => $order->code,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('Gagal terhubung dengan layanan pembayaran Midtrans: '.$response->body());
        }

        $data = $response->json();
        $token = (string) ($data['token'] ?? '');
        $redirectUrl = (string) ($data['redirect_url'] ?? '');

        $order->forceFill([
            'gateway' => 'midtrans',
            'gateway_reference' => $token,
            'payload' => [
                'snap_token' => $token,
                'redirect_url' => $redirectUrl,
            ],
        ])->save();

        return $token;
    }

    /**
     * Checks current transaction status from Midtrans API.
     *
     * @return array{status_code: string, transaction_status: string, fraud_status?: string}|null
     */
    public function checkStatus(Order $order): ?array
    {
        $serverKey = (string) config('talentra.payments.midtrans.server_key');
        if (blank($serverKey)) {
            return null;
        }

        $isProduction = (bool) config('talentra.payments.midtrans.is_production', false);
        $endpoint = $isProduction
            ? "https://api.midtrans.com/v2/{$order->code}/status"
            : "https://api.sandbox.midtrans.com/v2/{$order->code}/status";

        $response = Http::withBasicAuth($serverKey, '')
            ->acceptJson()
            ->get($endpoint);

        if ($response->failed()) {
            return null;
        }

        return $response->json();
    }
}
