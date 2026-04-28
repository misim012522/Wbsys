<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    'version' => env('APP_VERSION', (function () {
        // In production, prefer an explicit version set via environment/config.
        if (env('APP_ENV', 'production') === 'production') {
            return '1.0.0';
        }

        if (! function_exists('exec')) {
            return '1.0.0';
        }

        try {
            // Use an OS-aware null device so local Git version discovery works on
            // both Unix-like systems and Windows without boot-time path errors.
            $nullDevice = DIRECTORY_SEPARATOR === '\\' ? 'NUL' : '/dev/null';

            // --tags: use tags if available
            // --always: fallback to commit hash if no tags
            // --dirty: append -dirty if there are uncommitted changes
            $version = trim((string) exec("git describe --tags --always --dirty 2>{$nullDevice}"));

            return $version !== '' ? $version : '1.0.0';
        } catch (\Throwable $e) {
            return '1.0.0';
        }
    })()),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    'asset_url' => env('ASSET_URL'),

    /*
    |--------------------------------------------------------------------------
    | QR Code Base URL
    |--------------------------------------------------------------------------
    |
    | This URL is used specifically for QR code generation. If set, QR codes
    | will use this URL instead of APP_URL, allowing you to use ngrok or other
    | tunneling services for QR codes while keeping the system APP_URL unchanged.
    |
    */

    'qr_base_url' => env('QR_BASE_URL', null),

    /*
    |--------------------------------------------------------------------------
    | Asset URL for Cross-Device Access
    |--------------------------------------------------------------------------
    |
    | This URL is used specifically for loading CSS/JS assets when accessing
    | the application via ngrok or other tunneling services. Set this to your
    | ngrok URL to ensure assets load correctly on external devices.
    |
    */

    'asset_base_url' => env('ASSET_BASE_URL', null),
    'local_asset_url' => env('ASSET_URL_LOCAL', 'http://localhost:8000'),
    'ngrok_asset_url' => env('ASSET_URL_NGROK', null),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */

    'timezone' => 'UTC',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

];
