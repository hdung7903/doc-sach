<?php

namespace App\\Http\\Requests;

use Illuminate\\Foundation\\Http\\FormRequest;

class UpdateReadingProgressRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    public function rules(): array
    {
        return [
            'chapter_id'=>'nullable|uuid',
            'position_seconds'=>'required|integer|min:0',
            'progress_percent'=>'required|numeric|min:0|max:100',
        ];
    }
}
