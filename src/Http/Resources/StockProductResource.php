<?php

namespace Fpaipl\Brandy\Http\Resources;

use Illuminate\Http\Request;
use Fpaipl\Brandy\Models\Stock;
use Illuminate\Http\Resources\Json\JsonResource;
use Fpaipl\Brandy\Http\Resources\ProductRangeResource;
use Fpaipl\Brandy\Http\Resources\ProductOptionResource;

class StockProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = auth()->user();

        $stocks = null;
        if ($user->isManager() || $user->isStaff()) {
            $stocks = Stock::where('product_sid', $this->sid)->select(
                'product_option_sid', 
                'product_option_name', 
                'product_range_sid', 
                'product_range_name', 
                'quantity'
                )->get();
        }

        return [
            'sid' => $this->sid,
            'name' => $this->name,
            'image' => $this->getFirstImage($this->options, 'image'),
            'preview' => $this->getFirstImage($this->options, 'preview'),
            'options' => ProductOptionResource::collection($this->options),
            'ranges' => ProductRangeResource::collection($this->ranges),
            'stocks' => $stocks ?? null,
        ];
    }

    private function getFirstImage($options, $property): string
    {
        // Your implementation here
        return collect($options)->first()->$property;
    }
}
