<?php

return [

    /*
    | Payments. Until a gateway (Midtrans/Xendit) is connected, orders are paid by
    | manual transfer and verified by a super admin.
    */
    'payments' => [
        'gateway' => env('TALENTRA_PAYMENT_GATEWAY', 'manual'),
        'order_expiry_hours' => 24,
        'manual' => [
            'accounts' => [
                ['bank' => 'BCA', 'number' => env('TALENTRA_BCA_ACCOUNT', '0000000000'), 'holder' => env('TALENTRA_ACCOUNT_HOLDER', 'Talentra')],
                ['bank' => 'Mandiri', 'number' => env('TALENTRA_MANDIRI_ACCOUNT', '0000000000000'), 'holder' => env('TALENTRA_ACCOUNT_HOLDER', 'Talentra')],
            ],
            'contact' => env('TALENTRA_PAYMENT_CONTACT', 'admin@talentra.test'),
        ],
        'midtrans' => [
            'server_key' => env('MIDTRANS_SERVER_KEY'),
            'client_key' => env('MIDTRANS_CLIENT_KEY'),
            'merchant_id' => env('MIDTRANS_MERCHANT_ID'),
            'is_production' => (bool) env('MIDTRANS_IS_PRODUCTION', false),
        ],
        'methods' => [
            'qris' => 'QRIS',
            'bank_transfer' => 'Transfer bank / Virtual Account',
            'ewallet' => 'E-wallet (GoPay, OVO, DANA)',
        ],
    ],

    /*
    | AI result analysis. Without an API key the rule-based analysis is used.
    */
    'analysis' => [
        'driver' => env('TALENTRA_ANALYSIS_DRIVER', 'rules'),
        'anthropic' => [
            'api_key' => env('ANTHROPIC_API_KEY'),
            'model' => env('TALENTRA_ANALYSIS_MODEL', 'claude-opus-5-5'),
            'max_tokens' => 1500,
            'timeout' => 45,
        ],
        'daily_limit_per_user' => (int) env('TALENTRA_ANALYSIS_DAILY_LIMIT', 10),
        // true: run on the queue (needs `php artisan queue:work`). false: run right after the response.
        'use_queue' => (bool) env('TALENTRA_ANALYSIS_QUEUE', false),
    ],

    /*
    | Global ranking points. Each award is stored once per source.
    */
    'points' => [
        'tryout_completed' => 10,
        'tryout_passed' => 20,
        'chapter_completed' => 5,
        'best_answer' => 5,
    ],

    'ranking' => [
        // Minimum ranked packages a user must finish to appear in a category ranking.
        'category_min_packages' => (int) env('TALENTRA_RANKING_CATEGORY_MIN', 1),
        'cache_minutes' => 10,
        'page_size' => 50,
    ],

    'forum' => [
        'posts_per_window' => 6,
        'window_minutes' => 5,
        'blocked_words' => ['anjing', 'bangsat', 'babi', 'kontol', 'memek', 'goblok', 'tolol', 'judi online', 'slot gacor'],
    ],

    'mastery_levels' => [
        ['min' => 85, 'label' => 'Sangat baik'],
        ['min' => 70, 'label' => 'Baik'],
        ['min' => 50, 'label' => 'Cukup'],
        ['min' => 0, 'label' => 'Perlu ditingkatkan'],
    ],
];
