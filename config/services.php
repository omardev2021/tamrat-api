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

    // Shared secret for operator/admin endpoints (fulfillment ops). Fails closed:
    // the endpoints deny all requests when this is unset.
    'admin' => [
        'secret' => env('ADMIN_SECRET'),
    ],

    // Carrier → customer tracking-URL templates ({awb} is replaced with the
    // tracking number). Matched on a substring of the carrier name. Verify/extend
    // these for the courier you actually use; an unknown carrier just omits the link.
    'carriers' => [
        'smsa'       => env('TRACK_URL_SMSA', 'https://www.smsaexpress.com/track?tracknumbers={awb}'),
        'aramex'     => env('TRACK_URL_ARAMEX', 'https://www.aramex.com/track/results?ShipmentNumber={awb}'),
        'dhl'        => env('TRACK_URL_DHL', 'https://www.dhl.com/sa-en/home/tracking.html?tracking-id={awb}'),
        'spl'        => env('TRACK_URL_SPL', 'https://splonline.com.sa/en/track-and-trace/?trackingNumber={awb}'),
        'saudi post' => env('TRACK_URL_SAUDIPOST', 'https://splonline.com.sa/en/track-and-trace/?trackingNumber={awb}'),
    ],

    'tamrat' => [
        'review_url' => env('REVIEW_URL', 'https://wa.me/966548036906'),
        'winback_code' => env('WINBACK_CODE', ''),
        'store_url' => env('STORE_URL', 'https://tamratdates.com'),
        // Master frequency cap: minimum days between any two lifecycle emails to
        // the same customer (reorder/review/win-back). 0 disables the cap.
        'retention_min_gap_days' => env('RETENTION_MIN_GAP_DAYS', 5),
    ],

    // WhatsApp lifecycle/retention nudges. Sending is OFF until an approved
    // template exists — set LIFECYCLE_WA_ENABLED=true + LIFECYCLE_WA_TEMPLATE.
    'lifecycle' => [
        'wa_enabled' => env('LIFECYCLE_WA_ENABLED', false),
        'template_name' => env('LIFECYCLE_WA_TEMPLATE'),
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
