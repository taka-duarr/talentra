<?php

use App\Services\OrderService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('orders:expire', function (OrderService $orders) {
    $this->info($orders->expireOverdue().' pesanan ditandai kedaluwarsa.');
})->purpose('Tandai pesanan tanpa bukti bayar yang lewat batas waktu sebagai kedaluwarsa');

Artisan::command('subscriptions:remind', function (OrderService $orders) {
    $this->info($orders->remindExpiringSubscriptions().' pengingat langganan dikirim.');
})->purpose('Kirim pengingat H-7 dan H-1 sebelum langganan berakhir');

Schedule::command('orders:expire')->hourly();
Schedule::command('subscriptions:remind')->dailyAt('08:00');
