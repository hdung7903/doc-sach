<?php

namespace App\\Http\\Resources;

use Illuminate\\Http\\Request;
use Illuminate\\Http\\Resources\\Json\\JsonResource;

class ChapterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'=>$this->id,'book_id'=>$this->book_id,'title'=>$this->title,
            'position'=>$this->position,'content'=>$this->content,
            'word_count'=>$this->word_count,'duration_seconds'=>$this->duration_seconds,
        ];
    }
}
