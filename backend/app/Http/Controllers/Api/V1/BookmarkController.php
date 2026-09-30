<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Bookmark;
use App\Models\Book;
use App\Models\Chapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookmarkController extends Controller
{
    public function index(Request $request, Book $book): JsonResponse
    {
        abort_unless($book->user_id === $request->user()->id, 404);

        return response()->json([
            'data' => $book->bookmarks()
                ->where('user_id', $request->user()->id)
                ->with('chapter:id,title,position')
                ->latest()
                ->get(),
        ]);
    }

    public function store(Request $request, Book $book): JsonResponse
    {
        abort_unless($book->user_id === $request->user()->id, 404);

        $data = $request->validate([
            'chapter_id' => [
                'required',
                'uuid',
                function (string $attribute, mixed $value, \Closure $fail) use ($book) {
                    if (!Chapter::whereKey($value)->where('book_id', $book->id)->exists()) {
                        $fail('Chapter không thuộc sách này.');
                    }
                },
            ],
            'position' => 'required|integer|min:0|max:100',
            'note' => 'nullable|string|max:1000',
        ]);

        $bookmark = $request->user()->bookmarks()->create([
            'book_id' => $book->id,
            'chapter_id' => $data['chapter_id'],
            'position' => $data['position'],
            'note' => $data['note'] ?? null,
        ]);

        return response()->json($bookmark->load('chapter:id,title,position'), 201);
    }

    public function destroy(Request $request, Bookmark $bookmark): JsonResponse
    {
        abort_unless($bookmark->user_id === $request->user()->id, 404);

        $bookmark->delete();

        return response()->json(null, 204);
    }
}
