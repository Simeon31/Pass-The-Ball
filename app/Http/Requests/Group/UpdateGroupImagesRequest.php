<?php

namespace App\Http\Requests\Group;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGroupImagesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('updateImages', $this->route('group'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cover' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:2048'], // Max 2MB
            'thumbnail' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:2048'], // Max 2MB
        ];
    }

    /**
     * Get custom attribute names.
     */
    public function attributes(): array
    {
        return [
            'cover' => 'cover image',
            'thumbnail' => 'group thumbnail',
        ];
    }
}
