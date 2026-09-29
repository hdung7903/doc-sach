<?php

namespace App\\Jobs;

use App\\Models\\AudioAsset;
use App\\Models\\Chapter;
use App\\Models\\ChapterChunk;
use App\\Models\\TtsJob;
use Illuminate\\Bus\\Queueable;
use Illuminate\\Contracts\\Queue\\ShouldQueue;
use Illuminate\\Foundation\\Bus\\Dispatchable;
use Illuminate\\Queue\\InteractsWithQueue;
use Illuminate\\Queue\\SerializesModels;
use Illuminate\\Support\\Facades\\Http;
use Illuminate\\Support\\Facades\\Storage;
use Throwable;

class GenerateChapterAudioJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 1800;

    public function __construct(public string $ttsJobId) {}

    public function handle(): void
    {
        $job = TtsJob::findOrFail($this->ttsJobId);
        $job->update(['status' => 'processing', 'started_at' => now()]);

        $chunks = ChapterChunk::where('chapter_id', $job->chapter_id)->orderBy('position')->get();
        $job->update(['total_chunks' => $chunks->count()]);

        foreach ($chunks as $chunk) {
            $response = Http::timeout(300)
                ->withHeaders(['X-Worker-Token' => (string) config('services.tts.token')])
                ->post(config('services.tts.url').'/v1/synthesize', [
                'text' => $chunk->content,
                'voice' => $job->voice,
                'format' => 'mp3',
            ]);

            $response->throw();

            $path = 'audio/'.$job->book_id.'/'.$job->chapter_id.'/'.$chunk->id.'.mp3';
            Storage::disk('public')->put($path, $response->body());

            AudioAsset::updateOrCreate(
                ['chapter_id' => $job->chapter_id, 'chunk_id' => $chunk->id],
                [
                    'storage_disk' => 'public',
                    'storage_path' => $path,
                    'mime_type' => 'audio/mpeg',
                    'size_bytes' => strlen($response->body()),
                    'voice' => $job->voice,
                    'speed' => 1.0,
                ]
            );

            $job->increment('processed_chunks');
        }

        $job->update(['status' => 'completed', 'finished_at' => now()]);
    }

    public function failed(Throwable $exception): void
    {
        TtsJob::whereKey($this->ttsJobId)->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
            'finished_at' => now(),
        ]);
    }
}
