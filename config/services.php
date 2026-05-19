<?php

return [

    'stripe' => [
        'secret_key' => env('STRIPE_SECRET_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'prices' => [
            'starter' => env('STRIPE_PRICE_STARTER'),
            'pro' => env('STRIPE_PRICE_PRO'),
            'enterprise' => env('STRIPE_PRICE_ENTERPRISE'),
        ],
    ],

];
