<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'hash_id'        => $this->hash_id,
            'title'          => $this->title,
            'prompt_topic'   => $this->prompt_topic,
            'sections_count' => count($this->content['sections'] ?? []),
            'avg_rating'     => round((float) $this->ratings_avg_rating, 1),
            'ratings_count'  => (int) $this->ratings_count,
            'views_count'    => (int) $this->views_count,
            'user_rating'    => $user ? optional($this->ratings->first())->rating : null,
            'author'         => $this->user ? [
                'id'   => $this->user->id,
                'name' => $this->user->name,
            ] : null,
            'created_at'     => $this->created_at->toISOString(),
        ];
    }
}
