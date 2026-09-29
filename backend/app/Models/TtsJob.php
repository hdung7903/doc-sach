<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TtsJob extends Model
{
    use HasUuids;

    protected $fillable = [
        'book_id', 'chapter_id', 'status', 'provider', 'engine', 'voice',
        'speed', 'format', 'text_hash', 'processed_chunks', 'total_chunks',
        'error_message', 'started_at', 'finished_at',
    ];

    protected $casts = [
        'speed' => 'decimal:2',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function book(): BelongsTo { return $this->belongsTo(Book::class); }
    public function chapter(): BelongsTo { return $this->belongsTo(Chapter::class); }
}
