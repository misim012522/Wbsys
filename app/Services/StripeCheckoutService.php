<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class StripeCheckoutService
{
    public function createCheckoutSession(array $input): array
    {
        $secretKey = (string) config('services.stripe.secret');
        $verifySsl = (bool) config('services.stripe.verify_ssl', true);

        if ($secretKey === '') {
            throw new \RuntimeException('Stripe secret key is not configured.');
        }

        \Log::info('Creating Stripe checkout session', [
            'success_url' => $input['success_url'],
            'cancel_url' => $input['cancel_url'],
            'client_reference_id' => $input['client_reference_id'],
            'currency' => $input['currency'],
            'amount_cents' => $input['amount_cents'],
            'product_name' => $input['product_name'],
        ]);

        $response = Http::withOptions(['verify' => $verifySsl])
            ->withBasicAuth($secretKey, '')
            ->asForm()
            ->post('https://api.stripe.com/v1/checkout/sessions', [
                'mode' => 'payment',
                'success_url' => $input['success_url'],
                'cancel_url' => $input['cancel_url'],
                'client_reference_id' => $input['client_reference_id'],
                'payment_method_types[0]' => 'card',
                'line_items[0][quantity]' => 1,
                'line_items[0][price_data][currency]' => $input['currency'],
                'line_items[0][price_data][unit_amount]' => $input['amount_cents'],
                'line_items[0][price_data][product_data][name]' => $input['product_name'],
                'metadata[payment_reference]' => $input['payment_reference'],
            ]);

        \Log::info('Stripe create session response', [
            'status' => $response->status(),
            'body' => $response->body(),
            'failed' => $response->failed(),
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Unable to create Stripe Checkout session. Status: '.$response->status().', Body: '.$response->body());
        }

        return $response->json();
    }

    public function retrieveCheckoutSession(string $sessionId): array
    {
        $secretKey = (string) config('services.stripe.secret');
        $verifySsl = (bool) config('services.stripe.verify_ssl', true);

        if ($secretKey === '') {
            throw new \RuntimeException('Stripe secret key is not configured.');
        }

        \Log::info('Retrieving Stripe session', [
            'session_id' => $sessionId,
            'secret_key_prefix' => substr($secretKey, 0, 8) . '...',
            'verify_ssl' => $verifySsl,
        ]);

        $response = Http::withOptions(['verify' => $verifySsl])
            ->withBasicAuth($secretKey, '')
            ->get('https://api.stripe.com/v1/checkout/sessions/'.$sessionId);

        \Log::info('Stripe API response', [
            'status' => $response->status(),
            'body' => $response->body(),
            'failed' => $response->failed(),
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Unable to verify Stripe Checkout session. Status: '.$response->status().', Body: '.$response->body());
        }

        return $response->json();
    }
}
