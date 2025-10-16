<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Mews\Purifier\Facades\Purifier;

class UpdatePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $post = $this->route('post');
        return $post && $this->user()->id === $post->user_id;
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
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => [
                'file',
                'mimes:jpg,jpeg,png,gif,webp,mp4,webm,mov,avi,pdf',
                function ($attribute, $value, $fail) {
                    if (!$value instanceof \Illuminate\Http\UploadedFile) {
                        return;
                    }

                    $mimeType = $value->getMimeType();
                    $size = $value->getSize();

                    // Image: max 10MB
                    if (str_starts_with($mimeType, 'image/') && $size > 10 * 1024 * 1024) {
                        $fail('Images must not be larger than 10MB.');
                    }

                    // Video: max 50MB
                    if (str_starts_with($mimeType, 'video/') && $size > 50 * 1024 * 1024) {
                        $fail('Videos must not be larger than 50MB.');
                    }

                    // PDF: max 20MB
                    if ($mimeType === 'application/pdf' && $size > 20 * 1024 * 1024) {
                        $fail('PDFs must not be larger than 20MB.');
                    }
                },
            ],
            'deleted_attachments' => ['nullable', 'array'],
            'deleted_attachments.*' => ['integer', 'exists:post_attachments,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Sanitize HTML content before validation
        if ($this->has('body') && !empty($this->body)) {
            $this->merge([
                'body' => Purifier::clean($this->body, 'post_content'),
            ]);
        }
    }
}
