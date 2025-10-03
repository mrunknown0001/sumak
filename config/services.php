<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'api_url' => env('OPENAI_API_URL', 'https://api.openai.com/v1/chat/completions'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'max_retries' => env('OPENAI_MAX_RETRIES', 3),
        'timeout' => env('OPENAI_TIMEOUT', 120),
        'cost_per_1k_tokens' => env('OPENAI_COST_PER_1K', 0.00015),
        'max_requests_per_minute' => env('OPENAI_MAX_REQUESTS_PER_MINUTE', 10),
        'hourly_spending_limit' => env('OPENAI_HOURLY_SPENDING_LIMIT', 5.00),
        'max_content_size' => env('OPENAI_MAX_CONTENT_SIZE', 50000),
    ],

];
