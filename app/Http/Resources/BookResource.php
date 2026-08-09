<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'title'            => $this->title,
            'author_name'      => $this->author_name,
            'isbn'             => $this->isbn,
            'published_year'   => $this->published_year,
            'format'           => $this->format,
            'pages'            => $this->pages,
            'language'         => $this->language,
            'description'      => $this->description,
            'cover_image'      => $this->cover_image_url,
            'category'         => $this->whenLoaded('category', fn () => $this->category->only(['id', 'name'])),
            'publisher'        => $this->whenLoaded('publisher', fn () => $this->publisher->only(['id', 'name'])),
            'genres'           => $this->whenLoaded('genres', fn () => $this->genres->map->only(['id', 'name'])),
            'total_copies'     => $this->total_copies ?? null,
            'available_copies' => $this->available_copies ?? null,
        ];
    }
}
