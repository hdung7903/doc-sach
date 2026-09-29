<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

class Bookmark extends Model
{
    protected $fillable = ['user_id','book_id','chapter_id','position','note'];

    public function book(): BelongsTo { return $this->belongsTo(Book::class); }
    public function chapter(): BelongsTo { return $this->belongsTo(Chapter::class); }
}
