<?php

namespace App\\Http\\Controllers\\Api\\V1;

use App\\Http\\Controllers\\Controller;
use App\\Http\\Requests\\StoreBookRequest;
use App\\Http\\Resources\\BookResource;
use App\\Models\\Book;
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Str;

class BookController extends Controller
{
    public function index(Request $request) { return BookResource::collection($request->user()->books()->latest()->paginate(20)); }

    public function store(StoreBookRequest $request): BookResource
    {
        $file = $request->file('file');
        $path = $file->store('books/'.$request->user()->id, 'public');
        $book = $request->user()->books()->create([
            'title'=>$request->string('title'),'author'=>$request->input('author'),
            'description'=>$request->input('description'),'source_format'=>$request->string('source_format'),
            'status'=>'processing',
        ]);
        return new BookResource($book);
    }

    public function show(Request $request, Book $book): BookResource
    {
        abort_unless($book->user_id === $request->user()->id, 404);
        return new BookResource($book->loadCount('chapters'));
    }

    public function destroy(Request $request, Book $book): JsonResponse
    {
        abort_unless($book->user_id === $request->user()->id, 404);
        $book->delete();
        return response()->json(null, 204);
    }
}
