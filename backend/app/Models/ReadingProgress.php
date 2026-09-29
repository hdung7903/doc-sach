<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

class ReadingProgress extends Model
{
    protected $fillable = ['user_id','book_id','chapter_id','position_seconds','progress_percent','last_read_at'];

    protected $casts = ['progress_percent'=>'decimal:2','last_read_at'=>'datetime'];

    public function book(): BelongsTo { return $this->belongsTo(Book::class); }
    public function chapter(): BelongsTo { return $this->belongsTo(Chapter::class); }
}
