<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChapterResource;
use App\Models\Book;
use App\Models\Chapter;
use Illuminate\Http\Request;

class ChapterController extends Controller
{
    public function index(Request $request, Book $book)
    {
        abort_unless($book->user_id === $request->user()->id, 404);
        return ChapterResource::collection($book->chapters()->paginate(50));
    }

    public function show(Request $request, Chapter $chapter): ChapterResource
    {
        abort_unless($chapter->book->user_id === $request->user()->id, 404);
        return new ChapterResource($chapter);
    }
}
