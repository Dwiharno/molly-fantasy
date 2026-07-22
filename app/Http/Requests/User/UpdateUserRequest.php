<?php

namespace App\Http\Requests\User;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->user() && ! $this->user()->isSuperAdmin()) {
            $this->merge(['store_id' => $this->user()->store_id ?? \App\Models\Store::where('code', 'S040')->value('id')]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    public function rules(): array
    {
        $allowedRoles = [User::ROLE_ADMIN, User::ROLE_STAFF];
        if ($this->user()->isSuperAdmin()) {
            $allowedRoles[] = User::ROLE_SUPER_ADMIN;
            $allowedRoles[] = User::ROLE_AREA_MANAGER;
        }

        $target = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($target->id)],
            'role' => ['required', 'in:'.implode(',', $allowedRoles)],
            'store_id' => ['required', 'exists:stores,id'],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Email sudah terdaftar.',
            'role.in' => 'Anda tidak memiliki hak untuk memberikan role tersebut.',
        ];
    }
}
