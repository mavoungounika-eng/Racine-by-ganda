<?php

namespace App\Http\Resources\Pos;

use Illuminate\Http\Resources\Json\JsonResource;

class PosProductResource extends JsonResource
{
    public function toArray($request): array
    {
        $thumbnail = null;
        if (!empty($this->main_image)) {
            $thumbnail = asset('storage/' . $this->main_image);
        } elseif ($this->relationLoaded('images') && $this->images->first()) {
            $thumbnail = $this->images->first()->url;
        }

        return [
            'id' => $this->id,
            'name' => $this->title,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'price' => $this->price,
            'stock_quantity' => $this->stock,
            'in_stock' => $this->stock > 0,
            'low_stock' => $this->stock > 0 && $this->stock <= 5,
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category?->id,
                    'name' => $this->category?->name,
                    'slug' => $this->category?->slug,
                    'parent_id' => $this->category?->parent_id,
                ];
            }),
            'thumbnail' => $thumbnail,
            'is_active' => (bool) $this->is_active,
            'description' => $this->description,
            'slug' => $this->slug,
            'product_type' => $this->product_type,
            'collection_id' => $this->collection_id,
            'user_id' => $this->user_id,
            'main_image' => $this->main_image,
            'images' => $this->whenLoaded('images', function () {
                return $this->images->map(fn ($img) => [
                    'id' => $img->id,
                    'url' => $img->url,
                    'is_main' => (bool) $img->is_main,
                ]);
            }),
        ];
    }
}
