<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Voucher;
use App\Notifications\OrderStatusChanged;
use App\Notifications\SubscriptionExpiring;
use App\Payments\PaymentGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(private PaymentGateway $gateway) {}

    /**
     * Finds a usable voucher by code, or throws a validation error for the given field.
     */
    public function findUsableVoucher(string $code, string $field = 'voucher_code'): Voucher
    {
        $voucher = Voucher::query()->where('code', Str::upper(trim($code)))->first();

        if ($voucher === null || ! $voucher->isUsable()) {
            throw ValidationException::withMessages([$field => 'Kode voucher tidak valid atau sudah tidak berlaku.']);
        }

        return $voucher;
    }

    public function create(User $user, PlanPrice $price, ?string $voucherCode, ?string $paymentMethod): Order
    {
        $voucher = filled($voucherCode) ? $this->findUsableVoucher($voucherCode) : null;
        $discount = $voucher?->discountFor($price->price) ?? 0;

        return Order::create([
            'code' => $this->generateCode(),
            'user_id' => $user->id,
            'plan_id' => $price->plan_id,
            'months' => $price->months,
            'subtotal' => $price->price,
            'discount' => $discount,
            'total' => $price->price - $discount,
            'voucher_id' => $voucher?->id,
            'status' => OrderStatus::Pending,
            'payment_method' => $paymentMethod,
            'gateway' => $this->gateway->name(),
            'expires_at' => now()->addHours(config('talentra.payments.order_expiry_hours')),
        ]);
    }

    /**
     * Marks the order paid and grants (or extends) the subscription. Safe to call twice.
     */
    public function markPaid(Order $order, ?User $verifier = null): Subscription
    {
        $wasPaid = $order->refresh()->status === OrderStatus::Paid;

        $subscription = DB::transaction(function () use ($order, $verifier) {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($order->status === OrderStatus::Paid) {
                return $order->subscription()->firstOrFail();
            }

            if (! in_array($order->status, [OrderStatus::Pending, OrderStatus::Expired], true)) {
                throw ValidationException::withMessages(['order' => 'Pesanan dengan status '.$order->status->label().' tidak bisa disetujui.']);
            }

            $order->forceFill([
                'status' => OrderStatus::Paid,
                'paid_at' => now(),
                'verified_by' => $verifier?->id,
            ])->save();

            if ($order->voucher_id) {
                Voucher::query()->whereKey($order->voucher_id)->increment('used_count');
            }

            // Buying the same plan again extends it from the end of the current period.
            $latestEnd = Subscription::query()
                ->where('user_id', $order->user_id)
                ->where('plan_id', $order->plan_id)
                ->where('ends_at', '>', now())
                ->max('ends_at');

            $startsAt = $latestEnd ? now()->parse($latestEnd) : now();

            return Subscription::create([
                'user_id' => $order->user_id,
                'plan_id' => $order->plan_id,
                'order_id' => $order->id,
                'starts_at' => $startsAt,
                'ends_at' => $startsAt->copy()->addMonths($order->months),
            ]);
        });

        if (! $wasPaid) {
            $order->refresh()->user->notify(new OrderStatusChanged($order->load('plan', 'subscription')));
        }

        return $subscription;
    }

    public function reject(Order $order, User $verifier, ?string $note): void
    {
        if (! $order->isPending()) {
            throw ValidationException::withMessages(['order' => 'Hanya pesanan yang menunggu pembayaran yang bisa ditolak.']);
        }

        $order->forceFill([
            'status' => OrderStatus::Rejected,
            'verified_by' => $verifier->id,
            'admin_note' => $note,
        ])->save();

        $order->user->notify(new OrderStatusChanged($order->load('plan')));
    }

    /**
     * Sends H-7 and H-1 reminders for subscriptions that are not renewed yet.
     */
    public function remindExpiringSubscriptions(): int
    {
        $sent = 0;

        foreach ([7, 1] as $days) {
            $subscriptions = Subscription::query()
                ->with(['user', 'plan'])
                ->whereBetween('ends_at', [now()->addDays($days)->startOfDay(), now()->addDays($days)->endOfDay()])
                ->get();

            foreach ($subscriptions as $subscription) {
                $renewed = Subscription::query()
                    ->where('user_id', $subscription->user_id)
                    ->where('plan_id', $subscription->plan_id)
                    ->where('ends_at', '>', $subscription->ends_at)
                    ->exists();

                if (! $renewed) {
                    $subscription->user->notify(new SubscriptionExpiring($subscription, $days));
                    $sent++;
                }
            }
        }

        return $sent;
    }

    public function cancel(Order $order): void
    {
        if ($order->isPending()) {
            $order->forceFill(['status' => OrderStatus::Cancelled])->save();
        }
    }

    /**
     * Expires pending orders past their deadline that have no uploaded proof waiting for review.
     */
    public function expireOverdue(): int
    {
        return Order::query()
            ->where('status', OrderStatus::Pending)
            ->whereNull('proof_path')
            ->where('expires_at', '<', now())
            ->update(['status' => OrderStatus::Expired]);
    }

    private function generateCode(): string
    {
        do {
            $code = 'TLN-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
        } while (Order::query()->where('code', $code)->exists());

        return $code;
    }
}
