<?php

return [

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'web_push' => [
        'public_key' => env('WEB_PUSH_VAPID_PUBLIC_KEY'),
        'private_key' => env('WEB_PUSH_VAPID_PRIVATE_KEY'),
        'subject' => env('WEB_PUSH_VAPID_SUBJECT'),
    ],

    'paystack' => [
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
        'company_registration_fee' => env('PAYSTACK_COMPANY_REGISTRATION_FEE', env('PAYSTACK_COMPANY_SUBSCRIPTION_FEE', 50000)),
        'company_subscription_fee' => env('PAYSTACK_COMPANY_SUBSCRIPTION_FEE', env('PAYSTACK_COMPANY_REGISTRATION_FEE', 50000)),
        'subscription_duration_months' => env('PAYSTACK_COMPANY_SUBSCRIPTION_DURATION_MONTHS', 12),
    ],

];
