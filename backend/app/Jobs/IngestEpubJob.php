<?php

namespace App\Jobs;

use App\Models\Book;
use App\Services\EpubIngestionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

class IngestEpubJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 300;

    public function __construct(
        public string $bookId,
        public string $path,
        public string $disk,
    ) {}

    public function handle(EpubIngestionService $service): void
    {
        $book = Book::findOrFail($this->bookId);
        $storage = Storage::disk($this->disk);

        $temporaryPath = tempnam(sys_get_temp_dir(), 'doc-sach-epub-');
        if ($temporaryPath === false) {
            throw new \RuntimeException('Unable to create a temporary EPUB file.');
        }

        try {
            file_put_contents($temporaryPath, $storage->get($this->path));
            $service->ingest($book, $temporaryPath);
        } finally {
            @unlink($temporaryPath);
        }
    }

    public function failed(Throwable $exception): void
    {
        Book::whereKey($this->bookId)->update(['status' => 'failed']);
    }
}
