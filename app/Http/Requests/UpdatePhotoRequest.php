<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePhotoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $photo = $this->route('photo');
        return $photo && $this->user()->id === $photo->user_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'removed_tags' => ['nullable', 'array'],
            'removed_tags.*' => ['integer', 'exists:photo_tags,id'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'title' => 'photo title',
            'description' => 'photo description',
            'tags.*' => 'tag',
            'removed_tags.*' => 'removed tag',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.max' => 'The photo title cannot exceed 255 characters.',
            'description.max' => 'The photo description cannot exceed 1000 characters.',
            'tags.*.max' => 'Tags cannot exceed 50 characters.',
            'removed_tags.*.exists' => 'One or more tags to remove do not exist.',
        ];
    }
}
