<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:64', 'unique:tenant.users,username', 'regex:/^[a-zA-Z0-9_.-]+$/'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:tenant.users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'office_id' => ['required', 'exists:tenant.offices,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'office_id.required' => 'Please select your office.',
            'office_id.exists' => 'The selected office is invalid.',
            'username.regex' => 'Username may only contain letters, numbers, dots, underscores and hyphens.',
        ];
    }
}
