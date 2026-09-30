<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadingProgress extends Model
{
    protected $fillable = [
        'user_id','book_id','chapter_id',
        'position_seconds','progress_percent',
        'text_position_percent','audio_position_seconds',
        'last_read_at',
    ];

    protected $casts = [
        'progress_percent' => 'decimal:2',
        'text_position_percent' => 'integer',
        'audio_position_seconds' => 'integer',
        'position_seconds' => 'integer',
        'last_read_at' => 'datetime',
    ];

    public function book(): BelongsTo { return $this->belongsTo(Book::class); }
    public function chapter(): BelongsTo { return $this->belongsTo(Chapter::class); }
}
