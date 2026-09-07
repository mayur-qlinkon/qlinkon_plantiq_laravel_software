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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
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
     'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
 
        // Default model: used by AiService (chatbot / text AI)
        'model' => env(
            'GEMINI_MODEL',
            'gemini-2.5-flash-lite'
        ),
 
        // OCR model: used by GeminiVisionService (Vision / image understanding)
        // Flash (not Lite) gives significantly better Vision accuracy.
        // Lite is retained for chatbot to keep costs low.
        'ocr_model' => env(
            'GEMINI_OCR_MODEL',
            'gemini-2.5-flash'
        ),
 
        'base_url' => 'https://generativelanguage.googleapis.com/v1beta/models',
    ],

    'google_tts' => [
        // Separate key from GEMINI_API_KEY: Cloud Text-to-Speech is a different
        // API and is usually restricted independently in the GCP console.
        'api_key'  => env('GOOGLE_TTS_API_KEY'),
        'base_url' => 'https://texttospeech.googleapis.com/v1',
    ],

    'razorpay' => [
        // Platform (super-admin) keys — used for tenant → platform subscription payments.
        'key_id'         => env('RAZORPAY_KEY_ID'),
        'key_secret'     => env('RAZORPAY_KEY_SECRET'),
        'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
        'mode'           => env('RAZORPAY_MODE', 'test'), // test | live
    ],

    'cashfree' => [
        'app_id'     => env('CASHFREE_APP_ID', ''),
        'secret_key' => env('CASHFREE_SECRET_KEY', ''),
        'sandbox'    => env('CASHFREE_SANDBOX', true),  // false in production
    ],


];
