<?php

namespace App\\Http\\Requests;

use Illuminate\\Foundation\\Http\\FormRequest;

class StoreBookRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    public function rules(): array
    {
        return [
            'title'=>'required|string|max:255',
            'author'=>'nullable|string|max:255',
            'description'=>'nullable|string',
            'source_format'=>'required|in:epub,pdf',
            'file'=>'required|file|max:51200|mimes:epub,pdf',
        ];
    }
}
