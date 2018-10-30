<?php

return [
    'enabled' => env('WEB_MONITOR_ENABLED', false),
    'base_url' => env('WEB_MONITOR_BASE_URL', 'https://netmonitoring.co.uk'),
    'client_id' => env('WEB_MONITOR_CLIENT_ID'),
    'client_secret' => env('WEB_MONITOR_CLIENT_SECRET'),
    'queue' => env('WEB_MONITOR_QUEUE', 'default'),
];
