<?php

namespace App\\Http\\Controllers\\Api\\V1;

use App\\Http\\Controllers\\Controller;
use App\\Http\\Requests\\UpdateReadingProgressRequest;
use App\\Models\\Book;
use App\\Models\\ReadingProgress;
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;

class ReadingProgressController extends Controller
{
    public function show(Request $request, Book $book): JsonResponse
    {
        abort_unless($book->user_id === $request->user()->id, 404);
        $progress = ReadingProgress::where('user_id',$request->user()->id)->where('book_id',$book->id)->first();
        return response()->json($progress);
    }

    public function update(UpdateReadingProgressRequest $request, Book $book): JsonResponse
    {
        abort_unless($book->user_id === $request->user()->id, 404);
        $progress = ReadingProgress::updateOrCreate(
            ['user_id'=>$request->user()->id,'book_id'=>$book->id],
            [...$request->validated(),'last_read_at'=>now()]
        );
        return response()->json($progress);
    }
}
