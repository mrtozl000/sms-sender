<?php

return [
    'webhook_url' => env('SMS_WEBHOOK_URL'),
    'auth_key' => env('SMS_AUTH_KEY'),
    'max_length' => env('SMS_MAX_LENGTH', 160),
    'batch_size' => env('SMS_BATCH_SIZE', 2),
    'interval_seconds' => env('SMS_INTERVAL_SECONDS', 5),
];
