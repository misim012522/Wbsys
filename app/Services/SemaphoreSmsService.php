<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SemaphoreSmsService
{
    public function send(string $phoneNumber, string $message): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        $apiKey = (string) config('services.semaphore.api_key');
        if ($apiKey === '') {
            \Log::warning('Semaphore SMS skipped: missing API key.');

            return false;
        }

        $normalizedNumber = $this->normalizePhoneNumber($phoneNumber);
        if ($normalizedNumber === null) {
            \Log::warning('Semaphore SMS skipped: invalid phone number.', [
                'phone_number' => $phoneNumber,
            ]);

            return false;
        }

        $payload = [
            'apikey' => $apiKey,
            'number' => $normalizedNumber,
            'message' => $message,
        ];

        $senderName = trim((string) config('services.semaphore.sender_name'));
        if ($senderName !== '') {
            $payload['sendername'] = $senderName;
        }

        $response = Http::asForm()->withoutVerifying()->post((string) config('services.semaphore.api_url'), $payload);

        if ($response->failed()) {
            \Log::error('Semaphore SMS send failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
                'phone_number' => $normalizedNumber,
            ]);

            return false;
        }

        return true;
    }

    private function isEnabled(): bool
    {
        return (bool) config('services.semaphore.enabled', false);
    }

    private function normalizePhoneNumber(string $phoneNumber): ?string
    {
        $digits = preg_replace('/\D+/', '', $phoneNumber) ?? '';

        if ($digits === '') {
            return null;
        }

        // Supports PH mobile format by converting local 09xxxxxxxxx into 63xxxxxxxxxx.
        if (str_starts_with($digits, '09') && strlen($digits) === 11) {
            return '63'.substr($digits, 1);
        }

        if (str_starts_with($digits, '639') && strlen($digits) === 12) {
            return $digits;
        }

        return null;
    }
}
