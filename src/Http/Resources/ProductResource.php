<?php

namespace Fpaipl\Brandy\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Fpaipl\Brandy\Http\Resources\ProductRangeResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'id' => $this->id,
            'sid' => $this->sid,
            'moq' => $this->moq,
            'image' => $this->getFirstImage($this->options), // Implement this function in your model or here
            'options' =>  collect($this->options)->map(function ($option) {
                return [
                    'sid' => $option->sid,
                    'name' => $option->name,
                    'image' => $option->image,
                    'preview' => $option->preview,
                    'images' => collect($option->image),
                ];
            }),
            'ranges' => ProductRangeResource::collection($this->ranges),
            'tags' => $this->tags,
        ];
    }
    
    /**
     * Optionally, implement this function to fetch the first image
     * You could fetch it from a relation or some other way.
     */
    private function getFirstImage($options): string
    {
        // Your implementation here
        return collect($options)->first()->image;
    }
}
