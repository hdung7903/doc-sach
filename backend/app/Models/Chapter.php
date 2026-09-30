<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chapter extends Model
{
    use HasUuids;

    protected $fillable = ['book_id','title','position','content','word_count','duration_seconds'];

    public function book(): BelongsTo { return $this->belongsTo(Book::class); }
    public function chunks(): HasMany { return $this->hasMany(ChapterChunk::class)->orderBy('position'); }
    public function audioAssets(): HasMany { return $this->hasMany(AudioAsset::class); }
}
