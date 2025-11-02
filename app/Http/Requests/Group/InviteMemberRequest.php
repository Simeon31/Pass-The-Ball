<?php

namespace App\Http\Requests\Group;

use Illuminate\Foundation\Http\FormRequest;

class InviteMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('inviteMembers', $this->route('group'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'role' => ['nullable', 'in:member,moderator,admin'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'role' => $this->input('role', 'member'),
        ]);
    }

    /**
     * Get custom attribute names.
     */
    public function attributes(): array
    {
        return [
            'user_id' => 'user',
            'role' => 'member role',
        ];
    }
}
