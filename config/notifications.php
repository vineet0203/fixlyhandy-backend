<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Notification Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for notification queue jobs including retry settings.
    |
    */

    'tries' => env('NOTIFICATION_TRIES', 3),
    'timeout' => env('NOTIFICATION_TIMEOUT', 30),
    'backoff' => [
        (int) env('NOTIFICATION_BACKOFF_1', 10),
        (int) env('NOTIFICATION_BACKOFF_2', 30),
        (int) env('NOTIFICATION_BACKOFF_3', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Queues
    |--------------------------------------------------------------------------
    |
    | Queue names for different notification types.
    |
    */
    'queues' => [
        'emails' => env('NOTIFICATION_EMAIL_QUEUE', 'emails'),
        'notifications' => env('NOTIFICATION_DB_QUEUE', 'notifications'),
        'sms' => env('NOTIFICATION_SMS_QUEUE', 'sms'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Notification Settings
    |--------------------------------------------------------------------------
    |
    | Settings for email notifications.
    |
    */
    'email' => [
        'enabled' => env('NOTIFICATION_EMAIL_ENABLED', true),
        'delay' => env('NOTIFICATION_EMAIL_DELAY', 0),
        'retry_on_failure' => env('NOTIFICATION_EMAIL_RETRY', true),
        'max_retries' => env('NOTIFICATION_EMAIL_MAX_RETRIES', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Registration Notification Settings
    |--------------------------------------------------------------------------
    */
    'registration' => [
        'send_welcome_email' => env('SEND_WELCOME_EMAIL', true),
        'send_admin_notification' => env('SEND_ADMIN_REGISTRATION_NOTIFICATION', true),
        'admin_notification_delay' => env('ADMIN_NOTIFICATION_DELAY', 0),
        'welcome_email_delay' => env('WELCOME_EMAIL_DELAY', 0),
        //admin email fallback 
        'admin_email_fallback' => env('MAIL_ADMIN_EMAIL', 'admin@example.com'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Email Configuration
    |--------------------------------------------------------------------------
    */
    'admin' => [
        'email' => env('MAIL_ADMIN_EMAIL', 'admin@example.com'),
        'name' => env('MAIL_ADMIN_NAME', 'System Administrator'),
    ],
];
