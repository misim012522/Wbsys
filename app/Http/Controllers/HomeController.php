<?php

namespace App\Http\Controllers;

use App\Support\TenantUrl;

class HomeController extends Controller
{
    public function index()
    {
        if (auth()->check()) {
            $user = auth()->user();

            if ($user->tenant && ! app()->bound('current_tenant')) {
                return redirect()->away(TenantUrl::dashboard($user->tenant, $user));
            }

            return redirect()->away(TenantUrl::forUserDashboard($user));
        }

        if (app()->bound('current_tenant')) {
            return redirect()->away(TenantUrl::login(app('current_tenant')));
        }

        return redirect()->route('login');
    }

    public function dashboard()
    {
        $user = auth()->user();

        return redirect()->away(TenantUrl::forUserDashboard($user));
    }
}
