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

    'twofactor' => [
        'api_key' => env('TWOFACTOR_API_KEY'),
        'template' => env('TWOFACTOR_OTP_TEMPLATE', 'OTP1'),
    ],

    'otp' => [
        // While developing, skip the SMS provider entirely and hand the generated
        // OTP back in the API response so it can be shown on-screen. Set to
        // false (or remove the env var) before going live.
        // Defense in depth: hard-disabled whenever APP_ENV=production, regardless of what
        // OTP_DEV_MODE is set to — a .env accidentally copied from a dev/staging box (or an
        // env var left on by mistake) can never leak a real customer's OTP in production. This
        // is the single choke point both PhoneAuthController and PromoLeadController read
        // through, so nothing else needs to duplicate this check. Reads the raw APP_ENV env var
        // (not app()->environment(), which isn't safe to call this early in config loading —
        // it breaks `artisan config:clear`/`config:cache` with a cryptic container error).
        'dev_mode' => env('OTP_DEV_MODE', false) && env('APP_ENV', 'production') !== 'production',
    ],

    'razorpay' => [
        'key' => env('RAZORPAY_KEY_ID'),
        'secret' => env('RAZORPAY_KEY_SECRET'),
        'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
    ],

    'aisensy' => [
        'api_key' => env('AISENSY_API_KEY'),
        'base_url' => env('AISENSY_BASE_URL', 'https://backend.aisensy.com/campaign/t1/api/v2'),
        // one AiSensy campaign (= one approved WhatsApp template) per event — fill these in with
        // the real campaign names from the AiSensy dashboard. A blank value for a given event
        // just means AiSensyService::send() skips that event silently (logs a warning), so it's
        // safe to configure only some of these to start with. No 'order_placed' campaign on
        // purpose — the shop only wants one message, sent when the order is confirmed, not a
        // separate one at checkout too.
        'campaigns' => [
            'order_confirmed' => env('AISENSY_CAMPAIGN_ORDER_CONFIRMED'),
            'out_for_delivery' => env('AISENSY_CAMPAIGN_OUT_FOR_DELIVERY'),
            'delivered' => env('AISENSY_CAMPAIGN_DELIVERED'),
            'cancelled' => env('AISENSY_CAMPAIGN_CANCELLED'),
            'abandoned_cart' => env('AISENSY_CAMPAIGN_ABANDONED_CART'),
        ],

        // only needed for a template that has an image/document header — AiSensy rejects the
        // send with "Media URL Missing" otherwise. Leave blank for any event whose template has
        // no such header; AiSensyService::send() only attaches this when a URL is actually set.
        'media' => [
            'order_confirmed' => env('AISENSY_MEDIA_ORDER_CONFIRMED'),
            'out_for_delivery' => env('AISENSY_MEDIA_OUT_FOR_DELIVERY'),
            'delivered' => env('AISENSY_MEDIA_DELIVERED'),
            'cancelled' => env('AISENSY_MEDIA_CANCELLED'),
            'abandoned_cart' => env('AISENSY_MEDIA_ABANDONED_CART'),
        ],
    ],

];
