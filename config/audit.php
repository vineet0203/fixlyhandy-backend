<?php

use App\Models\ApplicationComment;
use App\Models\ApplicationCommunication;
use App\Models\ApplicationReview;
use App\Models\ApplicationStageHistory;
use App\Models\AuditLog;
use App\Models\PasswordHistory;
use App\Models\Role;
use App\Models\SystemErrorLog;
use App\Models\UserSecurityLog;

return [
    /*
    |--------------------------------------------------------------------------
    | Audit Log Configuration
    |--------------------------------------------------------------------------
    */

    'enabled' => env('AUDIT_LOG_ENABLED', false),

    'batch_size' => env('AUDIT_BATCH_SIZE', 100),

    'high_frequency_events' => [
        'login',
        'logout',
        'page_view',
        'api_request',
        'search',
        'file_download',
    ],

    'sensitive_fields' => [
        'password',
        'password_confirmation',
        'token',
        'secret',
        'api_key',
        'secret_key',
        'access_token',
        'refresh_token',
        'credit_card',
        'cvv',
        'expiry_date',
        'bank_account',
        'iban',
        'swift_code',
        'ssn',
        'social_security',
        'sin',
        'passport',
        'driver_license',
    ],

    'skip_models' => [
        AuditLog::class,
        UserSecurityLog::class,
        SystemErrorLog::class,
        Role::class,
        ApplicationStageHistory::class,
        ApplicationComment::class,
        ApplicationCommunication::class,
        ApplicationReview::class
    ],

    'retention_days' => env('AUDIT_RETENTION_DAYS', 730),

    'queue' => env('AUDIT_LOG_QUEUE', null),
];
