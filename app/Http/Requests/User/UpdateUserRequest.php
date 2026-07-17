<?php

namespace App\Http\Requests\User;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    public function rules(): array
    {
        $allowedRoles = array_keys(User::ROLES);

        if (! $this->user()->isSuperAdmin()) {
            $allowedRoles = array_diff($allowedRoles, [User::ROLE_SUPER_ADMIN]);
        }

        $target = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($target->id)],
            'role' => ['required', 'in:'.implode(',', $allowedRoles)],
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
