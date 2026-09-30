<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateReadingProgressRequest;
use App\Models\Book;
use App\Models\ReadingProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReadingProgressController extends Controller
{
    public function show(Request $request, Book $book): JsonResponse
    {
        abort_unless($book->user_id === $request->user()->id, 404);
        $progress = ReadingProgress::where('user_id', $request->user()->id)
            ->where('book_id', $book->id)
            ->first();

        return response()->json($progress);
    }

    public function update(UpdateReadingProgressRequest $request, Book $book): JsonResponse
    {
        abort_unless($book->user_id === $request->user()->id, 404);

        $data = $request->validated();
        $existing = ReadingProgress::where('user_id', $request->user()->id)
            ->where('book_id', $book->id)
            ->first();

        $payload = [
            'last_read_at' => now(),
            'chapter_id' => array_key_exists('chapter_id', $data) ? $data['chapter_id'] : $existing?->chapter_id,
            'position_seconds' => array_key_exists('position_seconds', $data) ? $data['position_seconds'] : ($existing?->position_seconds ?? 0),
            'progress_percent' => array_key_exists('progress_percent', $data) ? $data['progress_percent'] : ($existing?->progress_percent ?? 0),
            'text_position_percent' => array_key_exists('text_position_percent', $data) ? $data['text_position_percent'] : ($existing?->text_position_percent ?? 0),
            'audio_position_seconds' => array_key_exists('audio_position_seconds', $data) ? $data['audio_position_seconds'] : ($existing?->audio_position_seconds ?? 0),
        ];

        $progress = ReadingProgress::updateOrCreate(
            ['user_id' => $request->user()->id, 'book_id' => $book->id],
            $payload,
        );

        return response()->json($progress);
    }
}
