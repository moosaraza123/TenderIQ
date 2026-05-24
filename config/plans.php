<?php

return [

    'free' => [
        'name'            => 'Free',
        'price'           => 0,
        'stripe_price_id' => null,
        'color'           => 'slate',
        'badge'           => 'Free Forever',
        'features'        => [
            'daily_views'     => 5,
            'alerts'          => 1,
            'alert_frequency' => 'daily',
            'api_calls'       => 0,
            'csv_export'      => false,
            'ai_summaries'    => false,
            'webhooks'        => false,
        ],
        'sources'   => ['ppra_federal'],
        'countries' => ['PK'],
    ],

    'starter' => [
        'name'            => 'Starter',
        'price'           => 29,
        'stripe_price_id' => env('STRIPE_STARTER_PRICE_ID'),
        'color'           => 'teal',
        'badge'           => 'Most Popular',
        'features'        => [
            'daily_views'     => PHP_INT_MAX,
            'alerts'          => 5,
            'alert_frequency' => 'instant',
            'api_calls'       => 0,
            'csv_export'      => true,
            'ai_summaries'    => true,
            'webhooks'        => false,
        ],
        'sources'   => ['ppra_federal', 'uk_fts', 'uk_cf'],
        'countries' => ['PK', 'GB'],
    ],

    'professional' => [
        'name'            => 'Professional',
        'price'           => 49,
        'stripe_price_id' => env('STRIPE_PROFESSIONAL_PRICE_ID'),
        'color'           => 'blue',
        'badge'           => 'Best Value',
        'features'        => [
            'daily_views'     => PHP_INT_MAX,
            'alerts'          => 20,
            'alert_frequency' => 'instant',
            'api_calls'       => 0,
            'csv_export'      => true,
            'ai_summaries'    => true,
            'webhooks'        => false,
        ],
        'sources'   => ['ppra_federal', 'uk_fts', 'uk_cf', 'sam_gov', 'world_bank', 'ungm', 'adb', 'afdb'],
        'countries' => ['PK', 'GB', 'US', 'WB', 'UN'],
    ],

    'enterprise' => [
        'name'            => 'Enterprise',
        'price'           => 99,
        'stripe_price_id' => env('STRIPE_ENTERPRISE_PRICE_ID'),
        'color'           => 'purple',
        'badge'           => 'Full Access',
        'features'        => [
            'daily_views'     => PHP_INT_MAX,
            'alerts'          => PHP_INT_MAX,
            'alert_frequency' => 'instant',
            'api_calls'       => 1000,
            'csv_export'      => true,
            'ai_summaries'    => true,
            'webhooks'        => true,
        ],
        'sources'   => ['*'],
        'countries' => ['*'],
    ],

];
