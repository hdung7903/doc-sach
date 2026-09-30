<?php

namespace App\Jobs;

use App\Models\AudioAsset;
use App\Models\ChapterChunk;
use App\Models\TtsJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
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

        $chunks = ChapterChunk::where('chapter_id', $job->chapter_id)
            ->orderBy('position')
            ->get();

        $job->update(['total_chunks' => $chunks->count()]);

        $disk = config('filesystems.default');

        foreach ($chunks as $chunk) {
            $textHash = hash('sha256', trim($chunk->content));

            $cached = AudioAsset::query()
                ->where('chapter_id', $job->chapter_id)
                ->where('chunk_id', $chunk->id)
                ->where('engine', $job->engine)
                ->where('voice', $job->voice)
                ->where('speed', $job->speed)
                ->where('text_hash', $textHash)
                ->where('format', $job->format)
                ->exists();

            if ($cached) {
                continue;
            }

            $response = Http::timeout(300)
                ->withHeaders(['X-Worker-Token' => (string) config('services.tts.token')])
                ->post(config('services.tts.url').'/v1/synthesize', [
                    'text' => $chunk->content,
                    'voice' => $job->voice,
                    'speed' => (float) $job->speed,
                    'format' => $job->format,
                ]);

            $response->throw();

            $safeVoice = preg_replace('/[^A-Za-z0-9._-]+/', '-', $job->voice);
            $speedKey = number_format((float) $job->speed, 2, '.', '');
            $path = 'audio/'.$job->book_id.'/'.$job->chapter_id.'/'.$chunk->id.'/'.$textHash.'-'.$job->engine.'-'.$safeVoice.'-'.$speedKey.'.'.$job->format;

            $body = $response->body();
            $duration = max(0, (int) round((float) $response->header('X-Audio-Duration', 0)));
            Storage::disk($disk)->put($path, $body);

            AudioAsset::updateOrCreate(
                [
                    'chapter_id' => $job->chapter_id,
                    'chunk_id' => $chunk->id,
                    'engine' => $job->engine,
                    'voice' => $job->voice,
                    'speed' => $job->speed,
                    'text_hash' => $textHash,
                    'format' => $job->format,
                ],
                [
                    'storage_disk' => $disk,
                    'storage_path' => $path,
                    'mime_type' => 'audio/mpeg',
                    'duration_seconds' => $duration,
                    'size_bytes' => strlen($body),
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
