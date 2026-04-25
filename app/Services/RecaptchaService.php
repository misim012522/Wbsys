<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class RecaptchaService
{
    /**
     * Verify reCAPTCHA response with Google API.
     */
    public function verify(string $response, ?string $remoteIp = null): bool
    {
        if (! config('recaptcha.enabled') || app()->environment(['local', 'testing'])) {
            return true;
        }

        $secret = config('recaptcha.secret_key');
        if (! $secret) {
            return true;
        }

        $params = [
            'secret' => $secret,
            'response' => $response,
        ];
        if ($remoteIp) {
            $params['remoteip'] = $remoteIp;
        }

        $verify = config('recaptcha.verify_ssl', true);
        $result = Http::withOptions(['verify' => $verify])
            ->asForm()
            ->post('https://www.google.com/recaptcha/api/siteverify', $params);

        if (! $result->successful()) {
            return false;
        }

        $body = $result->json();

        return isset($body['success']) && $body['success'] === true;
    }
}
