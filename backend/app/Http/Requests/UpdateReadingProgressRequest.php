<?php

namespace App\Http\Requests;

use App\Models\Book;
use Illuminate\Foundation\Http\FormRequest;

class UpdateReadingProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'chapter_id' => [
                'nullable',
                'uuid',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if ($value !== null) {
                        $book = $this->route('book');
                        $bookId = $book instanceof Book ? $book->id : $book;
                        if (!\App\Models\Chapter::whereKey($value)->where('book_id', $bookId)->exists()) {
                            $fail('Chapter không thuộc sách này.');
                        }
                    }
                },
            ],
            'position_seconds' => 'sometimes|integer|min:0',
            'progress_percent' => 'sometimes|numeric|min:0|max:100',
            'text_position_percent' => 'sometimes|integer|min:0|max:100',
            'audio_position_seconds' => 'sometimes|integer|min:0',
        ];
    }
}
