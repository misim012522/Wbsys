<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifier;

class VerifyCsrfToken extends BaseVerifier
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Allow tenant admin approval POSTs to avoid 419 in case of cookie domain mismatches.
        'admin/users/*/approve',
        'central/payments/stripe/webhook',
        'api/github/webhook',
    ];
}
