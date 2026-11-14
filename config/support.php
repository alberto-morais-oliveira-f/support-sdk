<?php

declare(strict_types=1);

return [
    'url' => env('SUPPORT_API_URL', ''),
    'api_url' => env('SUPPORT_API_URL', ''),
    'app_key' => env('SUPPORT_API_APP_KEY', ''),
    'app_secret' => env('SUPPORT_API_APP_SECRET', ''),
    'timeout' => (int) env('SUPPORT_API_TIMEOUT', 10),
    'retry_times' => (int) env('SUPPORT_API_RETRY_TIMES', 2),
    'retry_delay_ms' => (int) env('SUPPORT_API_RETRY_DELAY_MS', 200),
];
