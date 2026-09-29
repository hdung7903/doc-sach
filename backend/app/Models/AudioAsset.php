<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AudioAsset extends Model
{
    use HasUuids;

    protected $fillable = [
        'chapter_id', 'chunk_id', 'storage_disk', 'storage_path',
        'mime_type', 'duration_seconds', 'size_bytes', 'engine',
        'text_hash', 'format', 'voice', 'speed',
    ];

    protected $casts = ['speed' => 'decimal:2'];

    public function chapter(): BelongsTo { return $this->belongsTo(Chapter::class); }
    public function chunk(): BelongsTo { return $this->belongsTo(ChapterChunk::class, 'chunk_id'); }
}
