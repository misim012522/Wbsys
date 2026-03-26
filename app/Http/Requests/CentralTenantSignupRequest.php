<?php

namespace App\Http\Requests;

use App\Support\TenantWorkspaceUrlValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

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
        return [
            'tenant_name' => ['required', 'string', 'max:255'],
            'tenant_admin_username' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9._-]+$/'],
            'plan_id' => [
                'required',
                Rule::exists($this->centralTable('plans'), 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'address' => ['required', 'string', 'max:500'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.$this->centralTable('tenants').',email'],
            'contact_number' => ['required', 'string', 'max:50'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $generatedSubdomain = Str::slug((string) $this->input('tenant_name')) ?: 'tenant';

            foreach (TenantWorkspaceUrlValidator::validate(null, $generatedSubdomain) as $message) {
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
        ];
    }
}
