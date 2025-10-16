<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Mews\Purifier\Facades\Purifier;

class StorePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => ['nullable', 'string'],
            'user_id' => ['numeric', 'exists:users,id']
        ];
    }

    protected function prepareForValidation(): void
    {
        // Sanitize HTML content before validation
        if ($this->has('body') && !empty($this->body)) {
            $this->merge([
                'body' => Purifier::clean($this->body, 'post_content'),
                'user_id' => auth()->id()
            ]);
        } else {
            $this->merge([
                'user_id' => auth()->id()
            ]);
        }
    }
}
