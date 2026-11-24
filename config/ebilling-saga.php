<?php

return [
    // This API B instance is connected to exactly one country request stream.
    'redis_connection' => env('EBILLING_REDIS_CONNECTION', 'default'),
    'redis_prefix' => env('EBILLING_REDIS_STREAM_PREFIX', ''),
    'request_stream' => env('EBILLING_REQUESTS_STREAM'),
    'results_stream' => env('EBILLING_RESULTS_STREAM', 'billing.fiscal.results'),
    'consumer_group' => env('EBILLING_CONSUMER_GROUP', 'ebilling-workers'),

    'consumer' => [
        'count' => (int) env('EBILLING_CONSUMER_COUNT', 10),
        'block_ms' => (int) env('EBILLING_CONSUMER_BLOCK_MS', 5000),
        'pending_idle_ms' => (int) env('EBILLING_PENDING_IDLE_MS', 60000),
    ],

    'handler' => env('EBILLING_FISCAL_HANDLER'),
    'outbox_batch_size' => (int) env('EBILLING_OUTBOX_BATCH_SIZE', 100),
];
