<?php

return [

    /*
    |--------------------------------------------------------------------------
    | reCAPTCHA Enabled
    |--------------------------------------------------------------------------
    | Disable this for local development so workspace login links can be tested
    | without completing the browser challenge on every sign-in attempt.
    |
    */

    'enabled' => env('RECAPTCHA_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | reCAPTCHA Site Key
    |--------------------------------------------------------------------------
    | The site key (public key) for Google reCAPTCHA v2. Used in the frontend.
    | Get keys at: https://www.google.com/recaptcha/admin
    |
    */

    'site_key' => env('RECAPTCHA_SITE_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | reCAPTCHA Secret Key
    |--------------------------------------------------------------------------
    | The secret key for server-side verification. Never expose this in the frontend.
    |
    */

    'secret_key' => env('RECAPTCHA_SECRET_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Verify SSL when calling reCAPTCHA API
    |--------------------------------------------------------------------------
    | Set to false only if you get "unable to get local issuer certificate" on
    | Windows (e.g. RECAPTCHA_VERIFY_SSL=false in .env). Use true in production.
    |
    */

    'verify_ssl' => env('RECAPTCHA_VERIFY_SSL', true),

];
