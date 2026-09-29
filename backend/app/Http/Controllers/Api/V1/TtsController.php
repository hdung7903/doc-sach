<?php

namespace App\\Http\\Controllers\\Api\\V1;

use App\\Http\\Controllers\\Controller;
use App\\Jobs\\GenerateChapterAudioJob;
use App\\Models\\Chapter;
use App\\Models\\TtsJob;
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;

class TtsController extends Controller
{
    public function store(Request $request, Chapter $chapter): JsonResponse
    {
        abort_unless($chapter->book->user_id === $request->user()->id, 404);

        $job = TtsJob::create([
            'book_id' => $chapter->book_id,
            'chapter_id' => $chapter->id,
            'status' => 'queued',
            'provider' => 'self-hosted',
            'voice' => $request->input('voice'),
            'total_chunks' => $chapter->chunks()->count(),
        ]);

        GenerateChapterAudioJob::dispatch($job->id);

        return response()->json(['id' => $job->id, 'status' => $job->status], 202);
    }

    public function show(Request $request, TtsJob $ttsJob): JsonResponse
    {
        abort_unless($ttsJob->book->user_id === $request->user()->id, 404);

        return response()->json($ttsJob);
    }
}
