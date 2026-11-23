<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MidtransWebhookController extends Controller
{
    public function __construct(private OrderService $orderService) {}

    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->all();

        $orderId = $payload['order_id'] ?? null;
        $statusCode = $payload['status_code'] ?? null;
        $grossAmount = $payload['gross_amount'] ?? null;
        $signatureKey = $payload['signature_key'] ?? null;
        $transactionStatus = $payload['transaction_status'] ?? null;
        $fraudStatus = $payload['fraud_status'] ?? null;

        if (! $orderId || ! $statusCode || ! $grossAmount || ! $signatureKey) {
            return response()->json(['message' => 'Payload tidak valid.'], 400);
        }

        $serverKey = (string) config('talentra.payments.midtrans.server_key');
        $expectedSignature = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        if (! hash_equals($expectedSignature, (string) $signatureKey)) {
            Log::warning('Midtrans webhook invalid signature', [
                'order_id' => $orderId,
                'received' => $signatureKey,
                'expected' => $expectedSignature,
            ]);

            return response()->json(['message' => 'Signature tidak valid.'], 403);
        }

        $order = Order::query()->where('code', $orderId)->first();

        if (! $order) {
            Log::warning('Midtrans webhook order tidak ditemukan', ['order_id' => $orderId]);

            return response()->json(['message' => 'Order tidak ditemukan.'], 404);
        }

        $currentPayload = $order->payload ?? [];
        $currentPayload['webhook_history'] = array_merge($currentPayload['webhook_history'] ?? [], [$payload]);
        $order->payload = $currentPayload;
        $order->save();

        if ($transactionStatus === 'capture') {
            if ($fraudStatus === 'accept') {
                $this->orderService->markPaid($order);
            }
        } elseif ($transactionStatus === 'settlement') {
            $this->orderService->markPaid($order);
        } elseif (in_array($transactionStatus, ['cancel', 'deny'], true)) {
            $this->orderService->cancel($order);
        } elseif ($transactionStatus === 'expire') {
            $order->update(['status' => OrderStatus::Expired]);
        }

        return response()->json(['message' => 'Webhook berhasil diproses.']);
    }
}
