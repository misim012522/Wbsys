<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureStripeRegistrationConfigured
{
    public function handle(Request $request, Closure $next)
    {
        $issues = $this->configurationIssues();

        if ($issues !== []) {
            return response()->view('central.payment-setup-warning', [
                'issues' => $issues,
            ], 503);
        }

        return $next($request);
    }

    /**
     * @return array<int, string>
     */
    private function configurationIssues(): array
    {
        $issues = [];

        $publishable = (string) config('services.stripe.key', '');
        $secret = (string) config('services.stripe.secret', '');
        $webhook = (string) config('services.stripe.webhook_secret', '');

        if ($publishable === '' || ! str_starts_with($publishable, 'pk_')) {
            $issues[] = 'Missing or invalid STRIPE_KEY (expected value starting with pk_).';
        }

        if ($secret === '' || ! str_starts_with($secret, 'sk_')) {
            $issues[] = 'Missing or invalid STRIPE_SECRET (expected value starting with sk_).';
        }

        if ($webhook === '' || ! str_starts_with($webhook, 'whsec_')) {
            $issues[] = 'Missing or invalid STRIPE_WEBHOOK_SECRET (expected value starting with whsec_).';
        }

        return $issues;
    }
}
