<?php

namespace App\Http\Requests\Group;

use Illuminate\Foundation\Http\FormRequest;

class ApproveJoinRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $group = $this->route('group');
        $user = $this->user();

        \Log::info('Authorization check', [
            'user_id' => $user?->id,
            'group_id' => $group?->id,
            'group_user_id' => $group?->user_id,
            'is_owner' => $group?->isOwner($user),
        ]);

        $canApprove = $user->can('approveRequests', $group);

        \Log::info('Authorization result', [
            'can_approve' => $canApprove,
        ]);

        return $canApprove;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        \Log::info('Validation input', [
            'user_id' => $this->input('user_id'),
            'action' => $this->input('action'),
            'role' => $this->input('role'),
        ]);

        return [
            'user_id' => ['required', 'exists:users,id'],
            'action' => ['required', 'in:approve,reject'],
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
            'action' => 'action',
            'role' => 'member role',
        ];
    }
}
