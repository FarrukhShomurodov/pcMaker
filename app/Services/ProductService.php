<?php

namespace App\Services;

use App\Models\Product;
use App\Traits\PhotoTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ProductService
{
    use PhotoTrait;

    public function store(array $validated): Model|Builder
    {
        if (isset($validated['photos'])) {
            $validated['photos'] = $this->storePhotos('product_photos');
        }

        return Product::query()->create($validated);
    }

    public function update(array $validated, Product $product): Model|Builder
    {
        if (isset($validated['photos'])) {
            $validated['photos'] = $this->updatePhotoPaths($validated['photos'], 'product_photos', $product);
        }

        $product->update($validated);
        return $product->refresh();
    }

    public function delete(Product $product): JsonResponse
    {
        if ($product->photos) {
            foreach (json_decode($product->photos) as $photo) {
                if (Storage::disk('public')->exists($photo)) {
                    Storage::disk('public')->delete($photo);
                }
            }
        }
        $product->delete();
        return response()->json('', 200);
    }
}
