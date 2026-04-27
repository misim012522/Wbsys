<?php

namespace App\Http\Requests;

use App\Support\ReservedUsernames;
use App\Support\TenantWorkspaceUrlValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CentralTenantSignupRequest extends FormRequest
{
    private function centralTable(string $table): string
    {
        return app()->environment('testing') ? $table : 'central.'.$table;
    }

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
            'tenant_name' => ['required', 'string', 'max:255'],
            'tenant_admin_username' => [
                'required',
                'string',
                'max:255',
                'regex:/^[A-Za-z0-9._-]+$/',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (ReservedUsernames::isReservedForTenant(is_string($value) ? $value : null)) {
                        $fail(ReservedUsernames::tenantMessage());
                    }
                },
            ],
            'plan_id' => [
                'required',
                Rule::exists($this->centralTable('plans'), 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'address' => ['required', 'string', 'max:500'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.$this->centralTable('tenants').',email'],
            'contact_number' => ['required', 'string', 'max:50'],
        ];

        // Temporarily disabled for testing
        // if (config('recaptcha.enabled') && config('recaptcha.secret_key')) {
        //     $rules['g-recaptcha-response'] = ['required'];
        // }

        return $rules;
    }

    public function withValidator($validator): void
    {
        \Log::info('Form validation running', [
            'all_input' => $this->all(),
            'errors' => $validator->errors()->all(),
        ]);

        $validator->after(function ($validator): void {
            $generatedSubdomain = Str::slug((string) $this->input('tenant_name')) ?: 'tenant';

            \Log::info('Subdomain validation', [
                'tenant_name' => $this->input('tenant_name'),
                'generated_subdomain' => $generatedSubdomain,
            ]);

            foreach (TenantWorkspaceUrlValidator::validate(null, $generatedSubdomain) as $message) {
                \Log::error('Subdomain validation failed', ['message' => $message]);
                $validator->errors()->add('tenant_name', $message);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tenant_name.required' => 'Please enter the tenant or organization name.',
            'tenant_admin_username.required' => 'Please enter the tenant administrator username.',
            'plan_id.required' => 'Please choose a subscription plan.',
            'plan_id.exists' => 'The selected subscription plan is invalid.',
            'g-recaptcha-response.required' => 'Please complete the reCAPTCHA verification.',
        ];
    }
}
