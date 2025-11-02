<?php

namespace App\Http\Requests\Group;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGroupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('group'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'about' => ['nullable', 'string', 'max:5000'],
            'auto_approval' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure auto_approval is explicitly set (handle unchecked checkbox)
        if ($this->has('auto_approval')) {
            $this->merge([
                'auto_approval' => $this->boolean('auto_approval'),
            ]);
        }
    }

    /**
     * Get custom attribute names.
     */
    public function attributes(): array
    {
        return [
            'name' => 'group name',
            'about' => 'about section',
            'auto_approval' => 'auto-approval setting',
        ];
    }
}
