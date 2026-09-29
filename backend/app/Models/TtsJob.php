<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Concerns\\HasUuids;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

class TtsJob extends Model
{
    use HasUuids;

    protected $fillable = ['book_id','chapter_id','status','provider','voice','processed_chunks','total_chunks','error_message','started_at','finished_at'];

    protected $casts = ['started_at'=>'datetime','finished_at'=>'datetime'];

    public function book(): BelongsTo { return $this->belongsTo(Book::class); }
    public function chapter(): BelongsTo { return $this->belongsTo(Chapter::class); }
}
