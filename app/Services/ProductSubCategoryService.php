<?php

namespace App\Services;

use App\Models\ProductSubCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

class ProductSubCategoryService
{
    public function store(array $validated): Model|Builder
    {
        return ProductSubCategory::query()->create($validated);
    }

    public function update(array $validated, ProductSubCategory $category): Model|Builder
    {
        $category->update($validated);
        return $category->refresh();
    }

    public function delete(ProductSubCategory $category): JsonResponse
    {
        $category->delete();
        return response()->json('', 200);
    }
}
