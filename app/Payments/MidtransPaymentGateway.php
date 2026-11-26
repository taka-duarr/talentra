<?php

namespace App\Payments;

use App\Models\Order;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Midtrans Core API gateway. Talentra renders the whole payment UI itself:
 * the buyer picks a channel, we charge it server side and show the VA number,
 * bill key, QR code or app deeplink that Midtrans returns.
 */
class MidtransPaymentGateway implements PaymentGateway
{
    /**
     * Payment channels offered on the order page, grouped for display.
     *
     * @var array<string, array{label: string, group: string, short: string, tone: string, steps: list<string>}>
     */
    public const CHANNELS = [
        'qris' => [
            'label' => 'QRIS',
            'group' => 'instant',
            'short' => 'QRIS',
            'tone' => 'bg-ink text-white',
            'steps' => [
                'Buka aplikasi bank atau e-wallet apa pun yang mendukung QRIS (GoPay, OVO, DANA, ShopeePay, m-banking).',
                'Pindai kode QR di samping.',
                'Periksa nominal, lalu konfirmasi pembayaran.',
            ],
        ],
        'gopay' => [
            'label' => 'GoPay',
            'group' => 'instant',
            'short' => 'GoPay',
            'tone' => 'bg-[#00aed6] text-white',
            'steps' => [
                'Di HP: ketuk tombol "Buka aplikasi GoPay". Di komputer: pindai kode QR dengan aplikasi Gojek.',
                'Periksa nominal, lalu konfirmasi dengan PIN GoPay.',
            ],
        ],
        'shopeepay' => [
            'label' => 'ShopeePay',
            'group' => 'instant',
            'short' => 'SPay',
            'tone' => 'bg-[#ee4d2d] text-white',
            'steps' => [
                'Buka halaman ini dari HP yang terpasang aplikasi Shopee.',
                'Ketuk tombol "Buka aplikasi ShopeePay", lalu konfirmasi dengan PIN.',
            ],
        ],
        'bca' => [
            'label' => 'BCA Virtual Account',
            'group' => 'va',
            'short' => 'BCA',
            'tone' => 'bg-[#0060af] text-white',
            'steps' => [
                'Buka BCA mobile atau KlikBCA, pilih m-Transfer › BCA Virtual Account.',
                'Masukkan nomor Virtual Account di atas.',
                'Pastikan nama dan nominal sesuai, lalu masukkan PIN.',
            ],
        ],
        'bni' => [
            'label' => 'BNI Virtual Account',
            'group' => 'va',
            'short' => 'BNI',
            'tone' => 'bg-[#f15a23] text-white',
            'steps' => [
                'Buka BNI Mobile Banking, pilih Transfer › Virtual Account Billing.',
                'Masukkan nomor Virtual Account di atas.',
                'Periksa tagihan, lalu konfirmasi dengan password transaksi.',
            ],
        ],
        'bri' => [
            'label' => 'BRI Virtual Account',
            'group' => 'va',
            'short' => 'BRI',
            'tone' => 'bg-[#00529c] text-white',
            'steps' => [
                'Buka BRImo, pilih Pembayaran › BRIVA.',
                'Masukkan nomor Virtual Account di atas.',
                'Periksa tagihan, lalu konfirmasi dengan PIN.',
            ],
        ],
        'mandiri' => [
            'label' => 'Mandiri Bill Payment',
            'group' => 'va',
            'short' => 'MDR',
            'tone' => 'bg-[#003d79] text-[#ffb700]',
            'steps' => [
                'Buka Livin\' by Mandiri, pilih Bayar › Multipayment / e-Commerce.',
                'Cari penyedia jasa "Midtrans" atau masukkan kode perusahaan di atas.',
                'Masukkan kode bayar di atas, periksa tagihan, lalu konfirmasi dengan PIN.',
            ],
        ],
        'permata' => [
            'label' => 'Permata Virtual Account',
            'group' => 'va',
            'short' => 'PMT',
            'tone' => 'bg-[#4b9a3c] text-white',
            'steps' => [
                'Buka PermataMobile X, pilih Bayar Tagihan › Virtual Account.',
                'Masukkan nomor Virtual Account di atas.',
                'Periksa tagihan, lalu konfirmasi.',
            ],
        ],
    ];

    public function name(): string
    {
        return 'midtrans';
    }

    /**
     * @return array{type: string, channels: array<string, array<string, mixed>>, preferred: list<string>, charge: array<string, mixed>|null, is_production: bool, simulator_url: string|null}
     */
    public function instructions(Order $order): array
    {
        $isProduction = $this->isProduction();

        return [
            'type' => 'midtrans',
            'channels' => self::CHANNELS,
            'preferred' => $this->preferredChannels($order->payment_method),
            'charge' => $this->currentCharge($order),
            'is_production' => $isProduction,
            'simulator_url' => $isProduction ? null : 'https://simulator.sandbox.midtrans.com',
        ];
    }

    public function requiresProof(): bool
    {
        return false;
    }

    /**
     * Channels that match the method picked at checkout, shown first.
     *
     * @return list<string>
     */
    public function preferredChannels(?string $paymentMethod): array
    {
        return match ($paymentMethod) {
            'bank_transfer' => ['bca', 'bni', 'bri', 'mandiri', 'permata'],
            'ewallet' => ['gopay', 'shopeepay', 'qris'],
            default => ['qris', 'gopay', 'shopeepay'],
        };
    }

    /**
     * The active charge stored on the order, if any.
     *
     * @return array<string, mixed>|null
     */
    public function currentCharge(Order $order): ?array
    {
        $charge = $order->payload['charge'] ?? null;

        return is_array($charge) ? $charge : null;
    }

    /**
     * Charges the order through the given channel and stores a normalized copy of the result.
     *
     * @return array<string, mixed>
     */
    public function charge(Order $order, string $channel): array
    {
        if (! array_key_exists($channel, self::CHANNELS)) {
            throw new RuntimeException('Metode pembayaran tidak dikenal.');
        }

        $order->loadMissing(['user', 'plan']);

        $attempt = (int) ($order->payload['charge_attempts'] ?? 0) + 1;
        $transactionId = $attempt === 1 ? $order->code : $order->code.'-'.$attempt;
        $minutesLeft = max(15, (int) ceil(now()->diffInMinutes($order->expires_at, false)));

        $params = array_merge([
            'transaction_details' => [
                'order_id' => $transactionId,
                'gross_amount' => (int) $order->total,
            ],
            'customer_details' => [
                'first_name' => mb_substr($order->user->name, 0, 50),
                'email' => $order->user->email,
            ],
            'item_details' => [[
                'id' => (string) $order->plan_id,
                'price' => (int) $order->total,
                'quantity' => 1,
                'name' => mb_substr($order->plan->name.' ('.$order->durationLabel().')', 0, 50),
            ]],
            'custom_expiry' => [
                'expiry_duration' => $minutesLeft,
                'unit' => 'minute',
            ],
        ], $this->channelParameters($order, $channel));

        $response = $this->client()->post($this->apiBase().'/v2/charge', $params);
        $data = $this->assertSuccessful($response, $order, 'charge');

        $charge = $this->normalizeCharge($channel, $transactionId, $data);

        $order->forceFill([
            'gateway' => 'midtrans',
            'gateway_reference' => $transactionId,
            'payload' => array_merge($order->payload ?? [], [
                'charge' => $charge,
                'charge_attempts' => $attempt,
            ]),
        ])->save();

        return $charge;
    }

    /**
     * Cancels the active charge (best effort) so the buyer can pick another channel.
     */
    public function resetCharge(Order $order): void
    {
        $charge = $this->currentCharge($order);

        if ($charge === null) {
            return;
        }

        try {
            $this->client()->post($this->apiBase().'/v2/'.rawurlencode((string) $charge['order_id']).'/cancel');
        } catch (\Throwable $exception) {
            Log::info('Midtrans cancel gagal, transaksi dibiarkan kedaluwarsa', ['order' => $order->code, 'error' => $exception->getMessage()]);
        }

        $payload = $order->payload ?? [];
        $payload['previous_charges'] = array_merge($payload['previous_charges'] ?? [], [$charge]);
        unset($payload['charge']);

        $order->forceFill(['payload' => $payload])->save();
    }

    /**
     * Checks the status of the active transaction at Midtrans.
     *
     * @return array<string, mixed>|null
     */
    public function checkStatus(Order $order): ?array
    {
        if (blank($this->serverKey()) || blank($order->gateway_reference)) {
            return null;
        }

        try {
            $response = $this->client()->get($this->apiBase().'/v2/'.rawurlencode($order->gateway_reference).'/status');
        } catch (\Throwable) {
            return null;
        }

        return $response->successful() ? $response->json() : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function channelParameters(Order $order, string $channel): array
    {
        $returnUrl = route('orders.show', $order);

        return match ($channel) {
            'qris' => ['payment_type' => 'qris', 'qris' => ['acquirer' => 'gopay']],
            'gopay' => ['payment_type' => 'gopay', 'gopay' => ['enable_callback' => true, 'callback_url' => $returnUrl]],
            'shopeepay' => ['payment_type' => 'shopeepay', 'shopeepay' => ['callback_url' => $returnUrl]],
            'mandiri' => ['payment_type' => 'echannel', 'echannel' => ['bill_info1' => 'Pembayaran', 'bill_info2' => 'Talentra '.$order->code]],
            default => ['payment_type' => 'bank_transfer', 'bank_transfer' => ['bank' => $channel]],
        };
    }

    /**
     * Keeps only what the order page needs from the charge response.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeCharge(string $channel, string $transactionId, array $data): array
    {
        $actions = collect($data['actions'] ?? [])->keyBy('name');

        return array_filter([
            'channel' => $channel,
            'order_id' => $transactionId,
            'transaction_id' => $data['transaction_id'] ?? null,
            'va_number' => Arr::get($data, 'va_numbers.0.va_number') ?? ($data['permata_va_number'] ?? null),
            'biller_code' => $data['biller_code'] ?? null,
            'bill_key' => $data['bill_key'] ?? null,
            'qr_url' => $actions->get('generate-qr-code')['url'] ?? null,
            'deeplink' => $actions->get('deeplink-redirect')['url'] ?? null,
            'expires_at' => $data['expiry_time'] ?? null,
            'created_at' => now()->toIso8601String(),
        ], fn ($value) => $value !== null);
    }

    /**
     * @return array<string, mixed>
     */
    private function assertSuccessful(Response $response, Order $order, string $action): array
    {
        $data = $response->json() ?? [];
        $statusCode = (string) ($data['status_code'] ?? $response->status());

        if ($response->successful() && str_starts_with($statusCode, '2')) {
            return $data;
        }

        Log::error("Midtrans {$action} gagal", [
            'order' => $order->code,
            'http' => $response->status(),
            'status_code' => $statusCode,
            'message' => $data['status_message'] ?? null,
        ]);

        throw new RuntimeException('Metode ini sedang tidak bisa dipakai. Coba metode lain atau ulangi beberapa saat lagi.');
    }

    private function client(): PendingRequest
    {
        if (blank($this->serverKey())) {
            throw new RuntimeException('Pembayaran otomatis belum dikonfigurasi. Hubungi admin.');
        }

        return Http::withBasicAuth($this->serverKey(), '')->acceptJson()->asJson()->timeout(20);
    }

    private function apiBase(): string
    {
        return $this->isProduction() ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com';
    }

    private function serverKey(): string
    {
        return (string) config('talentra.payments.midtrans.server_key');
    }

    private function isProduction(): bool
    {
        return (bool) config('talentra.payments.midtrans.is_production', false);
    }
}
