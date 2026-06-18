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

    'moyasar' => [
        'secret' => env('MOYASAR_SECRET_KEY'),
        'webhook_secret' => env('MOYASAR_WEBHOOK_SECRET'),
    ],

    'social' => [
        'upload_secret' => env('SOCIAL_UPLOAD_SECRET'),
    ],

    'tamrat' => [
        'review_url' => env('REVIEW_URL', 'https://wa.me/966548036906'),
        'winback_code' => env('WINBACK_CODE', ''),
        'store_url' => env('STORE_URL', 'https://tamratdates.com'),
    ],

    'brevo' => [
        'key' => env('BREVO_API_KEY'),
        'list_id' => env('BREVO_LIST_ID'),
    ],

    'ga4' => [
        'measurement_id' => env('GA4_MEASUREMENT_ID'),
        'mp_secret' => env('GA4_MP_API_SECRET'),
    ],

    'chatwoot' => [
        'base_url'       => env('CHATWOOT_BASE_URL', 'http://127.0.0.1:3001'),
        'account_id'     => env('CHATWOOT_ACCOUNT_ID', 1),
        'bot_token'      => env('CHATWOOT_BOT_TOKEN'),
        'api_token'      => env('CHATWOOT_API_TOKEN'),   // agent identity: full read+write
        'bot_agent_id'   => env('CHATWOOT_BOT_AGENT_ID'), // user id the bot posts as (to spot human agents)
        'webhook_secret' => env('CHATWOOT_WEBHOOK_SECRET'),
        'cs_team_id'     => env('CHATWOOT_CS_TEAM_ID'),
    ],

    'anthropic' => [
        'key'   => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-4-6'),
    ],

];
