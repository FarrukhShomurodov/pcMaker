<?php

namespace App\Services;

use App\Models\ComponentCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

class ComponentCategoryService
{
    public function store(array $validated): Model|Builder
    {
        return ComponentCategory::query()->create($validated);
    }

    public function update(array $validated, ComponentCategory $category): Model|Builder
    {
        $category->update($validated);
        return $category->refresh();
    }

    public function delete(ComponentCategory $category): JsonResponse
    {
        $category->delete();
        return response()->json('', 200);
    }
}
