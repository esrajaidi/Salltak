<?php

return [
    'cart_import' => [
        'timeout' => (int) env('CART_IMPORT_TIMEOUT', 12),
        'shein_browser' => [
            'enabled' => filter_var(env('SHEIN_BROWSER_ENABLED', true), FILTER_VALIDATE_BOOL),
            'node_binary' => env('SHEIN_BROWSER_NODE', 'node'),
            'headless' => filter_var(env('SHEIN_BROWSER_HEADLESS', true), FILTER_VALIDATE_BOOL),
            'timeout_ms' => (int) env('SHEIN_BROWSER_TIMEOUT_MS', 35000),
            'process_timeout' => (int) env('SHEIN_BROWSER_PROCESS_TIMEOUT', 110),
            'manual_challenge_wait_ms' => (int) env('SHEIN_BROWSER_MANUAL_CHALLENGE_WAIT_MS', 60000),
            'profile_dir' => env('SHEIN_BROWSER_PROFILE_DIR', storage_path('app/shein-browser-profile')),
        ],
    ],
];
