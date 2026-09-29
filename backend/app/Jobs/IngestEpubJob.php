<?php

namespace App\\Jobs;

use App\\Models\\Book;
use App\\Services\\EpubIngestionService;
use Illuminate\\Bus\\Queueable;
use Illuminate\\Contracts\\Queue\\ShouldQueue;
use Illuminate\\Foundation\\Bus\\Dispatchable;
use Illuminate\\Queue\\InteractsWithQueue;
use Illuminate\\Queue\\SerializesModels;
use Throwable;

class IngestEpubJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 300;

    public function __construct(public string $bookId, public string $path) {}

    public function handle(EpubIngestionService $service): void
    {
        $book = Book::findOrFail($this->bookId);
        $service->ingest($book, $this->path);
    }

    public function failed(Throwable $exception): void
    {
        Book::whereKey($this->bookId)->update(['status'=>'failed']);
    }
}
