<?php

namespace App\Http\Resources\Public;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BannerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'image' => $this->image_url,
            'button_text' => $this->button_text,
            'button_link' => $this->button_link,
            'position' => $this->position,
            'sort_order' => (int) $this->sort_order,
            'is_active' => (bool) $this->is_active,
            'starts_at' => optional($this->starts_at)?->toISOString(),
            'ends_at' => optional($this->ends_at)?->toISOString(),
        ];
    }
}