<?php

namespace App\Services;

use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

class ProductCategoryService
{
    public function store(array $validated): Model|Builder
    {
        return ProductCategory::query()->create($validated);
    }

    public function update(array $validated, ProductCategory $category): Model|Builder
    {
        $category->update($validated);
        return $category->refresh();
    }

    public function delete(ProductCategory $productCategory): JsonResponse
    {
        $productCategory->delete();
        return response()->json('', 200);
    }
}
