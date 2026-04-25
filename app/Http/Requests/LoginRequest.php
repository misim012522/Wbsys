<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'login' => ['required', 'string'],
            'password' => ['required'],
            'tenant_id' => ['nullable', 'integer'],
        ];

        if (config('recaptcha.enabled') && ! app()->environment(['local', 'testing']) && config('recaptcha.secret_key')) {
            $rules['g-recaptcha-response'] = ['required'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'g-recaptcha-response.required' => 'Please complete the reCAPTCHA verification.',
        ];
    }
}
