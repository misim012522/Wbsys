<?php
use Illuminate\Support\Facades\Route;
Route::get('/debug-tenant', function() {
    return [
        'host' => request()->header('host'),
        'xfh' => request()->header('X-Forwarded-Host'),
        'bound' => app()->bound('current_tenant'),
        'tenant' => app()->bound('current_tenant') ? app('current_tenant')->name : null,
        'app_url' => config('app.url'),
        'asset_url' => config('app.asset_url'),
        'generated_asset' => asset('build/assets/app.css'),
        'base_path_asset' => asset('resources/css/app.css'),
    ];
});
