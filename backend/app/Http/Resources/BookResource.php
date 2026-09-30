<?php

namespace App\\Http\\Resources;

use Illuminate\\Http\\Request;
use Illuminate\\Http\\Resources\\Json\\JsonResource;

class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'=>$this->id,'title'=>$this->title,'author'=>$this->author,
            'description'=>$this->description,'cover_url'=>$this->cover_path ? asset('storage/'.$this->cover_path) : null,
            'source_format'=>$this->source_format,'status'=>$this->status,
            'total_chapters'=>$this->total_chapters,'total_words'=>$this->total_words,
            'progress'=>$this->whenLoaded('readingProgress', fn () => (float) $this->readingProgress->progress_percent),
            'created_at'=>$this->created_at,'updated_at'=>$this->updated_at,
        ];
    }
}
