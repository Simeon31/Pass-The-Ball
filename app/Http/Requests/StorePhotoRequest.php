<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePhotoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Verify the album exists and user owns it
        $album = $this->route('album');

        if (!$album) {
            return false;
        }

        return $this->user()->id === $album->user_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'photos' => ['required', 'array', 'min:1', 'max:20'],
            'photos.*' => [
                'required',
                'image',
                'mimes:jpeg,jpg,png,gif,webp',
                'max:10240', // Max 10MB per image
            ],
            'titles' => ['nullable', 'array'],
            'titles.*' => ['nullable', 'string', 'max:255'],
            'descriptions' => ['nullable', 'array'],
            'descriptions.*' => ['nullable', 'string', 'max:1000'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'user_id' => auth()->id(),
            'album_id' => $this->route('album')->id,
        ]);
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'photos' => 'photo files',
            'photos.*' => 'photo',
            'titles.*' => 'photo title',
            'descriptions.*' => 'photo description',
            'tags.*' => 'tag',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'photos.required' => 'Please select at least one photo to upload.',
            'photos.max' => 'You can upload a maximum of 20 photos at once.',
            'photos.*.required' => 'Each photo is required.',
            'photos.*.image' => 'All files must be valid images.',
            'photos.*.mimes' => 'Photos must be in JPEG, PNG, GIF, or WebP format.',
            'photos.*.max' => 'Each photo must not be larger than 10MB.',
            'titles.*.max' => 'Photo titles cannot exceed 255 characters.',
            'descriptions.*.max' => 'Photo descriptions cannot exceed 1000 characters.',
            'tags.*.max' => 'Tags cannot exceed 50 characters.',
        ];
    }
}
