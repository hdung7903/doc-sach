<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Concerns\\HasUuids;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;
use Illuminate\\Database\\Eloquent\\Relations\\HasMany;

class Book extends Model
{
    use HasUuids;

    protected $fillable = ['user_id','title','author','description','cover_path','source_path','source_format','status','total_chapters','total_words'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function chapters(): HasMany { return $this->hasMany(Chapter::class)->orderBy('position'); }
    public function ttsJobs(): HasMany { return $this->hasMany(TtsJob::class); }
}
