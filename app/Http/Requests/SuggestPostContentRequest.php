<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SuggestPostContentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'min:5', 'max:500'],
            'tone' => ['required', 'string', 'in:professional,casual,enthusiastic,inspiring,humorous'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'content.required' => 'Please provide some content to enhance.',
            'content.min' => 'Content must be at least 5 characters long.',
            'content.max' => 'Content must not exceed 500 characters.',
            'tone.required' => 'Please select a tone.',
            'tone.in' => 'Invalid tone selected.',
        ];
    }
}
