<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAlbumRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $album = $this->route('album');
        return $album && $this->user()->id === $album->user_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'visibility' => [
                'sometimes',
                'required',
                Rule::in(['public', 'private', 'followers_only', 'link_only'])
            ],
            'cover' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'], // Max 5MB
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'title' => 'album title',
            'description' => 'album description',
            'visibility' => 'visibility setting',
            'cover' => 'cover image',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Please provide a title for your album.',
            'title.max' => 'The album title cannot exceed 255 characters.',
            'visibility.required' => 'Please select a visibility setting for your album.',
            'visibility.in' => 'Invalid visibility setting selected.',
            'cover.image' => 'The cover must be an image file.',
            'cover.max' => 'The cover image must not be larger than 5MB.',
        ];
    }
}
