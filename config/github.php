<?php

return [
    'webhook' => [
        'secret' => env('GITHUB_WEBHOOK_SECRET'),
    ],
    'deployment' => [
        'token' => env('DEPLOYMENT_TOKEN'),
        'enable_backups' => env('ENABLE_DEPLOYMENT_BACKUPS', true),
        'enable_auto_rollback' => env('ENABLE_AUTO_ROLLBACK', false),
    ],
];