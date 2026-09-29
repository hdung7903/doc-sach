<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Concerns\\HasUuids;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

class ChapterChunk extends Model
{
    use HasUuids;

    protected $fillable = ['chapter_id','position','content','word_count'];

    public function chapter(): BelongsTo { return $this->belongsTo(Chapter::class); }
}
