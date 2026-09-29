<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateChapterAudioJob;
use App\Models\AudioAsset;
use App\Models\Chapter;
use App\Models\TtsJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TtsController extends Controller
{
    private const ENGINE = 'piper';
    private const DEFAULT_VOICE = 'vi_VN-vais1000-medium';
    private const FORMAT = 'mp3';

    public function store(Request $request, Chapter $chapter): JsonResponse
    {
        abort_unless($chapter->book->user_id === $request->user()->id, 404);

        $voice = (string) $request->input('voice', self::DEFAULT_VOICE);
        $speed = (float) $request->input('speed', 1.0);

        abort_if($speed < 0.5 || $speed > 2.0, 422, 'speed must be between 0.5 and 2.0');

        $chunks = $chapter->chunks()->get();
        $missing = $chunks->filter(function ($chunk) use ($chapter, $voice, $speed) {
            $hash = hash('sha256', trim($chunk->content));

            return !AudioAsset::query()
                ->where('chapter_id', $chapter->id)
                ->where('chunk_id', $chunk->id)
                ->where('engine', self::ENGINE)
                ->where('voice', $voice)
                ->where('speed', $speed)
                ->where('text_hash', $hash)
                ->where('format', self::FORMAT)
                ->exists();
        })->values();

        if ($missing->isEmpty()) {
            return response()->json([
                'id' => null,
                'status' => 'cached',
                'cached_chunks' => $chunks->count(),
                'total_chunks' => $chunks->count(),
            ]);
        }

        $existing = TtsJob::query()
            ->where('chapter_id', $chapter->id)
            ->whereIn('status', ['queued', 'processing'])
            ->where('engine', self::ENGINE)
            ->where('voice', $voice)
            ->where('speed', $speed)
            ->where('format', self::FORMAT)
            ->latest()
            ->first();

        if ($existing) {
            return response()->json([
                'id' => $existing->id,
                'status' => $existing->status,
                'cached_chunks' => $chunks->count() - $missing->count(),
                'total_chunks' => $chunks->count(),
            ], 202);
        }

        $job = TtsJob::create([
            'book_id' => $chapter->book_id,
            'chapter_id' => $chapter->id,
            'status' => 'queued',
            'provider' => 'self-hosted',
            'engine' => self::ENGINE,
            'voice' => $voice,
            'speed' => $speed,
            'format' => self::FORMAT,
            'processed_chunks' => $chunks->count() - $missing->count(),
            'total_chunks' => $chunks->count(),
        ]);

        GenerateChapterAudioJob::dispatch($job->id);

        return response()->json([
            'id' => $job->id,
            'status' => $job->status,
            'cached_chunks' => $job->processed_chunks,
            'total_chunks' => $job->total_chunks,
        ], 202);
    }

    public function show(Request $request, TtsJob $ttsJob): JsonResponse
    {
        abort_unless($ttsJob->book->user_id === $request->user()->id, 404);

        return response()->json($ttsJob);
    }
}
